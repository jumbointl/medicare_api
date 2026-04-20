<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppointmentModel;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\PatientModel;
use App\Models\ConfigurationsModel;
use DateTime;
use DateTimeZone;

class ZoomVideoCallController extends Controller
{
    public function createMeeting($appId, $date, $time)
    {
        $appointmentModel = AppointmentModel::where('id', $appId)->first();

        if ($appointmentModel == null) {
            return;
        }

        $doctId = $appointmentModel->doct_id;
        $patientId = $appointmentModel->patient_id;

        if (empty($doctId) || empty($patientId)) {
            return;
        }

        $doctFName = "";
        $doctLName = "";
        $patientFName = "";
        $patientLName = "";
        $patientPhone = "";

        $userData = User::where('id', $doctId)->first();
        if ($userData != null) {
            $doctFName = $userData->f_name ?? "";
            $doctLName = $userData->l_name ?? "";
        }

        $patintData = PatientModel::where('id', $patientId)->first();
        if ($patintData != null) {
            $patientFName = $patintData->f_name ?? "";
            $patientLName = $patintData->l_name ?? "";
            $patientPhone = $patintData->phone ?? "";
        }

        $meetingDescription = $patientFName . ' ' . $patientLName . ' ' . $patientPhone .
            ' Meeting With ' . $doctFName . ' ' . $doctLName . ' Appointment ID: ' . $appId;

        $clientIdRes = ConfigurationsModel::where('id_name', 'zoom_client_id')->first();
        $clientSecretRes = ConfigurationsModel::where('id_name', 'zoom_client_secret')->first();
        $accountIdRes = ConfigurationsModel::where('id_name', 'zoom_account_id')->first();

        if ($clientIdRes == null || $clientSecretRes == null || $accountIdRes == null) {
            return;
        }

        $clientId = $clientIdRes->value;
        $clientSecret = $clientSecretRes->value;
        $accountId = $accountIdRes->value;

        if (empty($clientId) || empty($clientSecret) || empty($accountId)) {
            return;
        }

        // Normalize date and time to avoid:
        // "2026-04-14 00:00:00 18:20"
        $dateOnly = date('Y-m-d', strtotime($date));
        $timeOnly = date('H:i:s', strtotime($time));

        $dateTime = new DateTime($dateOnly . ' ' . $timeOnly, new DateTimeZone('Asia/Kolkata'));
        $dateTime->setTimezone(new DateTimeZone('UTC'));
        $start_time = $dateTime->format('Y-m-d\TH:i:s\Z');

        $response = Http::withBasicAuth($clientId, $clientSecret)
            ->asForm()
            ->post('https://zoom.us/oauth/token', [
                'grant_type' => 'account_credentials',
                'account_id' => $accountId
            ]);

        if ($response->successful()) {
            $accessToken = $response->json()['access_token'];

            $meetingDetails = [
                "topic" => $meetingDescription,
                "type" => 2,
                "start_time" => $start_time,
                "duration" => 30,
                "password" => 123456,
                "timezone" => "Asia/Kolkata",
                "agenda" => "Appointment Meeting",
                "settings" => [
                    "host_video" => false,
                    "participant_video" => true,
                    "cn_meeting" => false,
                    "in_meeting" => false,
                    "join_before_host" => true,
                    "mute_upon_entry" => true,
                    "watermark" => false,
                    "use_pmi" => false,
                    "approval_type" => 1,
                    "registration_type" => 1,
                    "audio" => "voip",
                    "auto_recording" => "none",
                    "waiting_room" => false
                ]
            ];

            $meetingResponse = Http::withToken($accessToken)
                ->post('https://api.zoom.us/v2/users/me/meetings', $meetingDetails);

            if ($meetingResponse->successful()) {
                $responce = $meetingResponse->json();
                $meetingId = $responce['id'] ?? null;
                $joinUrl = $responce['join_url'] ?? null;

                if ($appointmentModel != null) {
                    $appointmentModel->meeting_id = $meetingId;
                    $appointmentModel->meeting_link = $joinUrl;
                    $appointmentModel->save();
                }
            }
        }
    }

    public function deleteMeeting($appId, $meetingId)
    {
        if ($meetingId == null || $appId == null) {
            return;
        }

        $clientIdRes = ConfigurationsModel::where('id_name', 'zoom_client_id')->first();
        $clientSecretRes = ConfigurationsModel::where('id_name', 'zoom_client_secret')->first();
        $accountIdRes = ConfigurationsModel::where('id_name', 'zoom_account_id')->first();

        if ($clientIdRes == null || $clientSecretRes == null || $accountIdRes == null) {
            return;
        }

        $clientId = $clientIdRes->value;
        $clientSecret = $clientSecretRes->value;
        $accountId = $accountIdRes->value;

        if (empty($clientId) || empty($clientSecret) || empty($accountId)) {
            return;
        }

        $response = Http::withBasicAuth($clientId, $clientSecret)
            ->asForm()
            ->post('https://zoom.us/oauth/token', [
                'grant_type' => 'account_credentials',
                'account_id' => $accountId
            ]);

        if ($response->successful()) {
            $accessToken = $response->json()['access_token'];
            $url = "https://api.zoom.us/v2/meetings/" . $meetingId;

            $meetingResponse = Http::withToken($accessToken)->delete($url);

            if ($meetingResponse->successful()) {
                $appointmentModel = AppointmentModel::where('id', $appId)->first();
                if ($appointmentModel != null) {
                    $appointmentModel->meeting_id = null;
                    $appointmentModel->meeting_link = null;
                    $appointmentModel->save();
                }
            }
        }
    }

    public function updateMeeting($appId, $meetingId, $date, $time)
    {
        if ($meetingId == null || $appId == null) {
            return;
        }

        $clientIdRes = ConfigurationsModel::where('id_name', 'zoom_client_id')->first();
        $clientSecretRes = ConfigurationsModel::where('id_name', 'zoom_client_secret')->first();
        $accountIdRes = ConfigurationsModel::where('id_name', 'zoom_account_id')->first();

        if ($clientIdRes == null || $clientSecretRes == null || $accountIdRes == null) {
            return;
        }

        $clientId = $clientIdRes->value;
        $clientSecret = $clientSecretRes->value;
        $accountId = $accountIdRes->value;

        if (empty($clientId) || empty($clientSecret) || empty($accountId)) {
            return;
        }

        $response = Http::withBasicAuth($clientId, $clientSecret)
            ->asForm()
            ->post('https://zoom.us/oauth/token', [
                'grant_type' => 'account_credentials',
                'account_id' => $accountId
            ]);

        if ($response->successful()) {
            $accessToken = $response->json()['access_token'];
            $url = "https://api.zoom.us/v2/meetings/" . $meetingId;

            $meetingResponse = Http::withToken($accessToken)->delete($url);

            if ($meetingResponse->successful()) {
                $this->createMeeting($appId, $date, $time);
            }
        }
    }
}