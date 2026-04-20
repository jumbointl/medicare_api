<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleMeetService
{
    private function createGoogleMeetForAppointment($appointment, $user): array
    {
        $doctorUser = User::find($appointment->doct_id);

        if (!$doctorUser) {
            throw new \RuntimeException('Doctor user not found.');
        }

        $accessToken = $doctorUser->google_access_token;
        $refreshToken = $doctorUser->google_refresh_token;

        if (empty($accessToken) && empty($refreshToken)) {
            throw new \RuntimeException('Doctor has no Google Calendar credentials.');
        }

        if (
            !empty($doctorUser->google_token_expires_at) &&
            now()->greaterThanOrEqualTo(\Carbon\Carbon::parse($doctorUser->google_token_expires_at)->subMinutes(2))
        ) {
            $tokenData = $this->refreshGoogleAccessToken($refreshToken);

            $doctorUser->google_access_token = $tokenData['access_token'];
            $doctorUser->google_token_expires_at = now()->addSeconds((int) ($tokenData['expires_in'] ?? 3600));
            $doctorUser->save();

            $accessToken = $doctorUser->google_access_token;
        }

        $timeZone = config('app.timezone', 'America/Asuncion');

        $start = new \DateTime(
            trim($appointment->date . ' ' . $appointment->time_slots),
            new \DateTimeZone($timeZone)
        );

        $durationMinutes = (int) ($appointment->duration_minutes ?? 15);
        if ($durationMinutes <= 0) {
            $durationMinutes = 15;
        }

        $end = (clone $start)->modify('+' . $durationMinutes . ' minutes');

        $payload = [
            'summary' => 'Medical video appointment #' . $appointment->id,
            'description' => 'Appointment #' . $appointment->id,
            'start' => [
                'dateTime' => $start->format(DATE_RFC3339),
                'timeZone' => $timeZone,
            ],
            'end' => [
                'dateTime' => $end->format(DATE_RFC3339),
                'timeZone' => $timeZone,
            ],
            'conferenceData' => [
                'createRequest' => [
                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet',
                    ],
                    'requestId' => (string) Str::uuid(),
                ],
            ],
        ];

        $response = Http::withToken($accessToken)->post(
            'https://www.googleapis.com/calendar/v3/calendars/primary/events?conferenceDataVersion=1&sendUpdates=none',
            $payload
        );

        if (!$response->successful()) {
            throw new \RuntimeException('Google Calendar event creation failed: ' . $response->body());
        }

        $event = $response->json();

        $entryPoints = $event['conferenceData']['entryPoints'] ?? [];
        $videoEntry = collect($entryPoints)->firstWhere('entryPointType', 'video');
        $meetingLink = $videoEntry['uri'] ?? null;
        $meetingId = $event['conferenceData']['conferenceId'] ?? ($event['id'] ?? null);

        if (!$meetingLink) {
            throw new \RuntimeException('Google Meet link not returned by Calendar API.');
        }

        return [
            'meeting_id' => $meetingId,
            'meeting_link' => $meetingLink,
            'google_calendar_event_id' => $event['id'] ?? null,
        ];
    }
    private function isExpired(User $user): bool
    {
        $expiresAt = $user->google_token_expires_at ?? null;

        if (!$expiresAt) {
            return false;
        }

        return now()->greaterThanOrEqualTo(\Carbon\Carbon::parse($expiresAt)->subMinutes(2));
    }

    private function refreshGoogleAccessToken(string $refreshToken): array
    {
        if (empty($refreshToken)) {
            throw new \RuntimeException('Missing Google refresh token.');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Could not refresh Google token: ' . $response->body());
        }

        $data = $response->json();

        if (empty($data['access_token'])) {
            throw new \RuntimeException('Google token refresh did not return access_token.');
        }

        return $data;
    }
}