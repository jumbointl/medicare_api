<?php

namespace App\Services;

use App\Models\VUser;
use Carbon\Carbon;

class GoogleCalendarStatusService
{
    public function buildStatus(VUser $vUser): array
    {
        $googleId = trim((string) ($vUser->google_id ?? ''));
        $expiresAtRaw = $vUser->google_token_expires_at;

        $hasGoogleId = $googleId !== '';
        $hasRefreshToken = !empty($vUser->google_refresh_token);
        $hasAccessToken = !empty($vUser->google_access_token);

        $linked = $hasGoogleId || $hasRefreshToken || $hasAccessToken;

        $expiresAt = null;
        $expiresInSeconds = null;
        $expiresInDays = null;
        $isExpired = null;

        if (!empty($expiresAtRaw)) {
            try {
                $expiresAt = Carbon::parse($expiresAtRaw);
                $now = now();

                $expiresInSeconds = $now->diffInSeconds($expiresAt, false);
                $expiresInDays = (int) floor($expiresInSeconds / 86400);
                $isExpired = $expiresInSeconds <= 0;
            } catch (\Throwable $e) {
                $expiresAt = null;
                $expiresInSeconds = null;
                $expiresInDays = null;
                $isExpired = null;
            }
        }

        $status = 'not_linked';
        $label = 'Not linked';
        $color = 'gray';
        $connected = false;
        $action = 'connect';

        if ($linked) {
            if (!$hasRefreshToken) {
                $status = 'error';
                $label = 'Reconnect required';
                $color = 'red';
                $connected = false;
                $action = 'reconnect';
            } elseif ($expiresAt === null) {
                $status = 'warning';
                $label = 'Expiry unknown';
                $color = 'yellow';
                $connected = true;
                $action = 'reconnect';
            } elseif ($isExpired === true) {
                $status = 'expired';
                $label = 'Expired';
                $color = 'red';
                $connected = false;
                $action = 'reconnect';
            } elseif ($expiresInSeconds <= 86400) {
                $status = 'warning';
                $label = 'Expiring soon';
                $color = 'yellow';
                $connected = true;
                $action = 'reconnect';
            } else {
                $status = 'valid';
                $label = 'Connected';
                $color = 'green';
                $connected = true;
                $action = 'connected';
            }
        }

        return [
            'linked' => $linked,
            'connected' => $connected,
            'status' => $status,
            'label' => $label,
            'color' => $color,
            'action' => $action,
            'expires_at' => $expiresAt?->toDateTimeString(),
            'expires_in_days' => $expiresInDays,
        ];
    }
}