<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Google\Client as GoogleClient;

class LoginController extends Controller
{
    private function getAllowedGoogleClientIds(): array
    {
        return config('services.google.allowed_client_ids', []);
    }
    public function loginGoogleDoctor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_token' => 'required|string',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            \Log::warning('loginGoogleDoctor validation failed', [
                'errors' => $validator->errors()->toArray(),
            ]);

            return response([
                "response" => 400,
                "status" => false,
                "message" => "Invalid request",
                "errors" => $validator->errors(),
            ], 400);
        }

        try {
            \Log::info('loginGoogleDoctor start', [
                'has_id_token' => $request->filled('id_token'),
                'has_email' => $request->filled('email'),
                'request_keys' => array_keys($request->all()),
            ]);

            DB::beginTransaction();

            $client = new GoogleClient();
            $payload = $client->verifyIdToken($request->id_token);

            if (!$payload) {
                \Log::warning('loginGoogleDoctor invalid token');

                return response([
                    "response" => 200,
                    "status" => false,
                    "message" => "Invalid Google token",
                ], 200);
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
                \Log::warning('loginGoogleDoctor audience rejected', [
                    'aud' => $aud,
                    'allowed' => $allowedAudiences,
                ]);

                return response([
                    "response" => 200,
                    "status" => false,
                    "message" => "Token audience is not allowed",
                    "aud" => $aud,
                    "allowed" => $allowedAudiences,
                ], 200);
            }

            if (!$email || !$emailVerified) {
                \Log::warning('loginGoogleDoctor email not verified', [
                    'email' => $email,
                    'email_verified' => $emailVerified,
                ]);

                return response([
                    "response" => 200,
                    "status" => false,
                    "message" => "Google email is not verified",
                ], 200);
            }

            if ($requestedEmail !== $email) {
                \Log::warning('loginGoogleDoctor email mismatch', [
                    'request_email' => $requestedEmail,
                    'token_email' => $email,
                ]);

                return response([
                    "response" => 200,
                    "status" => false,
                    "message" => "Google email does not match token email",
                ], 200);
            }

            $matchedUsers = User::whereRaw('LOWER(email) = ?', [$email])
                ->where('is_deleted', false)
                ->get();

            \Log::info('loginGoogleDoctor users by email', [
                'email' => $email,
                'matched_count' => $matchedUsers->count(),
                'matched_ids' => $matchedUsers->pluck('id')->toArray(),
            ]);

            if ($matchedUsers->count() > 1) {
                DB::rollBack();

                return response([
                    "response" => 200,
                    "status" => false,
                    "message" => "Multiple accounts found for this email. Please contact support.",
                ], 200);
            }

            if ($matchedUsers->count() === 0) {
                DB::rollBack();

                return response([
                    "response" => 200,
                    "status" => false,
                    "message" => "This email is not registered as doctor.",
                ], 200);
            }

            $user = $matchedUsers->first();

            $roles = DB::table("users_role_assign")
                ->select('users_role_assign.*', 'roles.name as name')
                ->join('roles', 'roles.id', '=', 'users_role_assign.role_id')
                ->where('users_role_assign.user_id', '=', $user->id)
                ->get();

            $hasDoctorRole = $roles->contains(function ($role) {
                return strtolower(trim($role->name ?? '')) === 'doctor';
            });

            if (!$hasDoctorRole) {
                DB::rollBack();

                return response([
                    "response" => 200,
                    "status" => false,
                    "message" => "This account is not a doctor account.",
                ], 200);
            }

            if (!empty($user->google_id) && $user->google_id !== $sub) {
                DB::rollBack();

                return response([
                    "response" => 200,
                    "status" => false,
                    "message" => "This doctor account is linked to another Google account.",
                ], 200);
            }

            $user->google_id = $sub;
            $user->auth_provider = 'google';

            if (!empty($givenName)) {
                $user->f_name = $givenName;
            } elseif (!empty($name) && empty($user->f_name)) {
                $user->f_name = $name;
            }

            if (!empty($familyName)) {
                $user->l_name = $familyName;
            }

            if (!empty($picture)) {
                $user->image = $picture;
                if (\Schema::hasColumn('users', 'avatar_url')) {
                    $user->avatar_url = $picture;
                }
            }

            if (empty($user->email)) {
                $user->email = $email;
            }

            $user->save();

            $token = $user->createToken('my-app-token')->plainTextToken;

            $user->role = $roles;

            $clinic = DB::table("clinics")
                ->select('clinics.id', 'clinics.title')
                ->where('clinics.user_id', '=', $user->id)
                ->first();

            if ($clinic) {
                $user->clinic_id = $clinic->id;
                $user->clinic_title = $clinic->title;
            }

            $doctor = DB::table("doctors")
                ->select('doctors.clinic_id')
                ->where('doctors.user_id', '=', $user->id)
                ->first();

            if ($doctor) {
                $user->assign_clinic_id = $doctor->clinic_id;
            }

            DB::commit();

            return response([
                "response" => 200,
                "status" => true,
                "message" => "Successfully",
                "data" => $user,
                "token" => $token,
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('loginGoogleDoctor exception', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response([
                "response" => 500,
                "status" => false,
                "message" => "Login with Google failed",
                "error" => $e->getMessage(),
            ], 200);
        }
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
                "response" => 400,
                "status" => false,
                "message" => "Invalid request",
                "errors" => $validator->errors(),
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
                    "response" => 200,
                    "status" => false,
                    "message" => "Invalid Google token",
                ], 200);
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

                return response([
                    "response" => 200,
                    "status" => false,
                    "message" => "Token audience is not allowed",
                    "aud" => $aud,
                    "allowed" => $allowedAudiences,
                ], 200);
            }

            if (!$email || !$emailVerified) {
                \Log::warning('loginGoogle email not verified', [
                    'email' => $email,
                    'email_verified' => $emailVerified,
                ]);

                return response([
                    "response" => 200,
                    "status" => false,
                    "message" => "Google email is not verified",
                ], 200);
            }

            if ($requestedEmail !== $email) {
                \Log::warning('loginGoogle email mismatch', [
                    'request_email' => $requestedEmail,
                    'token_email' => $email,
                ]);

                return response([
                    "response" => 200,
                    "status" => false,
                    "message" => "Google email does not match token email",
                ], 200);
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
                    "response" => 200,
                    "status" => false,
                    "message" => "Multiple accounts found for this email. Please contact support.",
                ], 200);
            }

            if ($matchedUsers->count() === 1) {
                $user = $matchedUsers->first();

                if (!empty($user->google_id) && $user->google_id !== $sub) {
                    DB::rollBack();

                    return response([
                        "response" => 200,
                        "status" => false,
                        "message" => "This account is linked to another Google account.",
                    ], 200);
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

            if (!empty($givenName)) {
                $user->f_name = $givenName;
            } elseif (!empty($name) && empty($user->f_name)) {
                $user->f_name = $name;
            }

            if (!empty($familyName)) {
                $user->l_name = $familyName;
            }

            if (!empty($picture)) {
                $user->image = $picture;
                if (\Schema::hasColumn('users', 'avatar_url')) {
                    $user->avatar_url = $picture;
                }
            }

            $user->save();

            $token = $user->createToken('my-app-token')->plainTextToken;

            DB::commit();

            return response([
                "response" => 200,
                "status" => true,
                "message" => "Successfully",
                "data" => $user,
                "token" => $token,
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('loginGoogle exception', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response([
                "response" => 500,
                "status" => false,
                "message" => "Login with Google failed",
                "error" => $e->getMessage(),
            ], 200);
        }
    }
        function checkUserRegMobile(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'phone' => 'required'
        ]);

        if ($validator->fails())
            return response(["response" => 400], 400);

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response([
                "response" => 201,
                "status" => false,
                'message' => 'These credentials do not match our records.'
            ], 200);
        } else {
            return response([
                "response" => 201,
                "status" => true,
                'message' => 'User exists'
            ], 200);
        }
    }
    function loginMobile(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'phone' => 'required'
        ]);

        if ($validator->fails())
            return response(["response" => 400], 400);

        $user = User::where('phone', $request->phone)->where('is_deleted', '=', false)->first();

        if (!$user) {
            return response([
                "response" => 200,
                "status" => false,
                'message' => "Not Exists",
                'data' => null,

            ], 200);
        }
        // $user->tokens()->delete();
        $token = $user->createToken('my-app-token')->plainTextToken;


        $response = [
            "response" => 200,
            "status" => true,
            'message' => "Successfully",
            'data' => $user,
            'token' => $token,
        ];

        return response($response, 200);
    }
    function ReLoginMobile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
        ]);

        if ($validator->fails()) {
            return response(["response" => 400], 400);
        }

        $user = User::where('phone', $request->phone)->where('is_deleted', '=', false)->first();

        if (!$user) {
            return response([
                "response" => 200,
                "status" => false,
                'message' => "User does not exist",
                'data' => null,
            ], 200);
        }

        return response([
            "response" => 200,
            "status" => true,
            'message' => "Successfully retrieved user data",
            'data' => $user,
        ], 200);
    }



    function login(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response([
                "response" => 201,
                "status" => false,
                'message' => 'These credentials do not match our records.'
            ], 200);
        }
        // $user->tokens()->delete();
        $token = $user->createToken('my-app-token')->plainTextToken;


        $user->role = DB::table("users_role_assign")
            ->select(
                'users_role_assign.*',
                'roles.name as name',
            )
            ->Join('roles', 'roles.id', '=', 'users_role_assign.role_id')
            ->where('users_role_assign.user_id', '=', $user->id)
            ->get();


        $clinic = DB::table("clinics")
            ->select(
                'clinics.id',
                'clinics.title'
            )
            ->where('clinics.user_id', '=', $user->id)
            ->first();

        if ($clinic) {

            $user->clinic_id =   $clinic->id;
            $user->clinic_title =   $clinic->title;
        }


        $doctors = DB::table("doctors")
            ->select(
                'doctors.clinic_id',
            )
            ->where('doctors.user_id', '=', $user->id)
            ->first();
        if ($doctors) {

            $user->assign_clinic_id =   $doctors->clinic_id;
        }

        $response = [
            "response" => 200,
            "status" => true,
            'message' => "Successfully",
            'data' => $user,
            //  'roles'=> $roles, 
            'token' => $token,
        ];

        return response($response, 200);
    }


    public function logout(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response([
                "response" => 401,
                "status" => false,
                "message" => "Unauthenticated."
            ], 401);
        }

        $user->update([
            'fcm' => null,
            'web_fcm' => null,
        ]);

        if ($user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response([
            "response" => 200,
            "status" => true,
            "message" => "Logged out successfully."
        ], 200);
    }

    // imporsnate user
    public function impersonateUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',    // user to impersonate
        ]);

        if ($validator->fails()) {
            return response(["response" => 400, "message" => "Invalid request"], 400);
        }

        /** AUTH USER FROM BEARER TOKEN */
        $admin = $request->user();  // no need to send admin_id
        $targetUser = User::where('id', $request->user_id)->where('is_deleted', false)->first();

        if (!$admin || !$targetUser) {
            return response([
                "response" => 404,
                "status" => false,
                "message" => "User not found"
            ], 200);
        }

        /** CHECK IF ADMIN IS SUPER ADMIN */
        $isSuperAdmin = DB::table("users_role_assign")
            ->join('roles', 'roles.id', '=', 'users_role_assign.role_id')
            ->where('users_role_assign.user_id', $admin->id)
            ->where('roles.name', 'Super Admin')
            ->exists();

        if (!$isSuperAdmin) {
            return response([
                "response" => 403,
                "status" => false,
                "message" => "Only Super Admin can impersonate users"
            ], 200);
        }

        /** CREATE TOKEN FOR IMPERSONATED USER */
        $token = $targetUser->createToken('impersonate-token')->plainTextToken;

        // Attach role info
        $targetUser->role = DB::table("users_role_assign")
            ->select('users_role_assign.*', 'roles.name as name')
            ->join('roles', 'roles.id', '=', 'users_role_assign.role_id')
            ->where('users_role_assign.user_id', $targetUser->id)
            ->first();


        // Clinic details if exist
        if ($clinic = DB::table("clinics")->select('id', 'title')->where('user_id', $targetUser->id)->first()) {
            $targetUser->clinic_id = $clinic->id;
            $targetUser->clinic_title = $clinic->title;
        }

        // Assigned doctor clinic
        if ($doctor = DB::table("doctors")->select('clinic_id')->where('user_id', $targetUser->id)->first()) {
            $targetUser->assign_clinic_id = $doctor->clinic_id;
        }

        return response([
            "response" => 200,
            "status" => true,
            "message" => "Impersonation Successful",
            "impersonating_as" => $targetUser->name,
            "data" => $targetUser,
            "token" => $token,
        ], 200);
    }
}
