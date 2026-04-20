<?php

namespace App\Services;

use App\Models\AppointmentModel;
use App\Models\PatientModel;
use Illuminate\Support\Carbon;

require_once app_path('Support/Agora2/RtcTokenBuilder2.php');

class AgoraJoinDataService
{

    
    /**
     * Build Agora join data for an appointment and user.
     *
     * @throws \Exception
     */
    public function buildJoinData(AppointmentModel $appointment, int $userId): array
    {
        $isDoctor = ((int) $appointment->doct_id === $userId);

        $patient = PatientModel::where('id', $appointment->patient_id)->first();
        $isPatient = ($patient && (int) ($patient->user_id ?? 0) === $userId);

        if (!$isDoctor && !$isPatient) {
            throw new \Exception('No autorizado para esta videollamada.', 403);
        }

        if ($appointment->type !== 'Video Consultant') {
            throw new \Exception('Esta cita no es de videollamada.', 400);
        }

        if (($appointment->payment_status ?? '') !== 'Paid') {
            throw new \Exception('Debe pagar primero para participar en la consulta por video.', 400);
        }

        $appointmentDate = date('Y-m-d', strtotime((string) $appointment->date));
        $appointmentTime = date('H:i:s', strtotime((string) $appointment->time_slots));
        $appointmentDateTime = strtotime($appointmentDate . ' ' . $appointmentTime);

        if (!$appointmentDateTime) {
            throw new \Exception('No se pudo interpretar fecha/hora de la cita.', 500);
        }

        $lateToleranceMinutes = (int) config('agora.late_tolerance_minutes', 20);
        $durationMinutes = (int) ($appointment->duration_minutes ?? 15);

        $joinOpensAt = $appointmentDateTime - (5 * 60);
        $joinClosesAt = $appointmentDateTime + (($durationMinutes + $lateToleranceMinutes) * 60);
        $now = time();

        if ($now < $joinOpensAt) {
            return [
                'response' => 400,
                'status' => false,
                'message' => 'La videollamada estará disponible 5 minutos antes de la cita.',
                'data' => [
                    'provider' => 'agora',
                    'can_join_video' => false,
                    'join_opens_at' => $joinOpensAt,
                    'join_closes_at' => $joinClosesAt,
                    'appointment_at' => $appointmentDateTime,
                    'seconds_remaining' => $joinOpensAt - $now,
                    'is_doctor' => $isDoctor,
                    'is_patient' => $isPatient,
                ],
                'http_code' => 400,
            ];
        }

        if ($now > $joinClosesAt) {
            return [
                'response' => 400,
                'status' => false,
                'message' => 'La ventana para ingresar a la videollamada ya finalizó.',
                'data' => [
                    'provider' => 'agora',
                    'can_join_video' => false,
                    'join_opens_at' => $joinOpensAt,
                    'join_closes_at' => $joinClosesAt,
                    'appointment_at' => $appointmentDateTime,
                    'seconds_remaining' => 0,
                    'is_doctor' => $isDoctor,
                    'is_patient' => $isPatient,
                ],
                'http_code' => 400,
            ];
        }

        $appId = (string) config('agora.app_id');
        $appCertificate = (string) config('agora.app_certificate');
        $expireSeconds = (int) config('agora.token_expire_seconds', 3600);

        if (empty($appId) || empty($appCertificate)) {
            throw new \Exception('Agora no está configurado correctamente.', 500);
        }

        if (empty($appointment->video_channel_name)) {
            $appointment->video_provider = 'agora';
            $appointment->video_channel_name = 'appointment_' . $appointment->id;
            $appointment->meeting_id = $appointment->video_channel_name;
            $appointment->meeting_link = null;
            $appointment->video_join_open_at = date('Y-m-d H:i:s', $joinOpensAt);
            $appointment->video_join_close_at = date('Y-m-d H:i:s', $joinClosesAt);
        }

        $channelName = (string) $appointment->video_channel_name;
        $uid = (int) $userId;
        $expireTimestamp = time() + $expireSeconds;

        $token = \RtcTokenBuilder2::buildTokenWithUid(
            $appId,
            $appCertificate,
            $channelName,
            $uid,
            \RtcTokenBuilder2::ROLE_PUBLISHER,
            $expireSeconds,
            $expireSeconds
        );

        \Log::info('AGORA_ENV_CHECK', [
            'app_id_len' => strlen($appId),
            'app_certificate_len' => strlen($appCertificate),
        ]);

        \Log::info('AGORA_JOIN_DATA', [
            'appointment_id' => (int) $appointment->id,
            'user_id' => $userId,
            'channel_name' => $channelName,
            'uid' => $uid,
            'expires_at' => $expireTimestamp,
        ]);

        $appointment->video_provider = 'agora';
        $appointment->video_last_token_expires_at = date('Y-m-d H:i:s', $expireTimestamp);

        if ($appointment->video_session_started_at == null) {
            $appointment->video_session_started_at = Carbon::now();
        }

        $appointment->save();

        return [
            'response' => 200,
            'status' => true,
            'message' => 'Join data generado correctamente.',
            'data' => [
                'provider' => 'agora',
                'appId' => $appId,
                'channel_name' => $channelName,
                'channelName' => $channelName,
                'meeting_id' => $appointment->meeting_id,
                'meeting_link' => null,
                'uid' => $uid,
                'token' => $token,
                'role' => 'broadcaster',
                'appointment_id' => (int) $appointment->id,
                'expires_at' => $expireTimestamp,
                'is_doctor' => $isDoctor,
                'is_patient' => $isPatient,
                'can_join_video' => true,
                'join_opens_at' => $joinOpensAt,
                'join_closes_at' => $joinClosesAt,
                'appointment_at' => $appointmentDateTime,
                'seconds_remaining' => 0,
            ],
            'http_code' => 200,
        ];
    }
}