<?php

namespace App\Http\Controllers\Api\V1\DoctorWeb;

use App\Events\DoctorJoinedVideoAppointment;
use App\Http\Controllers\Controller;
use App\Models\AppointmentModel;
use App\Services\AgoraJoinDataService;
use App\Services\DoctorWeb\DoctorWebAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // 1. Verificamos si el usuario llega bien
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'No Auth User'], 401);
        }

        // 2. Consulta mínima para asegurar que el canal funcione
        $data = DB::table('appointments')
            ->select('id', 'status', 'date')
            ->where('doct_id', $user->id)
            ->orderByDesc('date')
            ->paginate(15);

        return response()->json([
            'status' => true,
            'debug' => 'Conexión Exitosa',
            'data' => $data
        ], 200);

    } catch (\Throwable $e) {
        // 3. Si hay error, lo devolvemos con un 200 para que el CORS NO lo bloquee
        // Esto es solo para encontrar el error ahora mismo
        return response()->json([
            'status' => false,
            'debug' => 'Error de SQL detectado',
            'error_mensaje' => $e->getMessage()
        ], 200); 
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
            \Log::error('DoctorWebAppointmentController@show failed', [
                'appointment_id' => (int) $id,
                'user_id' => $request->user()?->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
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
        $validator = \Validator::make($request->all(), [
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

        $user = $request->user();
        $provider = strtolower(trim($appointment->video_provider ?? 'agora'));

        if ($provider === 'google') {
            try {
                if (empty($appointment->meeting_link)) {
                    $meetingCode = strtolower(\Str::random(3)) . '-' . strtolower(\Str::random(4)) . '-' . strtolower(\Str::random(3));
                    $appointment->meeting_id = $meetingCode;
                    $appointment->meeting_link = 'https://meet.google.com/' . $meetingCode;
                }

                $appointment->doctor_joined_at = $appointment->doctor_joined_at ?? now();
                $appointment->video_session_started_at = $appointment->video_session_started_at ?? now();
                $appointment->save();

                //broadcast(new DoctorJoinedVideoAppointment($appointment))->toOthers();

                return response()->json([
                    'status' => true,
                    'fallback_used' => false,
                    'data' => [
                        'provider' => 'google',
                        'meeting_id' => $appointment->meeting_id,
                        'meeting_link' => $appointment->meeting_link,
                    ],
                ], 200);
            } catch (\Throwable $e) {
                $appointment->video_provider = 'agora';
                $appointment->meeting_link = null;
                $appointment->save();
            }
        }

        $appointment->doctor_joined_at = $appointment->doctor_joined_at ?? now();
        $appointment->video_session_started_at = $appointment->video_session_started_at ?? now();
        $appointment->save();

        $agoraResult = $this->agoraJoinDataService->buildJoinData($appointment, (int) $user->id);

        broadcast(new DoctorJoinedVideoAppointment($appointment))->toOthers();

        return response()->json([
            'status' => true,
            'fallback_used' => $provider === 'google',
            'data' => $agoraResult['data'],
        ], 200);
    }
}