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
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            $perPage = (int) $request->get('per_page', 15);
            if ($perPage <= 0) {
                $perPage = 15;
            }

            $query = DB::table('appointments')
                ->leftJoin('patients', 'patients.id', '=', 'appointments.patient_id')
                ->leftJoin('clinics', 'clinics.id', '=', 'appointments.clinic_id')
                ->leftJoin('department', 'department.id', '=', 'appointments.dept_id')
                ->select(
                    'appointments.id',
                    'appointments.patient_id',
                    'appointments.status',
                    'appointments.date',
                    'appointments.time_slots',
                    'appointments.duration_minutes',
                    'appointments.doct_id',
                    'appointments.clinic_id',
                    'appointments.dept_id',
                    'appointments.type',
                    'appointments.meeting_id',
                    'appointments.meeting_link',
                    'appointments.google_calendar_event_id',
                    'appointments.video_provider',
                    'appointments.video_channel_name',
                    'appointments.video_join_open_at',
                    'appointments.video_join_close_at',
                    'appointments.video_session_started_at',
                    'appointments.doctor_joined_at',
                    'appointments.patient_joined_at',
                    'appointments.video_session_ended_at',
                    'appointments.video_last_token_expires_at',
                    'appointments.id_payment',
                    'appointments.payment_status',
                    'appointments.payment_reference',
                    'appointments.payment_provider',
                    'appointments.payment_confirmed_at',
                    'appointments.current_cancel_req_status',
                    'appointments.source',
                    'appointments.created_at',
                    'appointments.updated_at',
                    'patients.f_name as patient_f_name',
                    'patients.l_name as patient_l_name',
                    DB::raw("TRIM(CONCAT(COALESCE(patients.f_name,''), ' ', COALESCE(patients.l_name,''))) as patient_name"),
                    'clinics.title as clinic_name',
                    'department.title as department_name'
                )
                ->where('appointments.doct_id', $user->id);

            if ($request->filled('status')) {
                $query->where('appointments.status', $request->status);
            }

            if ($request->filled('date')) {
                $query->whereDate('appointments.date', $request->date);
            }

            $data = $query
                ->orderByDesc('appointments.date')
                ->orderByDesc('appointments.time_slots')
                ->paginate($perPage);

            return response()->json([
                'status' => true,
                'data' => $data,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('DoctorWebAppointmentController@index failed', [
                'user_id' => $request->user()?->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Could not load appointments.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $appointmentId = (int) $id;

            $appointment = DB::table('appointments')
                ->leftJoin('patients', 'patients.id', '=', 'appointments.patient_id')
                ->leftJoin('clinics', 'clinics.id', '=', 'appointments.clinic_id')
                ->leftJoin('department', 'department.id', '=', 'appointments.dept_id')
                ->select(
                    'appointments.*',
                    'patients.f_name as patient_f_name',
                    'patients.l_name as patient_l_name',
                    'patients.phone as patient_phone',
                    'patients.email as patient_email',
                    DB::raw("TRIM(CONCAT(COALESCE(patients.f_name,''), ' ', COALESCE(patients.l_name,''))) as patient_name"),
                    'clinics.id as clinic_ref_id',
                    'clinics.title as clinic_name',
                    'department.id as department_ref_id',
                    'department.title as department_name'
                )
                ->where('appointments.id', $appointmentId)
                ->where('appointments.doct_id', $user->id)
                ->first();

            if (!$appointment) {
                return response()->json([
                    'status' => false,
                    'message' => 'Appointment not found or access denied.',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $appointment,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('DoctorWebAppointmentController@show failed', [
                'appointment_id' => (int) $id,
                'user_id' => $request->user()?->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Could not load appointment details.',
                'error' => $e->getMessage(),
            ], 500);
        }
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
                ], 400);
            }

            if (($appointment->payment_status ?? 'Unpaid') !== 'Paid') {
                return response()->json([
                    'status' => false,
                    'message' => 'Appointment must be paid before joining video.',
                ], 400);
            }

            if (in_array($appointment->status, ['Rejected', 'Cancelled', 'Completed', 'Visited'], true)) {
                return response()->json([
                    'status' => false,
                    'message' => 'This appointment is not available for video call.',
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
                        'data' => [
                            'provider' => 'google',
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
                'data' => $agoraResult['data'],
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
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function createGoogleMeetForAppointment(AppointmentModel $appointment): array
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
}