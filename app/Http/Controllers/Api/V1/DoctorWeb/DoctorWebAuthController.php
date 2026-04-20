<?php

namespace App\Http\Controllers\Api\V1\DoctorWeb;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DoctorWeb\DoctorWebAccessService;
use Google\Client as GoogleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DoctorWebAuthController extends Controller
{
    public function __construct(
        private DoctorWebAccessService $accessService
    ) {}

    private function getAllowedGoogleClientIds(): array
    {
        return config('services.google.allowed_client_ids', []);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid request',
                'errors' => $validator->errors(),
            ], 400);
        }

        $user = User::whereRaw('LOWER(email) = ?', [strtolower(trim($request->email))])
            ->where('is_deleted', false)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'These credentials do not match our records.',
            ], 200);
        }

        try {
            $doctor = $this->accessService->ensureDoctor($user);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => 'This account is not a doctor account.',
            ], 200);
        }

        $token = $user->createToken('doctor-web-token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Successfully',
            'token' => $token,
            'data' => [
                'user' => $user,
                'doctor' => $doctor,
            ],
        ], 200);
    }

    public function loginGoogle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_token' => 'required|string',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid request',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            DB::beginTransaction();

            $client = new GoogleClient();
            $payload = $client->verifyIdToken($request->id_token);

            if (!$payload) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid Google token',
                ], 200);
            }

            $allowedAudiences = $this->getAllowedGoogleClientIds();
            $aud = $payload['aud'] ?? null;
            $sub = $payload['sub'] ?? null;
            $email = isset($payload['email']) ? strtolower(trim($payload['email'])) : null;
            $requestedEmail = strtolower(trim($request->email));
            $emailVerified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if (!$sub || !$aud || !in_array($aud, $allowedAudiences, true)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Token audience is not allowed',
                ], 200);
            }

            if (!$email || !$emailVerified) {
                return response()->json([
                    'status' => false,
                    'message' => 'Google email is not verified',
                ], 200);
            }

            if ($requestedEmail !== $email) {
                return response()->json([
                    'status' => false,
                    'message' => 'Google email does not match token email',
                ], 200);
            }

            $user = User::whereRaw('LOWER(email) = ?', [$email])
                ->where('is_deleted', false)
                ->first();

            if (!$user) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'These credentials do not match our records.',
                ], 200);
            }

            try {
                $doctor = $this->accessService->ensureDoctor($user);
            } catch (\RuntimeException $e) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'This account is not a doctor account.',
                ], 200);
            }

            if (!empty($user->google_id) && $user->google_id !== $sub) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'This doctor account is linked to another Google account.',
                ], 200);
            }

            $user->google_id = $sub;
            $user->auth_provider = 'google';
            $user->save();

            $token = $user->createToken('doctor-web-token')->plainTextToken;

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Successfully',
                'token' => $token,
                'data' => [
                    'user' => $user,
                    'doctor' => $doctor,
                ],
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Login with Google failed',
                'error' => $e->getMessage(),
            ], 200);
        }
    }

    public function me(Request $request)
    {
        $user = $request->user();

        try {
            $doctor = $this->accessService->ensureDoctor($user);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Doctor profile not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'user' => $user,
                'doctor' => $doctor,
            ],
        ], 200);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user?->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully.',
        ], 200);
    }
}