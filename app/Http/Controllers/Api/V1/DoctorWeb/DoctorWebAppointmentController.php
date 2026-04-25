<?php

namespace App\Http\Controllers\Api\V1\DoctorWeb;

use App\Events\DoctorJoinedVideoAppointment;
use App\Http\Controllers\Controller;
use App\Models\AppointmentModel;
use App\Models\User;
use App\Services\AgoraJoinDataService;
use App\Services\DoctorWeb\DoctorWebAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DoctorWebAppointmentController extends Controller
{
    public function __construct(
        private DoctorWebAccessService $accessService,
        private AgoraJoinDataService $agoraJoinDataService
    ) {}

    public function index(Request $request)
    {
        try {
            $doctorId = $this->currentDoctorId($request);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 404);
        }

        $perPage = (int) $request->get('per_page', 10);
        $search = trim((string) $request->get('search', ''));
        $status = trim((string) $request->get('status', ''));
        $type = trim((string) $request->get('type', ''));
        $start = trim((string) $request->get('start', ''));
        $end = trim((string) $request->get('end', ''));
        $clinicId = trim((string) $request->get('clinic_id', ''));

        $query = \App\Models\AppointmentModel::query()
            ->select([
                'appointments.id',
                'appointments.patient_id',
                'appointments.doct_id',
                'appointments.clinic_id',
                'appointments.type',
                'appointments.status',
                'appointments.payment_status',
                'appointments.video_provider',
                'appointments.date',
                'appointments.time_slots',
                'appointments.created_at',
                \DB::raw("CONCAT(COALESCE(patients.f_name, ''), ' ', COALESCE(patients.l_name, '')) as patient_name"),
                'clinics.title as clinic_name',
            ])
            ->leftJoin('patients', 'patients.id', '=', 'appointments.patient_id')
            ->leftJoin('clinics', 'clinics.id', '=', 'appointments.clinic_id')
            ->where('appointments.doct_id', $doctorId)
            ->orderByDesc('appointments.date')
            ->orderByDesc('appointments.time_slots');

        if ($clinicId !== '' && $clinicId !== 'all') {
            $query->where('appointments.clinic_id', $clinicId);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('appointments.id', 'like', "%{$search}%")
                    ->orWhere(\DB::raw("CONCAT(COALESCE(patients.f_name, ''), ' ', COALESCE(patients.l_name, ''))"), 'like', "%{$search}%")
                    ->orWhere('patients.f_name', 'like', "%{$search}%")
                    ->orWhere('patients.l_name', 'like', "%{$search}%")
                    ->orWhere('appointments.type', 'like', "%{$search}%")
                    ->orWhere('appointments.status', 'like', "%{$search}%");
            });
        }

        if ($status !== '' && strtoupper($status) !== 'ALL') {
            $query->where('appointments.status', $status);
        }

        if ($type !== '' && strtoupper($type) !== 'ALL') {
            $query->where('appointments.type', $type);
        }

        if ($start !== '' && $end !== '') {
            $query->whereBetween('appointments.date', [$start, $end]);
        } elseif ($start !== '') {
            $query->whereDate('appointments.date', '>=', $start);
        } elseif ($end !== '') {
            $query->whereDate('appointments.date', '<=', $end);
        }

        return response()->json([
            'status' => true,
            'data' => $query->paginate($perPage),
        ], 200);
    }

    public function show(Request $request, $id)
    {
        try {
            $doctorId = $this->currentDoctorId($request);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 404);
        }

        $appointment = \App\Models\AppointmentModel::query()
            ->select([
                'appointments.*',
                \DB::raw("CONCAT(COALESCE(patients.f_name, ''), ' ', COALESCE(patients.l_name, '')) as patient_name"),
                'patients.phone as patient_phone',
                'patients.email as patient_email',
                'clinics.title as clinic_name',
                'clinics.address as clinic_address',
                'vd.doctor_name',
                'vd.specialization as doctor_specialization',
                'vd.email as doctor_email',
                'vd.image as doctor_image',
            ])
            ->leftJoin('patients', 'patients.id', '=', 'appointments.patient_id')
            ->leftJoin('clinics', 'clinics.id', '=', 'appointments.clinic_id')
            ->leftJoin('v_doctors as vd', 'vd.doctor_id', '=', 'appointments.doct_id')
            ->where('appointments.id', $id)
            ->where('appointments.doct_id', $doctorId)
            ->first();

        if (!$appointment) {
            return response()->json([
                'status' => false,
                'message' => 'Appointment not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $appointment,
        ], 200);
    }

    public function confirm(Request $request, $id)
    {
        return $this->updateStatus($request, (int) $id, 'Confirmed');
    }

    public function cancel(Request $request, $id)
    {
        return $this->updateStatus($request, (int) $id, 'Cancelled');
    }

    public function complete(Request $request, $id)
    {
        return $this->updateStatus($request, (int) $id, 'Completed');
    }

    private function updateStatus(Request $request, int $appointmentId, string $status)
    {
        try {
            $appointment = $this->accessService->findOwnedAppointmentOrFail($request->user(), $appointmentId);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 404);
        }

        $appointment->status = $status;
        $appointment->save();

        return response()->json([
            'status' => true,
            'message' => 'Appointment updated successfully.',
            'data' => $appointment,
        ], 200);
    }

    public function reschedule(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'time_slots' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid request',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $appointment = $this->accessService->findOwnedAppointmentOrFail($request->user(), (int) $id);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 404);
        }

        $appointment->date = $request->date;
        $appointment->time_slots = $request->time_slots;
        $appointment->status = 'Rescheduled';
        $appointment->save();

        return response()->json([
            'status' => true,
            'message' => 'Appointment rescheduled successfully.',
            'data' => $appointment,
        ], 200);
    }

    public function videoJoinData(Request $request, $id)
    {
        try {
            $appointment = $this->accessService->findOwnedAppointmentOrFail($request->user(), (int) $id);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 404);
        }

        try {
            $user = $request->user();

            if (($appointment->type ?? '') !== 'Video Consultant') {
                return response()->json([
                    'status' => false,
                    'message' => 'This appointment is not a video consultation.',
                    'message_key' => 'video_join_not_video_consultation',
                ], 400);
            }

            if (($appointment->payment_status ?? 'Unpaid') !== 'Paid') {
                return response()->json([
                    'status' => false,
                    'message' => 'Appointment must be paid before joining video.',
                    'message_key' => 'video_join_payment_required',
                ], 400);
            }

            if (in_array($appointment->status, ['Rejected', 'Cancelled', 'Completed', 'Visited'], true)) {
                return response()->json([
                    'status' => false,
                    'message' => 'This appointment is not available for video call.',
                    'message_key' => 'video_join_not_available',
                ], 400);
            }

            $provider = strtolower(trim($appointment->video_provider ?? 'agora'));
            $isGooglePreferred = in_array($provider, ['google', 'google_meet'], true);
            $fallbackUsed = false;

            DB::beginTransaction();

            if ($isGooglePreferred) {
                try {
                    $meet = $this->createGoogleMeetForAppointment($appointment);
                    Log::info('GOOGLE_MEET_CREATED', [
                        'appointment_id' => $appointment->id,
                        'meeting_id' => $meet['meeting_id'] ?? null,
                        'meeting_link' => $meet['meeting_link'] ?? null,
                        'google_calendar_event_id' => $meet['google_calendar_event_id'] ?? null,
                    ]);
                    $appointment->meeting_id = $meet['meeting_id'];
                    $appointment->meeting_link = $meet['meeting_link'];
                    $appointment->google_calendar_event_id = $meet['google_calendar_event_id'] ?? null;
                    $appointment->doctor_joined_at = $appointment->doctor_joined_at ?? now();
                    $appointment->video_session_started_at = $appointment->video_session_started_at ?? now();
                    $appointment->save();

                    DB::commit();
                    Log::info('GOOGLE_MEET_SAVED', [
                        'appointment_id' => $appointment->id,
                        'video_provider' => $appointment->video_provider,
                        'meeting_id' => $appointment->meeting_id,
                        'meeting_link' => $appointment->meeting_link,
                        'google_calendar_event_id' => $appointment->google_calendar_event_id,
                    ]);
                    broadcast(new DoctorJoinedVideoAppointment($appointment))->toOthers();

                    return response()->json([
                        'status' => true,
                        'fallback_used' => false,
                        'message' => 'Google Meet ready. Opening meeting link.',
                        'message_key' => 'video_join_google_ready',
                        'data' => [
                            'provider' => 'google',
                            'join_message' => 'Google Meet ready. Opening meeting link.',
                            'join_message_key' => 'video_join_google_ready',
                            'meeting_id' => $appointment->meeting_id,
                            'meeting_link' => $appointment->meeting_link,
                            'google_calendar_event_id' => $appointment->google_calendar_event_id,
                            'video_join_open_at' => $appointment->video_join_open_at,
                            'video_join_close_at' => $appointment->video_join_close_at,
                        ],
                    ], 200);
                } catch (\Throwable $e) {
                    DB::rollBack();

                    Log::warning('DoctorWebAppointmentController@videoJoinData google failed, fallback agora', [
                        'appointment_id' => $appointment->id,
                        'user_id' => $user?->id,
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);

                    $fallbackUsed = true;
                }
            }

            DB::beginTransaction();

            $appointment->doctor_joined_at = $appointment->doctor_joined_at ?? now();
            $appointment->video_session_started_at = $appointment->video_session_started_at ?? now();
            $appointment->save();

            $agoraResult = $this->agoraJoinDataService->buildJoinData($appointment, (int) $user->id);

            DB::commit();

            broadcast(new DoctorJoinedVideoAppointment($appointment))->toOthers();

            return response()->json([
                'status' => true,
                'fallback_used' => $fallbackUsed,
                'message' => $fallbackUsed
                    ? 'Google Meet is not available right now. Opening internal video call.'
                    : 'Internal video call ready.',
                'message_key' => $fallbackUsed
                    ? 'video_join_google_not_available_fallback_agora'
                    : 'video_join_internal_ready',
                'data' => array_merge($agoraResult['data'], [
                    'join_message' => $fallbackUsed
                        ? 'Google Meet is not available right now. Opening internal video call.'
                        : 'Internal video call ready.',
                    'join_message_key' => $fallbackUsed
                        ? 'video_join_google_not_available_fallback_agora'
                        : 'video_join_internal_ready',
                ]),
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('DoctorWebAppointmentController@videoJoinData failed', [
                'appointment_id' => (int) $id,
                'user_id' => $request->user()?->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Could not prepare video join data.',
                'message_key' => 'video_join_prepare_failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function createGoogleMeetForAppointment(AppointmentModel $appointment): array
    {
        $doctor = \App\Models\DoctorsModel::query()->find($appointment->doct_id);

        if (!$doctor) {
            throw new \RuntimeException('Doctor profile not found.');
        }

        $doctorUser = User::find($doctor->user_id);

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

        $dateRaw = trim((string) ($appointment->date ?? ''));
        $timeRaw = trim((string) ($appointment->time_slots ?? ''));

        try {
            $dateNormalized = \Carbon\Carbon::parse($dateRaw)->format('Y-m-d');
        } catch (\Throwable $e) {
            throw new \RuntimeException('Invalid appointment date format: ' . $dateRaw);
        }

        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $timeRaw)) {
            throw new \RuntimeException('Invalid appointment time format: ' . $timeRaw);
        }

        if (strlen($timeRaw) === 5) {
            $timeRaw .= ':00';
        }

        $startString = $dateNormalized . ' ' . $timeRaw;

        Log::info('GOOGLE_MEET_INPUT', [
            'appointment_id' => $appointment->id,
            'date_raw' => $dateRaw,
            'date_normalized' => $dateNormalized,
            'time_raw' => $timeRaw,
            'duration_minutes' => $appointment->duration_minutes,
            'start_string' => $startString,
            'timezone' => $timeZone,
        ]);

        $start = new \DateTime($startString, new \DateTimeZone($timeZone));

        $durationMinutes = (int) ($appointment->duration_minutes ?? 20);
        if ($durationMinutes <= 0) {
            $durationMinutes = 20;
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
    private function currentDoctorId(Request $request): int
{
    $vUser = \App\Models\VUser::query()->where('id', $request->user()->id)->first();

    if (!$vUser || empty($vUser->doctor_id)) {
        throw new \RuntimeException('Doctor profile not found.');
    }

    return (int) $vUser->doctor_id;
}
}