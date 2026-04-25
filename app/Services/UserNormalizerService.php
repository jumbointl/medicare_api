<?php

namespace App\Services;

use App\Models\VUser;
use App\Services\GoogleCalendarStatusService;
class UserNormalizerService
{
    public function __construct(
        private GoogleCalendarStatusService $googleCalendarStatusService
    ) {
    }

    public function normalizeUser(VUser $vUser): array
    {
        $rolesRaw = $this->decodeJsonArray($vUser->roles);
        $permissions = $this->decodeJsonArray($vUser->permissions);
        $doctorData = $this->decodeJsonObject($vUser->doctor);

        $clinicsRaw = $this->decodeJsonArray(
            $vUser->doctor_clinics ?? $vUser->clinics ?? '[]'
        );

        $doctorClinics = array_values(array_filter($clinicsRaw, function ($clinic) {
            if (!is_array($clinic)) {
                return false;
            }

            if (array_key_exists('active', $clinic) && (int) $clinic['active'] === 0) {
                return false;
            }

            if (array_key_exists('is_active', $clinic) && (int) $clinic['is_active'] === 0) {
                return false;
            }

            return true;
        }));

        $ownerClinic = null;

        if (!empty($doctorClinics)) {
            foreach ($doctorClinics as $clinic) {
                if ((int) ($clinic['is_default'] ?? 0) === 1) {
                    $ownerClinic = $clinic;
                    break;
                }
            }

            if ($ownerClinic === null) {
                $ownerClinic = $doctorClinics[0];
            }
        }

        $rolePayload = $this->buildRolePayload($rolesRaw);

        $roleNames = array_values(array_filter(
            $rolePayload->all,
            fn ($name) => trim((string) $name) !== ''
        ));

        $googleCalendar = $this->googleCalendarStatusService->buildStatus($vUser);

        return [
            'id' => $vUser->id,
            'clinic_id' => $vUser->clinic_id,
            'pathologist_id' => $vUser->pathologist_id,
            'wallet_amount' => $vUser->wallet_amount,

            'f_name' => $vUser->f_name,
            'l_name' => $vUser->l_name,
            'phone' => $vUser->phone,
            'isd_code' => $vUser->isd_code,
            'gender' => $vUser->gender,
            'dob' => $vUser->dob,
            'email' => $vUser->email,

            'auth_provider' => $vUser->auth_provider,
            'avatar_url' => $vUser->avatar_url,
            'image' => $vUser->image,

            'address' => $vUser->address,
            'city' => $vUser->city,
            'state' => $vUser->state,
            'postal_code' => $vUser->postal_code,

            'isd_code_sec' => $vUser->isd_code_sec,
            'phone_sec' => $vUser->phone_sec,

            'email_verified_at' => $vUser->email_verified_at,
            'fcm' => $vUser->fcm,
            'web_fcm' => $vUser->web_fcm,
            'notification_seen_at' => $vUser->notification_seen_at,

            'created_at' => $vUser->created_at,
            'updated_at' => $vUser->updated_at,
            'is_deleted' => $vUser->is_deleted,
            'deleted_at' => $vUser->deleted_at,

            'doctor_id' => $vUser->doctor_id,
            'doctor' => $doctorData,

            'roles' => $roleNames,
            'permissions' => $permissions,
            'role' => $rolePayload,

            'clinic_title' => $ownerClinic['clinic_title'] ?? $ownerClinic['title'] ?? null,
            'owner_clinic' => $ownerClinic,
            'doctor_clinics' => $doctorClinics,
            'clinics' => $doctorClinics,

            'google_calendar' => $googleCalendar,
        ];
    }

    public function normalizeDoctorUser(VUser $vUser): array
    {
        return $this->normalizeUser($vUser);
    }

    private function decodeJsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function decodeJsonObject(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function buildRolePayload(array $rolesRaw): object
    {
        $normalized = array_values(array_filter(array_map(function ($role) {
            if (is_array($role)) {
                return trim((string) (
                    $role['name']
                    ?? $role['title']
                    ?? $role['role']
                    ?? ''
                ));
            }

            return trim((string) $role);
        }, $rolesRaw), fn ($role) => $role !== ''));

        $primary = $normalized[0] ?? null;

        return (object) [
            'primary' => $primary,
            'all' => $normalized,
        ];
    }
}