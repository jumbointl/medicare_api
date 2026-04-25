<?php

namespace App\Http\Controllers\Api\V1\DoctorWeb;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VUser;
use App\Services\UserNormalizerService;
use Google\Client as GoogleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DoctorWebAuthController extends Controller
{
    public function __construct(
        private UserNormalizerService $userNormalizerService
    ) {
    }

    private function findDoctorVUserByEmail(string $email): ?VUser
    {
        $row = VUser::query()
            ->whereRaw('LOWER(email) = ?', [strtolower(trim($email))])
            ->where('is_deleted', 0)
            ->first();

        if (!$row || empty($row->doctor_id)) {
            return null;
        }

        return $row;
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

        \Log::info('DOCTOR_LOGIN request', [
            'email' => $request->email,
        ]);

        $vUser = $this->findDoctorVUserByEmail($request->email);

        if (!$vUser || !Hash::check($request->password, (string) $vUser->password)) {
            return response()->json([
                'status' => false,
                'message' => 'These credentials do not match our records.',
            ], 200);
        }

        $user = User::find($vUser->id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.',
            ], 404);
        }

        \Log::info('DOCTOR_LOGIN vUser found', [
            'exists' => !!$vUser,
            'id' => $vUser->id ?? null,
            'email' => $vUser->email ?? null,
            'doctor_id' => $vUser->doctor_id ?? null,
            'roles_raw' => $vUser->roles ?? null,
            'doctor_raw' => $vUser->doctor ?? null,
            'clinics_raw' => $vUser->clinics ?? null,
        ]);

        $token = $user->createToken('doctor-web-token')->plainTextToken;

        $normalized = $this->userNormalizerService->normalizeUser($vUser);

        \Log::info('DOCTOR_LOGIN success_payload', [
            'id' => $normalized['id'] ?? null,
            'doctor_id' => $normalized['doctor_id'] ?? null,
            'roles' => $normalized['roles'] ?? [],
            'role' => $normalized['role'] ?? null,
            'doctor' => $normalized['doctor'] ?? null,
            'clinics_count' => count($normalized['clinics'] ?? []),
            'owner_clinic' => $normalized['owner_clinic'] ?? null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Successfully',
            'token' => $token,
            'data' => $normalized,
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

        $client = new GoogleClient();
        $payload = $client->verifyIdToken($request->id_token);

        if (!$payload) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid Google token',
            ], 200);
        }

        $email = isset($payload['email']) ? strtolower(trim($payload['email'])) : null;
        $requestedEmail = strtolower(trim($request->email));

        if (!$email || $email !== $requestedEmail) {
            return response()->json([
                'status' => false,
                'message' => 'Google account email does not match.',
            ], 200);
        }

        $vUser = $this->findDoctorVUserByEmail($email);

        if (!$vUser) {
            return response()->json([
                'status' => false,
                'message' => 'This account is not a doctor account.',
            ], 200);
        }

        $user = User::find($vUser->id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $token = $user->createToken('doctor-web-token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Successfully',
            'token' => $token,
            'data' => $this->userNormalizerService->normalizeUser($vUser),
        ], 200);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $vUser = VUser::query()->where('id', $user->id)->first();

        if (!$vUser || empty($vUser->doctor_id)) {
            return response()->json([
                'status' => false,
                'message' => 'Doctor profile not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $this->userNormalizerService->normalizeUser($vUser),
        ], 200);
    }
}