<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VUser;
use App\Services\UserNormalizerService;
use Google\Client as GoogleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function __construct(
        private UserNormalizerService $userNormalizerService
    ) {
    }

    private function findVUserById(int $userId): ?VUser
    {
        return VUser::query()
            ->where('id', $userId)
            ->first();
    }

    private function findVUserByEmail(string $email): ?VUser
    {
        return VUser::query()
            ->whereRaw('LOWER(email) = ?', [strtolower(trim($email))])
            ->where('is_deleted', 0)
            ->first();
    }

    private function getAllowedGoogleClientIds(): array
    {
        return config('services.google.allowed_client_ids', []);
    }

    private function getUserRoles(int $userId): array
    {
        return DB::table('users_role_assign')
            ->join('roles', 'roles.id', '=', 'users_role_assign.role_id')
            ->where('users_role_assign.user_id', $userId)
            ->pluck('roles.name')
            ->toArray();
    }

    private function buildFallbackNormalizedUser(User $user): array
    {
        $linked = !empty($user->google_id);

        return [
            'id' => $user->id,
            'clinic_id' => $user->clinic_id ?? null,
            'pathologist_id' => $user->pathologist_id ?? null,
            'wallet_amount' => $user->wallet_amount ?? null,

            'f_name' => $user->f_name ?? null,
            'l_name' => $user->l_name ?? null,
            'phone' => $user->phone ?? null,
            'isd_code' => $user->isd_code ?? null,
            'gender' => $user->gender ?? null,
            'dob' => $user->dob ?? null,
            'email' => $user->email ?? null,

            'auth_provider' => $user->auth_provider ?? null,
            'avatar_url' => $user->avatar_url ?? null,
            'image' => $user->image ?? null,

            'address' => $user->address ?? null,
            'city' => $user->city ?? null,
            'state' => $user->state ?? null,
            'postal_code' => $user->postal_code ?? null,

            'isd_code_sec' => $user->isd_code_sec ?? null,
            'phone_sec' => $user->phone_sec ?? null,

            'email_verified_at' => $user->email_verified_at ?? null,
            'fcm' => $user->fcm ?? null,
            'web_fcm' => $user->web_fcm ?? null,
            'notification_seen_at' => $user->notification_seen_at ?? null,

            'created_at' => $user->created_at ?? null,
            'updated_at' => $user->updated_at ?? null,
            'is_deleted' => $user->is_deleted ?? null,
            'deleted_at' => $user->deleted_at ?? null,

            'doctor_id' => null,
            'doctor' => [],
            'roles' => [],
            'permissions' => [],
            'role' => (object) [
                'primary' => null,
                'all' => [],
            ],

            'clinic_title' => null,
            'owner_clinic' => null,
            'doctor_clinics' => [],
            'clinics' => [],

            'google_calendar' => [
                'linked' => $linked,
                'connected' => $linked,
                'status' => $linked ? 'valid' : 'not_linked',
                'label' => $linked ? 'Connected' : 'Not linked',
                'color' => $linked ? 'green' : 'gray',
                'action' => $linked ? 'connected' : 'connect',
                'expires_at' => null,
                'expires_in_days' => null,
            ],
        ];
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'response' => 422,
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $vUser = $this->findVUserByEmail($request->email);

        if (!$vUser || !Hash::check($request->password, (string) $vUser->password)) {
            return response()->json([
                'response' => 401,
                'status' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = User::find($vUser->id);

        if (!$user) {
            
            return response()->json([
                'response' => 404,
                'status' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
             'response' => 200,
            'status' => true,
            'message' => 'Login successful',
            'token' => $token,
            'data' => $this->userNormalizerService->normalizeUser($vUser),
        ], 200);
    }

    public function loginGoogleDoctor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'response' => 422,
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $vUser = $this->findVUserByEmail($request->email);

        if (!$vUser) {
            return response()->json([
                 'response' => 404,
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        $user = User::find($vUser->id);

        if (!$user) {
            return response()->json([
                'response' => 404,
                'status' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'response' => 200,
            'status' => true,
            'message' => 'Login successful',
            'token' => $token,
            'data' => $this->userNormalizerService->normalizeUser($vUser),
        ], 200);
    }
    public function loginMobile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'response' => 200,
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => null,
            ], 422);
        }

        $phone = trim((string) $request->phone);

        $user = User::query()
            ->where('phone', $phone)
            ->where('is_deleted', false)
            ->first();

        if (!$user) {
            return response()->json([
                'response' => 200,
                'status' => false,
                'message' => 'Not Exists',
                'data' => null,
            ], 200);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $vUser = $this->findVUserById($user->id);

        return response()->json([
            'response' => 200,
            'status' => true,
            'message' => 'Successfully',
            'token' => $token,
            'data' => $vUser
                ? $this->userNormalizerService->normalizeUser($vUser)
                : $this->buildFallbackNormalizedUser($user),
        ], 200);
    }
    public function reLoginMobile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'response' => 422,
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => null,
            ], 422);
        }

        $phone = trim((string) $request->phone);

        $user = User::query()
            ->where('phone', $phone)
            ->where('is_deleted', false)
            ->first();

        if (!$user) {
            return response()->json([
                'response' => 401,
                'status' => false,
                'message' => 'User does not exist',
                'data' => null,
            ], 401);
        }

        $vUser = $this->findVUserById($user->id);

        return response()->json([
            'response' => 200,
            'status' => true,
            'message' => 'Re Log with Mobile success',
            'data' => $vUser
                ? $this->userNormalizerService->normalizeUser($vUser)
                : $this->buildFallbackNormalizedUser($user),
        ], 200);
    }
    public function impersonateUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $vUser = $this->findVUserById((int) $request->user_id);

        if (!$vUser) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        $user = User::find($vUser->id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Impersonation successful',
            'token' => $token,
            'data' => $this->userNormalizerService->normalizeUser($vUser),
        ], 200);
    }

    public function loginGoogle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_token' => 'required|string',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            \Log::warning('loginGoogle validation failed', [
                'errors' => $validator->errors()->toArray(),
            ]);

            return response([
                'response' => 400,
                'status' => false,
                'message' => 'Invalid request',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            \Log::info('loginGoogle start', [
                'has_id_token' => $request->filled('id_token'),
                'has_email' => $request->filled('email'),
                'request_keys' => array_keys($request->all()),
            ]);

            DB::beginTransaction();

            $client = new GoogleClient();
            $payload = $client->verifyIdToken($request->id_token);

            if (!$payload) {
                \Log::warning('loginGoogle invalid token');

                return response([
                    'response' => 401,
                    'status' => false,
                    'message' => 'Invalid Google token',
                ], 401);
            }

            $allowedAudiences = $this->getAllowedGoogleClientIds();

            $aud = $payload['aud'] ?? null;
            $sub = $payload['sub'] ?? null;
            $email = isset($payload['email']) ? strtolower(trim($payload['email'])) : null;
            $requestedEmail = strtolower(trim($request->email));
            $emailVerified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $givenName = $payload['given_name'] ?? null;
            $familyName = $payload['family_name'] ?? null;
            $name = $payload['name'] ?? null;
            $picture = $payload['picture'] ?? null;

            if (!$sub || !$aud || !in_array($aud, $allowedAudiences, true)) {
                \Log::warning('loginGoogle audience rejected', [
                    'aud' => $aud,
                    'allowed' => $allowedAudiences,
                ]);

                DB::rollBack();

                return response([
                    'response' => 401,
                    'status' => false,
                    'message' => 'Token audience is not allowed',
                    'aud' => $aud,
                    'allowed' => $allowedAudiences,
                ], 402);
            }

            if (!$email || !$emailVerified) {
                \Log::warning('loginGoogle email not verified', [
                    'email' => $email,
                    'email_verified' => $emailVerified,
                ]);

                DB::rollBack();

                return response([
                    'response' => 401,
                    'status' => false,
                    'message' => 'Google email is not verified',
                ], 401);
            }

            if ($requestedEmail !== $email) {
                \Log::warning('loginGoogle email mismatch', [
                    'request_email' => $requestedEmail,
                    'token_email' => $email,
                ]);

                DB::rollBack();

                return response([
                    'response' => 401,
                    'status' => false,
                    'message' => 'Google email does not match token email',
                ], 401);
            }

            $matchedUsers = User::whereRaw('LOWER(email) = ?', [$email])
                ->where('is_deleted', false)
                ->get();

            \Log::info('loginGoogle users by email', [
                'email' => $email,
                'matched_count' => $matchedUsers->count(),
                'matched_ids' => $matchedUsers->pluck('id')->toArray(),
            ]);

            if ($matchedUsers->count() > 1) {
                DB::rollBack();

                return response([
                    'response' => 401,
                    'status' => false,
                    'message' => 'Multiple accounts found for this email. Please contact support.',
                ], 401);
            }

            if ($matchedUsers->count() === 1) {
                $user = $matchedUsers->first();

                if (!empty($user->google_id) && $user->google_id !== $sub) {
                    DB::rollBack();

                    return response([
                        'response' => 401,
                        'status' => false,
                        'message' => 'This account is linked to another Google account.',
                    ], 401);
                }
            } else {
                $user = new User();
                $user->email = $email;
                $user->google_id = $sub;
                $user->f_name = $givenName ?: ($name ?: 'Google');
                $user->l_name = $familyName ?: 'User';
                $user->auth_provider = 'google';

                if (\Schema::hasColumn('users', 'password')) {
                    $user->password = bcrypt(Str::random(32));
                }
            }

            $user->google_id = $sub;
            $user->auth_provider = 'google';

            if (empty($user->email)) {
                $user->email = $email;
            }

            if (empty($user->f_name)) {
                $user->f_name = $givenName ?: ($name ?: 'Google');
            }

            if (empty($user->l_name)) {
                $user->l_name = $familyName ?: 'User';
            }

            if (\Schema::hasColumn('users', 'avatar_url') && !empty($picture)) {
                $user->avatar_url = $picture;
            }

            if (\Schema::hasColumn('users', 'email_verified_at') && empty($user->email_verified_at)) {
                $user->email_verified_at = now();
            }

            $user->save();

            $roles = $this->getUserRoles($user->id);

            \Log::info('loginGoogle roles loaded', [
                'user_id' => $user->id,
                'roles' => $roles,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            $vUser = $this->findVUserById($user->id);

            $data = $vUser
                ? $this->userNormalizerService->normalizeUser($vUser)
                : $this->buildFallbackNormalizedUser($user);

            return response([
                'response' => 200,
                'status' => true,
                'message' => 'Successfully',
                'token' => $token,
                'data' => $data,
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('loginGoogle error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response([
                'response' => 500,
                'status' => false,
                'message' => 'Something went wrong Login Google',
                'error' => app()->environment('production') ? null : $e->getMessage(),
            ], 500);
        }
    }
}
