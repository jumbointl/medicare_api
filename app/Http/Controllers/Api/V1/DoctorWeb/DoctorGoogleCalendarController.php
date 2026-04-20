<?php

namespace App\Http\Controllers\Api\V1\DoctorWeb;

use App\Http\Controllers\Controller;
use App\Models\User;
use Google\Client as GoogleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DoctorGoogleCalendarController extends Controller
{
    private function makeClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect_uri'));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setIncludeGrantedScopes(true);
        $client->setScopes([
            'openid',
            'email',
            'profile',
            'https://www.googleapis.com/auth/calendar.events',
        ]);

        return $client;
    }

    public function getConnectUrl(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $doctor = DB::table('doctors')
            ->where('user_id', $user->id)
            ->first();

        if (!$doctor) {
            return response()->json([
                'status' => false,
                'message' => 'Doctor not found',
            ], 404);
        }

        $client = $this->makeClient();

        $state = base64_encode(json_encode([
            'user_id' => $user->id,
            'ts' => time(),
        ]));

        $client->setState($state);

        return response()->json([
            'status' => true,
            'message' => 'OK',
            'data' => [
                'url' => $client->createAuthUrl(),
            ],
        ]);
    }

    public function callback(Request $request)
    {
        $code = $request->query('code');
        $state = $request->query('state');
        $error = $request->query('error');

        if (!empty($error)) {
            return $this->redirectToDoctorWeb('error');
        }

        if (empty($code) || empty($state)) {
            return $this->redirectToDoctorWeb('error');
        }

        $stateData = json_decode(base64_decode($state), true);
        $userId = (int) ($stateData['user_id'] ?? 0);

        if ($userId <= 0) {
            return $this->redirectToDoctorWeb('error');
        }

        $user = User::find($userId);

        if (!$user) {
            return $this->redirectToDoctorWeb('error');
        }

        $doctor = DB::table('doctors')
            ->where('user_id', $user->id)
            ->first();

        if (!$doctor) {
            return $this->redirectToDoctorWeb('error');
        }

        try {
            $client = $this->makeClient();
            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (!empty($token['error'])) {
                Log::warning('Google Calendar callback token error', [
                    'user_id' => $user->id,
                    'token' => $token,
                ]);

                return $this->redirectToDoctorWeb('error');
            }

            DB::beginTransaction();

            $user->google_access_token = $token['access_token'] ?? null;

            if (!empty($token['refresh_token'])) {
                $user->google_refresh_token = $token['refresh_token'];
            }

            $expiresIn = (int) ($token['expires_in'] ?? 3600);
            $user->google_token_expires_at = now()->addSeconds($expiresIn);
            $user->google_scopes = is_array($token['scope'] ?? null)
                ? implode(' ', $token['scope'])
                : ($token['scope'] ?? 'https://www.googleapis.com/auth/calendar.events');
            $user->save();

            DB::table('doctors')
                ->where('user_id', $user->id)
                ->update([
                    'video_provider' => 'google',
                    'updated_at' => now(),
                ]);

            DB::commit();

            return $this->redirectToDoctorWeb('connected');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Google Calendar callback failed', [
                'user_id' => $user->id ?? null,
                'message' => $e->getMessage(),
            ]);

            return $this->redirectToDoctorWeb('error');
        }
    }

    public function status(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $doctor = DB::table('doctors')
            ->select('video_provider')
            ->where('user_id', $user->id)
            ->first();

        $connected = !empty($user->google_refresh_token) || !empty($user->google_access_token);

        return response()->json([
            'status' => true,
            'message' => 'OK',
            'data' => [
                'google_calendar_connected' => $connected,
                'video_provider' => strtolower(trim($doctor->video_provider ?? 'agora')),
            ],
        ]);
    }

    public function disconnect(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $doctor = DB::table('doctors')
            ->where('user_id', $user->id)
            ->first();

        if (!$doctor) {
            return response()->json([
                'status' => false,
                'message' => 'Doctor not found',
            ], 404);
        }

        DB::beginTransaction();

        try {
            $user->google_access_token = null;
            $user->google_refresh_token = null;
            $user->google_token_expires_at = null;
            $user->google_scopes = null;
            $user->save();

            DB::table('doctors')
                ->where('user_id', $user->id)
                ->update([
                    'video_provider' => 'agora',
                    'updated_at' => now(),
                ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Google Calendar disconnected successfully',
                'data' => [
                    'video_provider' => 'agora',
                    'google_calendar_connected' => false,
                ],
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Google Calendar disconnect failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Could not disconnect Google Calendar',
            ], 500);
        }
    }

    private function redirectToDoctorWeb(string $status)
    {
        $baseUrl = rtrim(config('app.doctor_web_url'), '/');

        return redirect()->away($baseUrl . '/dashboard?google_calendar=' . $status);
    }
}