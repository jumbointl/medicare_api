<?php

use App\Models\AppointmentModel;
use App\Models\PatientModel;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Future private channel for appointment ownership validation.
 * Your current mobile app is still using public channel: appointment-video.{id}
 * so this is not strictly required today, but it is useful to keep ready.
 */
Broadcast::channel('appointment.{appointmentId}', function ($user, $appointmentId) {
    $appointment = AppointmentModel::find((int) $appointmentId);
    if (!$appointment) {
        return false;
    }

    $patient = PatientModel::find($appointment->patient_id);

    $isPatientOwner = (int) ($patient->user_id ?? 0) === (int) $user->id;
    $isDoctorOwner = (int) $appointment->doct_id === (int) $user->id;

    return $isPatientOwner || $isDoctorOwner;
});

/**
 * Current mobile/web listener still uses public channel:
 * appointment-video.{appointmentId}
 *
 * Public channels do not require authorization callback,
 * but leaving this note here for future migration.
 */