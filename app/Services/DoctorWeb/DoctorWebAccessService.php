<?php

namespace App\Services\DoctorWeb;

use App\Models\AppointmentModel;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DoctorWebAccessService
{
    public function getDoctorRowByUser(User $user): ?object
    {
        return DB::table('doctors')
            ->where('user_id', $user->id)
            ->first();
    }

    public function ensureDoctor(User $user): object
    {
        $doctor = $this->getDoctorRowByUser($user);

        if (!$doctor) {
            throw new \RuntimeException('Doctor profile not found.', 403);
        }

        return $doctor;
    }

    public function findOwnedAppointmentOrFail(User $user, int $appointmentId): AppointmentModel
    {
        $appointment = AppointmentModel::where('id', $appointmentId)
            ->where('doct_id', $user->id)
            ->first();

        if (!$appointment) {
            throw new \RuntimeException('Appointment not found or access denied.', 404);
        }

        return $appointment;
    }

    public function canAccessPatient(User $user, int $patientId): bool
    {
        return AppointmentModel::where('doct_id', $user->id)
            ->where('patient_id', $patientId)
            ->exists();
    }
}