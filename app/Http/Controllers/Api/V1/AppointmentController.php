<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppointmentModel;
use App\Models\AppointmentInvoiceModel;
use App\Models\AppointmentPaymentModel;
use App\Models\AppointmentStatusLogModel;
use App\Models\AppointmentInvoiceItemModel;
use App\Models\AllTransactionModel;
use App\Models\User;
use App\Models\FamilyMembersModel;
use App\Models\PatientModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\V1\ZoomVideoCallController;
use App\Models\CouponUseModel;
use App\Http\Controllers\Api\V1\NotificationCentralController;
use App\Models\PatientClinicModel;
use App\Events\DoctorJoinedVideoAppointment;
use Illuminate\Support\Str;
use App\Services\AgoraJoinDataService;
use Illuminate\Support\Facades\Http;
use App\Models\TimeSlotsModel;
use App\Models\TimeSlotsVideoModel;


class AppointmentController extends Controller
{
    private AgoraJoinDataService $agoraJoinDataService;

    public function __construct(AgoraJoinDataService $agoraJoinDataService)
    {
        $this->agoraJoinDataService = $agoraJoinDataService;
    }

    protected function isPaidByType(?int $idPaymentType): bool
    {
        return !is_null($idPaymentType) && $idPaymentType >= 8000;
    }

    // update status to paid/unpaid according to payment type
    function updateStatusToPaid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appointment_id' => 'required|integer',
            'payment_method' => 'required|string',
            'id_payment_type' => 'required|integer',
            'total_amount' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response(['response' => 400], 400);
        }

        try {
            DB::beginTransaction();

            $appointmentId = (int) $request->appointment_id;
            $idPaymentType = (int) $request->id_payment_type;
            $timeStamp = now();
            $date = now()->toDateString();

            $resolvedPaymentStatus = $this->resolveAppointmentPaymentStatusByType($idPaymentType);

            if ($resolvedPaymentStatus !== 'Paid') {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Selected payment type does not resolve to Paid.',
                ], 422);
            }

            $appointment = AppointmentModel::where('id', $appointmentId)->first();

            if (!$appointment) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Appointment not found.',
                ], 404);
            }

            if ($appointment->id_payment) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Appointment already has a payment registered.',
                ], 422);
            }

            $paymentType = DB::table('payment_types')
                ->select('id', 'name', 'active')
                ->where('id', $idPaymentType)
                ->first();

            if (!$paymentType || (int) $paymentType->active !== 1) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid payment type.',
                ], 422);
            }

            $patient = DB::table('patients')
                ->select('id', 'user_id')
                ->where('id', $appointment->patient_id)
                ->first();

            if (!$patient || !$patient->user_id) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Patient payer user not found.',
                ], 422);
            }

            $invoice = AppointmentInvoiceModel::where('appointment_id', $appointmentId)->first();

            $amount = (float) ($appointment->amount ?? $request->total_amount ?? $request->fee ?? $invoice->total_amount ?? 0);

            if (!$invoice) {
                $invoice = new AppointmentInvoiceModel();
                $invoice->patient_id = $appointment->patient_id;
                $invoice->user_id = $patient->user_id;
                $invoice->clinic_id = $appointment->clinic_id;
                $invoice->appointment_id = $appointment->id;
                $invoice->status = 'Paid';
                $invoice->total_amount = $amount;
                $invoice->invoice_date = $date;
                $invoice->created_at = $timeStamp;
                $invoice->updated_at = $timeStamp;
                $invoice->save();

                $invoiceItem = new AppointmentInvoiceItemModel();
                $invoiceItem->invoice_id = $invoice->id;
                $invoiceItem->description = $appointment->type ?? 'Appointment';
                $invoiceItem->quantity = 1;
                $invoiceItem->clinic_id = $appointment->clinic_id;
                $invoiceItem->unit_price = $amount;
                $invoiceItem->service_charge = 0;
                $invoiceItem->total_price = $amount;
                $invoiceItem->unit_tax = 0;
                $invoiceItem->unit_tax_amount = 0;
                $invoiceItem->created_at = $timeStamp;
                $invoiceItem->updated_at = $timeStamp;
                $invoiceItem->save();
            } else {
                $invoice->status = 'Paid';
                if ((float) $invoice->total_amount <= 0 && $amount > 0) {
                    $invoice->total_amount = $amount;
                }
                $invoice->updated_at = $timeStamp;
                $invoice->save();
            }

            $providerReference = $request->provider_reference
                ?? ('APPT-' . $appointment->id . '-' . now()->timestamp);

            $paymentId = $this->createPaymentForAppointment(
                $appointment,
                (int) $patient->user_id,
                $idPaymentType,
                $paymentType->name,
                $request->payment_provider ?? 'manual',
                $request->payment_method_code ?? $request->payment_method,
                $providerReference,
                $amount
            );

            $appointment->id_payment = $paymentId;
            $appointment->payment_status = 'Paid';
            $appointment->payment_confirmed_at = $timeStamp;
            $appointment->updated_at = $timeStamp;
            $appointment->save();

            DB::commit();

            return Helpers::successWithIdResponse('successfully', $appointmentId);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'error ' . $e->getMessage(),
            ], 500);
        }
    }
        
    // appointment resch
    function appointmentResch(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'id' => 'required',
            'time_slots' => 'required',
            'date' => 'required',
        ]);

        if ($validator->fails()) {
            return response(["response" => 400], 400);
        }

        try {
            DB::beginTransaction();

            $dataModel = AppointmentModel::where("id", $request->id)->first();
            if ($dataModel == null) {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }

            $oldTime = $dataModel->time_slots;
            $oldDate = $dataModel->date;
            $currentStatus = $dataModel->status;

            if ($currentStatus == "Rejected" || $currentStatus == "Cancelled") {
                DB::rollBack();
                return Helpers::errorResponse("Cannot update status");
            }

            $dataModel->status = 'Rescheduled';
            $dataModel->time_slots = $request->time_slots;
            $dataModel->date = $request->date;
            $timeStamp = date("Y-m-d H:i:s");
            $dataModel->updated_at = $timeStamp;
            $qResponce = $dataModel->save();

            if (!$qResponce) {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }

            $appointmentData = DB::table("appointments")
                ->select('appointments.*', 'patients.user_id')
                ->join("patients", "patients.id", '=', 'appointments.patient_id')
                ->where("appointments.id", "=", $request->id)
                ->first();

            $userId = $appointmentData->user_id;
            $patient_id = $appointmentData->patient_id;
            if ($patient_id == null) {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }

            $dataASLModel = new AppointmentStatusLogModel;
            $dataASLModel->appointment_id  =  $request->id;
            $dataASLModel->user_id  = $userId;
            $dataASLModel->status  = "Rescheduled";
            $dataASLModel->patient_id  = $patient_id;
            $dataASLModel->clinic_id  =  $dataModel->clinic_id;
            $dataASLModel->notes  = "Appointment " . $oldDate . " " . $oldTime . " rescheduled to " . $request->date . " " . $request->time_slots;
            $dataASLModel->created_at = $timeStamp;
            $dataASLModel->updated_at = $timeStamp;
            $qResponceApp = $dataASLModel->save();

            if (!$qResponceApp) {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }

            DB::commit();

            if ($dataModel->type == "Video Consultant") {
                $zoomController = new ZoomVideoCallController();
                $zoomController->updateMeeting($dataModel->id, $dataModel->meeting_id, $request->date, $request->time_slots);
            }

            $notificationCentralController = new NotificationCentralController();
            $notificationCentralController->sendWalletRshNotificationToUsersAgainstRejected($request->id, $oldDate, $oldTime);

            return Helpers::successResponse("successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("error $e");
        }
    }

    // Update data
    function updateStatus(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'id' => 'required',
            "status" => 'required'
        ]);

        if ($validator->fails()) {
            return response(["response" => 400], 400);
        }

        try {
            DB::beginTransaction();

            if ($request->status == "Cancelled") {
                DB::rollBack();
                return Helpers::errorResponse("Cannot update status");
            }

            $dataModel = AppointmentModel::where("id", $request->id)->first();
            $currentStatus = $dataModel->status;
            if ($currentStatus == "Rejected" || $currentStatus == "Cancelled") {
                DB::rollBack();
                return Helpers::errorResponse("Cannot update status");
            }

            $dataModel->status = $request->status;
            $timeStamp = date("Y-m-d H:i:s");
            $dataModel->updated_at = $timeStamp;
            $qResponce = $dataModel->save();

            if ($qResponce) {
                $appointmentData = DB::table("appointments")
                    ->select('appointments.*', 'patients.user_id')
                    ->join("patients", "patients.id", '=', 'appointments.patient_id')
                    ->where("appointments.id", "=", $request->id)
                    ->first();

                $userId = $appointmentData->user_id;
                $patient_id = $appointmentData->patient_id;
                if ($patient_id == null) {
                    DB::rollBack();
                    return Helpers::errorResponse("error");
                }

                $dataASLModel = new AppointmentStatusLogModel;
                $dataASLModel->appointment_id  =  $request->id;
                $dataASLModel->user_id  = $userId;
                $dataASLModel->status  =  $request->status;
                $dataASLModel->clinic_id = $dataModel->clinic_id;
                $dataASLModel->patient_id  = $patient_id;
                $dataASLModel->created_at = $timeStamp;
                $dataASLModel->updated_at = $timeStamp;
                $qResponceApp = $dataASLModel->save();

                if (!$qResponceApp) {
                    DB::rollBack();
                    return Helpers::errorResponse("error");
                }

                DB::commit();

                if ($request->status == "Rejected") {
                    if ($appointmentData->type == "Video Consultant") {
                        $zoomController = new ZoomVideoCallController();
                        $zoomController->deleteMeeting($appointmentData->id, $appointmentData->meeting_id);
                    }
                }

                $notificationCentralController = new NotificationCentralController();
                $notificationCentralController->sendAppointmentSatusChangeNotificationToUsers($request->id, $request->status);

                return Helpers::successResponse("successfully");
            } else {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("error");
        }
    }

    // Delete Data
    function deleteData(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response(["response" => 400], 400);
        }

        try {
            $dataModel = CityModel::where("id", $request->id)->first();
            $qResponce = $dataModel->delete();

            if ($qResponce) {
                return Helpers::successResponse("successfully");
            } else {
                return Helpers::errorResponse("error");
            }
        } catch (\Exception $e) {
            return Helpers::errorResponse("error");
        }
    }

    // get data
    public function getBookedTimeSlotsByDoctIdAndDateAndTpe(Request $request)
    {
        $request->validate([
            'doct_id' => 'required|integer',
            'date' => 'required|date',
            'type' => 'required|string',
            'clinic_id' => 'nullable|integer',
        ]);

        $data = AppointmentModel::query()
            ->select(
                DB::raw('TIME_FORMAT(appointments.time_slots, "%H:%i") as time_slots'),
                'appointments.date',
                'appointments.type',
                'appointments.id as appointment_id'
            )
            ->where('appointments.doct_id', '=', $request->doct_id)
            ->whereDate('appointments.date', '=', $request->date)
            ->where('appointments.type', '=', $request->type)
            ->where('appointments.status', '!=', 'Rejected')
            ->where('appointments.status', '!=', 'Completed')
            ->where('appointments.status', '!=', 'Cancelled');

        if ($request->filled('clinic_id')) {
            $data->where('appointments.clinic_id', '=', $request->clinic_id);
        }

        return response()->json([
            'status' => true,
            'response' => 200,
            'data' => $data->get(),
        ], 200);
    }
    // get data by id
    public function getDataById($id)
    {
        $data = DB::table('appointments')
            ->select(
                'appointments.*',
                'patients.user_id',
                'patients.mrn as patient_mrn',
                'patients.f_name as patient_f_name',
                'patients.l_name as patient_l_name',
                'patients.phone as patient_phone',
                'patients.gender as patient_gender',
                'department.title as dept_title',

                'vd.doctor_name as doct_name',
                'vd.f_name as doct_f_name',
                'vd.l_name as doct_l_name',
                'vd.image as doct_image',
                'vd.specialization as doct_specialization',
                'vd.email as doct_email',
                'vd.phone as doct_phone',

                'clinics.title as clinic_title',
                'clinics.address as clinics_address',
                'clinics.image as clinic_thumb_image',
                'clinics.phone as clinic_phone',
                'clinics.phone_second as clinic_phone_second',
                'clinics.whatsapp as clinic_whatsapp',
                'clinics.email as clinic_email',
                'clinics.latitude as clinic_latitude',
                'clinics.longitude as clinic_longitude',
                'clinics.ambulance_number as clinic_ambulance_number',
                'clinics.ambulance_btn_enable as clinic_ambulance_btn_enable'
            )
            ->join('patients', 'patients.id', '=', 'appointments.patient_id')
            ->join('department', 'department.id', '=', 'appointments.dept_id')
            ->join('clinics', 'clinics.id', '=', 'appointments.clinic_id')
            ->leftJoin('v_doctors as vd', 'vd.doctor_id', '=', 'appointments.doct_id')
            ->where('appointments.id', '=', $id)
            ->first();

        if ($data != null) {
            $reviewAgg = DB::table('doctors_review')
                ->selectRaw('COALESCE(SUM(points),0) as total_review_points, COUNT(*) as number_of_reviews')
                ->where('doctor_id', '=', $data->doct_id)
                ->first();

            $data->total_review_points = (int) ($reviewAgg->total_review_points ?? 0);
            $data->number_of_reviews = (int) ($reviewAgg->number_of_reviews ?? 0);
            $data->average_rating = $data->number_of_reviews > 0
                ? number_format($data->total_review_points / $data->number_of_reviews, 2)
                : '0.00';

            $data->total_appointment_done = DB::table('appointments')
                ->where('appointments.doct_id', '=', $data->doct_id)
                ->count();

            $data = $this->appendVideoFlags($data);
        }

        return response([
            'response' => 200,
            'data' => $data,
        ], 200);
    }

    protected function appendVideoFlags(object $item): object
    {
        $item->is_video_consult = (($item->type ?? '') === 'Video Consultant');
        $item->must_pay_first = false;
        $item->can_join_video = false;
        $item->video_join_opens_at = null;
        $item->video_join_seconds_remaining = 0;

        if (!$item->is_video_consult) {
            return $item;
        }

        if (($item->payment_status ?? '') !== 'Paid') {
            $item->must_pay_first = true;
            return $item;
        }

        $appointmentDate = date('Y-m-d', strtotime((string) $item->date));
        $appointmentTime = date('H:i:s', strtotime((string) $item->time_slots));
        $appointmentDateTime = strtotime($appointmentDate . ' ' . $appointmentTime);

        if (!$appointmentDateTime) {
            return $item;
        }

        $durationMinutes = (int) ($item->duration_minutes ?? 15);
        $lateToleranceMinutes = (int) config('agora.late_tolerance_minutes', 20);

        $joinOpensAt = $appointmentDateTime - (5 * 60);
        $joinClosesAt = $appointmentDateTime + (($durationMinutes + $lateToleranceMinutes) * 60);
        $now = time();

        $item->video_join_opens_at = $joinOpensAt;
        $item->video_join_closes_at = $joinClosesAt;
        $item->video_join_seconds_remaining = max(0, $joinOpensAt - $now);
        $item->can_join_video = $now >= $joinOpensAt && $now <= $joinClosesAt;
        $item->server_now = $now;

        return $item;
    }

    public function getData(Request $request)
    {
        $query = DB::table('appointments')
            ->select(
                'appointments.*',
                'patients.user_id',
                'patients.f_name as patient_f_name',
                'patients.l_name as patient_l_name',
                'patients.phone as patient_phone',
                'department.title as dept_title',

                'vd.doctor_name as doct_name',
                'vd.f_name as doct_f_name',
                'vd.l_name as doct_l_name',
                'vd.image as doct_image',
                'vd.specialization as doct_specialization',
                'vd.email as doct_email',

                'clinics.title as clinic_title'
            )
            ->join('patients', 'patients.id', '=', 'appointments.patient_id')
            ->join('department', 'department.id', '=', 'appointments.dept_id')
            ->join('clinics', 'clinics.id', '=', 'appointments.clinic_id')
            ->leftJoin('v_doctors as vd', 'vd.doctor_id', '=', 'appointments.doct_id');

        if ($request->filled('start_date')) {
            $query->whereDate('appointments.date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('appointments.date', '<=', $request->end_date);
        }

        if ($request->filled('status')) {
            $statuses = array_filter(array_map('trim', explode(',', $request->status)));
            if (!empty($statuses)) {
                $query->whereIn('appointments.status', $statuses);
            }
        }

        if ($request->filled('doct_id')) {
            $query->where('appointments.doct_id', '=', $request->doct_id);
        }

        if ($request->filled('clinic_id')) {
            $query->where('appointments.clinic_id', '=', $request->clinic_id);
        }

        if ($request->filled('patient_id')) {
            $query->where('appointments.patient_id', '=', $request->patient_id);
        }

        if ($request->filled('type')) {
            $types = array_filter(array_map('trim', explode(',', $request->type)));
            if (!empty($types)) {
                $query->whereIn('appointments.type', $types);
            }
        }

        if ($request->filled('current_cancel_req_status')) {
            $cancelStatuses = array_filter(array_map('trim', explode(',', $request->current_cancel_req_status)));
            if (!empty($cancelStatuses)) {
                $query->whereIn('appointments.current_cancel_req_status', $cancelStatuses);
            }
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('patients.f_name', 'like', "%{$search}%")
                    ->orWhere('patients.l_name', 'like', "%{$search}%")
                    ->orWhere('vd.f_name', 'like', "%{$search}%")
                    ->orWhere('vd.l_name', 'like', "%{$search}%")
                    ->orWhere('patients.phone', 'like', "%{$search}%")
                    ->orWhere('appointments.id', 'like', "%{$search}%")
                    ->orWhere('appointments.status', 'like', "%{$search}%")
                    ->orWhere('appointments.time_slots', 'like', "%{$search}%")
                    ->orWhere('appointments.date', 'like', "%{$search}%")
                    ->orWhere('appointments.type', 'like', "%{$search}%")
                    ->orWhere('appointments.current_cancel_req_status', 'like', "%{$search}%")
                    ->orWhere('vd.specialization', 'like', "%{$search}%");
            });
        }

        $total_record = (clone $query)->count();

        if ($request->filled('start') && $request->filled('end')) {
            $start = (int) $request->start;
            $end = (int) $request->end;
            $limit = max(0, $end - $start);

            if ($limit > 0) {
                $query->skip($start)->take($limit);
            }
        }

        $data = $query
            ->orderByDesc('appointments.date')
            ->orderByDesc('appointments.time_slots')
            ->get()
            ->map(function ($item) {
                return $this->appendVideoFlags($item);
            });

        return response()->json([
            'response' => 200,
            'total_record' => $total_record,
            'data' => $data,
        ], 200);
    }

    private function createGoogleMeetForAppointment($appointment, $user): array
    {
        $doctor = DB::table('doctors')
            ->select('id', 'user_id', 'department', 'video_provider')
            ->where('id', $appointment->doct_id)
            ->first();

        if (!$doctor) {
            throw new \RuntimeException('Doctor profile not found.');
        }

        $doctorUser = User::find($doctor->user_id);

        if (!$doctorUser) {
            throw new \RuntimeException('Doctor user not found.');
        }

        if (
            empty($doctorUser->google_access_token) &&
            empty($doctorUser->google_refresh_token)
        ) {
            throw new \RuntimeException('Doctor Google account is not connected.');
        }

        $accessToken = $doctorUser->google_access_token;
        $refreshToken = $doctorUser->google_refresh_token;
        $expiresAt = $doctorUser->google_token_expires_at;

        $needsRefresh = true;

        if (!empty($accessToken) && !empty($expiresAt)) {
            try {
                $needsRefresh = now()->greaterThanOrEqualTo(
                    \Carbon\Carbon::parse($expiresAt)->subMinutes(2)
                );
            } catch (\Throwable $e) {
                $needsRefresh = true;
            }
        }

        if ($needsRefresh) {
            if (empty($refreshToken)) {
                throw new \RuntimeException('Doctor Google refresh token not found.');
            }

            $tokenData = $this->refreshGoogleAccessToken($refreshToken);

            $accessToken = $tokenData['access_token'] ?? null;
            $newRefreshToken = $tokenData['refresh_token'] ?? $refreshToken;
            $expiresIn = (int) ($tokenData['expires_in'] ?? 3600);

            if (empty($accessToken)) {
                throw new \RuntimeException('Unable to refresh Google access token.');
            }

            $doctorUser->google_access_token = $accessToken;
            $doctorUser->google_refresh_token = $newRefreshToken;
            $doctorUser->google_token_expires_at = now()->addSeconds($expiresIn);
            $doctorUser->save();
        }

        $client = new \Google\Client();
        $client->setAccessToken($accessToken);

        $calendar = new \Google\Service\Calendar($client);

        $startDateTime = \Carbon\Carbon::parse(
            $appointment->date . ' ' . $appointment->time_slots
        );

        $endDateTime = (clone $startDateTime)->addMinutes(
            (int) ($appointment->duration_minutes ?: 15)
        );

        $summary = 'Video Consultation #' . $appointment->id;

        $description = 'Appointment #' . $appointment->id;
        if (!empty($appointment->type)) {
            $description .= ' - ' . $appointment->type;
        }

        $event = new \Google\Service\Calendar\Event([
            'summary' => $summary,
            'description' => $description,
            'start' => [
                'dateTime' => $startDateTime->toIso8601String(),
                'timeZone' => config('app.timezone', 'UTC'),
            ],
            'end' => [
                'dateTime' => $endDateTime->toIso8601String(),
                'timeZone' => config('app.timezone', 'UTC'),
            ],
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => 'appt-' . $appointment->id . '-' . Str::uuid(),
                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet',
                    ],
                ],
            ],
        ]);

        $createdEvent = $calendar->events->insert('primary', $event, [
            'conferenceDataVersion' => 1,
            'sendUpdates' => 'none',
        ]);

        $meetingLink =
            $createdEvent->getHangoutLink() ?:
            data_get($createdEvent, 'conferenceData.entryPoints.0.uri');

        $meetingId = null;
        if ($meetingLink) {
            $parts = parse_url($meetingLink);
            if (!empty($parts['path'])) {
                $meetingId = trim($parts['path'], '/');
            }
        }

        if (empty($meetingLink)) {
            throw new \RuntimeException('Google Meet link was not generated.');
        }

        return [
            'meeting_link' => $meetingLink,
            'meeting_id' => $meetingId,
            'google_calendar_event_id' => $createdEvent->getId(),
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
    private function resolveAppointmentPaymentStatusByType(?int $idPaymentType): string
    {
        if (!$idPaymentType) {
            return 'Unpaid';
        }

        return $idPaymentType >= 8000 ? 'Paid' : 'Unpaid';
    }

    
    private function ensurePatientClinic(int $patientId, int $clinicId): object
    {
        $patient = DB::table('patients')
            ->select('id', 'user_id')
            ->where('id', $patientId)
            ->first();

        if (!$patient) {
            throw new \RuntimeException('Patient not found.');
        }

        $exists = DB::table('patient_clinic')
            ->where('patient_id', $patientId)
            ->where('clinic_id', $clinicId)
            ->exists();

        if (!$exists) {
            DB::table('patient_clinic')->insert([
                'patient_id' => $patientId,
                'clinic_id' => $clinicId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $patient;
    }

    private function resolveOrCreatePatientByUserIdForClinic(int $userId, int $clinicId): object
    {
        $patient = DB::table('patients')
            ->select('id', 'user_id')
            ->where('user_id', $userId)
            ->first();

        if (!$patient) {
            DB::statement(
                "
                INSERT INTO patients (
                    clinic_id,
                    user_id,
                    f_name,
                    l_name,
                    isd_code,
                    phone,
                    city,
                    state,
                    address,
                    email,
                    gender,
                    dob,
                    image,
                    postal_code,
                    created_at,
                    updated_at
                )
                SELECT
                    ?,
                    u.id,
                    u.f_name,
                    u.l_name,
                    u.isd_code,
                    u.phone,
                    u.city,
                    u.state,
                    u.address,
                    u.email,
                    u.gender,
                    u.dob,
                    u.image,
                    u.postal_code,
                    NOW(),
                    NOW()
                FROM users u
                WHERE u.id = ?
                ",
                [$clinicId, $userId]
            );

            $patient = DB::table('patients')
                ->select('id', 'user_id')
                ->where('user_id', $userId)
                ->first();

            if (!$patient) {
                throw new \RuntimeException('Could not create patient record.');
            }

            DB::table('patients')
                ->where('id', $patient->id)
                ->update([
                    'mrn' => $patient->id,
                    'updated_at' => now(),
                ]);
        }

        $exists = DB::table('patient_clinic')
            ->where('patient_id', $patient->id)
            ->where('clinic_id', $clinicId)
            ->exists();

        if (!$exists) {
            DB::table('patient_clinic')->insert([
                'patient_id' => $patient->id,
                'clinic_id' => $clinicId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('users')
            ->where('id', $userId)
            ->update([
                'patient_id' => $patient->id,
                'updated_at' => now(),
            ]);

        return $patient;
    }

    private function resolvePaidPaymentStatusId(): int
    {
        return 99;
    }
    private function createPaymentForAppointment(
        AppointmentModel $appointment,
        int $payerUserId,
        int $idPaymentType,
        string $namePaymentType,
        ?string $provider,
        ?string $paymentMethodCode,
        ?string $providerReference,
        float $amount
    ): int {
        $paidStatusId = $this->resolvePaidPaymentStatusId();

        $paymentId = DB::table('payments')->insertGetId([
            'id_user' => $payerUserId,
            'id_appointment' => $appointment->id,
            'id_payment_type' => $idPaymentType,
            'name_payment_type' => $namePaymentType,
            'provider' => $provider,
            'payment_method_code' => $paymentMethodCode,
            'provider_reference' => $providerReference,
            'id_payment_status' => $paidStatusId,
            'currency_code' => 'PYG',
            'confirmed_at' => now(),
            'amount' => $amount,
            'response_message' => 'Created from appointment registration as paid.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) $paymentId;
    }
    public function addData(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|integer',
            'doct_id' => 'required|integer',
            'clinic_id' => 'required|integer',
            'date' => 'required|date',
            'time' => 'required',
            'appointment_type' => 'required|string',
            'total_amount' => 'nullable|numeric',
            'fee' => 'nullable|numeric',
            'invoice_description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $timeStamp = now();
            $date = now()->toDateString();

            $doctorId = (int) $request->doct_id;
            $clinicId = (int) $request->clinic_id;
            $patientId = (int) $request->patient_id;
            $appointmentType = $request->type ?? $request->appointment_type;
            $appointmentDay = date('l', strtotime($request->date));
            $timeSlotValue = $request->time_slots ?? $request->time;
            $amount = (float) ($request->total_amount ?? $request->fee ?? 0);
            $fee = (float) ($request->fee ?? 0);
            $duration_minutes = (int) ($request->duration_minutes ?? 15);

            $doctor = DB::table('v_doctors')
                ->select(
                    'doctor_id',
                    'clinic_id',
                    'department',
                    'active',
                    'stop_booking',
                    'clinic_appointment',
                    'video_appointment',
                    'emergency_appointment',
                    'is_active'
                )
                ->where('doctor_id', $doctorId)
                ->where('clinic_id', $clinicId)
                ->where('is_active', 1)
                ->first();

            if (!$doctor) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Selected doctor does not belong to the selected clinic.',
                ], 422);
            }

            if ((int) $doctor->active !== 1) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Selected doctor is inactive in this clinic.',
                ], 422);
            }

            if ((int) $doctor->stop_booking === 1) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Selected doctor is not accepting appointments in this clinic.',
                ], 422);
            }

            if ($appointmentType === 'OPD' && (int) $doctor->clinic_appointment !== 1) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Selected doctor does not accept OPD appointments.',
                ], 422);
            }

            if ($appointmentType === 'Video Consultant' && (int) $doctor->video_appointment !== 1) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Selected doctor does not accept video appointments.',
                ], 422);
            }

            if ($appointmentType === 'Emergency' && (int) $doctor->emergency_appointment !== 1) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Selected doctor does not accept emergency appointments.',
                ], 422);
            }

            try {
                $patient = $this->ensurePatientClinic($patientId, $clinicId);
            } catch (\RuntimeException $e) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            $slotQuery = $appointmentType === 'Video Consultant'
                ? TimeSlotsVideoModel::query()
                : TimeSlotsModel::query();

            $slotExists = $slotQuery
                ->where('doct_id', $doctorId)
                ->where('clinic_id', $clinicId)
                ->where('day', $appointmentDay)
                ->where('time_start', '<=', $request->time)
                ->where('time_end', '>', $request->time)
                ->exists();

            if (!$slotExists) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Selected time slot is not available for this doctor in this clinic.',
                ], 422);
            }

            $alreadyBooked = AppointmentModel::query()
                ->where('doct_id', $doctorId)
                ->where('clinic_id', $clinicId)
                ->whereDate('date', $request->date)
                ->where('time_slots', $timeSlotValue)
                ->whereNotIn('status', ['Cancelled', 'Rejected'])
                ->exists();

            if ($alreadyBooked) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'This appointment slot is already booked.',
                ], 422);
            }

            $idPaymentType = $request->id_payment_type
                ? (int) $request->id_payment_type
                : null;

            $paymentType = null;
            if ($idPaymentType) {
                $paymentType = DB::table('payment_types')
                    ->select('id', 'name', 'active')
                    ->where('id', $idPaymentType)
                    ->first();

                if (!$paymentType || (int) $paymentType->active !== 1) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid payment type.',
                    ], 422);
                }
            }

            $paymentStatus = $this->resolveAppointmentPaymentStatusByType($idPaymentType);

            if ($paymentStatus === 'Paid' && !$patient->user_id) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Patient payer user not found.',
                ], 422);
            }

            $appointment = new AppointmentModel();
            $appointment->patient_id = $patientId;
            $appointment->status = $request->status ?? 'Pending';
            $appointment->date = $request->date;
            $appointment->time_slots = $timeSlotValue;
            $appointment->duration_minutes = $request->duration_minutes ?? 15;
            $appointment->amount = $amount;
            $appointment->doct_id = $doctorId;
            $appointment->clinic_id = $clinicId;
            $appointment->dept_id = $request->dept_id ?? $doctor->department;
            $appointment->type = $appointmentType;
            $appointment->id_payment = null;
            $appointment->payment_status = $paymentStatus;
            $appointment->payment_reference = $request->payment_reference ?? null;
            $appointment->payment_provider = $request->payment_provider ?? null;
            $appointment->source = $request->source ?? 'Admin';
            $appointment->created_at = $timeStamp;
            $appointment->updated_at = $timeStamp;
            


            if ($paymentStatus === 'Paid') {
                $appointment->payment_confirmed_at = $timeStamp;
            }

            $appointment->save();

            if (isset($request->coupon_id)) {
                $couponUse = new CouponUseModel();
                $couponUse->user_id = $request->user_id ?? $patient->user_id;
                $couponUse->clinic_id = $clinicId;
                $couponUse->appointment_id = $appointment->id;
                $couponUse->coupon_id = $request->coupon_id;
                $couponUse->created_at = $timeStamp;
                $couponUse->updated_at = $timeStamp;
                $couponUse->save();
            }

            if ($paymentStatus === 'Paid') {
                $invoice = new AppointmentInvoiceModel();
                $invoice->patient_id = $patientId;
                $invoice->user_id = $request->user_id ?? $patient->user_id;
                $invoice->clinic_id = $clinicId;
                $invoice->appointment_id = $appointment->id;
                $invoice->status = $paymentStatus;
                $invoice->total_amount = $amount;
                $invoice->invoice_date = $date;
                $invoice->created_at = $timeStamp;
                $invoice->updated_at = $timeStamp;
                $invoice->coupon_title = $request->coupon_title ?? null;
                $invoice->coupon_value = $request->coupon_value ?? null;
                $invoice->coupon_off_amount = $request->coupon_off_amount ?? null;
                $invoice->coupon_id = $request->coupon_id ?? null;
                $invoice->save();

                $invoiceItem = new AppointmentInvoiceItemModel();
                $invoiceItem->invoice_id = $invoice->id;
                $invoiceItem->description = $request->invoice_description ?? $appointmentType;
                $invoiceItem->quantity = 1;
                $invoiceItem->clinic_id = $clinicId;
                $invoiceItem->unit_price = $fee > 0 ? $fee : $amount;
                $invoiceItem->service_charge = $request->service_charge ?? 0;
                $invoiceItem->total_price = $request->unit_total_amount ?? $amount;
                $invoiceItem->unit_tax = $request->tax ?? 0;
                $invoiceItem->unit_tax_amount = $request->unit_tax_amount ?? 0;
                $invoiceItem->created_at = $timeStamp;
                $invoiceItem->updated_at = $timeStamp;
                $invoiceItem->save();

                if ($idPaymentType) {
                    $providerReference = $request->provider_reference
                        ?? ('APPT-' . $appointment->id . '-' . now()->timestamp);

                    $paymentId = $this->createPaymentForAppointment(
                        $appointment,
                        (int) $patient->user_id,
                        $idPaymentType,
                        $paymentType->name,
                        $request->payment_provider ?? null,
                        $request->payment_method_code ?? null,
                        $providerReference,
                        $amount
                    );

                    $appointment->id_payment = $paymentId;
                    $appointment->save();
                }
            }

            DB::commit();

            $notificationCentralController = new NotificationCentralController();
            $notificationCentralController->sendAppointmentNotificationToUsers($appointment->id);

            return response()->json([
                'response' => 200,
                'status' => true,
                'message' => 'successfully',
                'message_detail' => 'Appointment created successfully.',
                'data' => $appointment,
                'id' => $appointment->id,
                'appointment_id' => $appointment->id,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'error ' . $e->getMessage(),
            ], 500);
        }
    }

    public function addDataFirstAppointment(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'doct_id' => 'required|integer',
            'clinic_id' => 'required|integer',
            'date' => 'required|date',
            'time' => 'required',
            'appointment_type' => 'required|string',
            'total_amount' => 'nullable|numeric',
            'fee' => 'nullable|numeric',
            'invoice_description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $timeStamp = now();
            $date = now()->toDateString();

            $userId = (int) $request->user_id;
            $doctorId = (int) $request->doct_id;
            $clinicId = (int) $request->clinic_id;
            $appointmentType = $request->type ?? $request->appointment_type;
            $appointmentDay = date('l', strtotime($request->date));
            $timeSlotValue = $request->time_slots ?? $request->time;
            $amount = (float) ($request->total_amount ?? $request->fee ?? 0);
            $fee = (float) ($request->fee ?? 0);

            $doctor = DB::table('v_doctors')
                ->select(
                    'doctor_id',
                    'clinic_id',
                    'department',
                    'active',
                    'stop_booking',
                    'clinic_appointment',
                    'video_appointment',
                    'emergency_appointment',
                    'is_active'
                )
                ->where('doctor_id', $doctorId)
                ->where('clinic_id', $clinicId)
                ->where('is_active', 1)
                ->first();

            if (!$doctor) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Selected doctor does not belong to the selected clinic.',
                ], 422);
            }

            if ((int) $doctor->active !== 1) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Selected doctor is inactive in this clinic.',
                ], 422);
            }

            if ((int) $doctor->stop_booking === 1) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Selected doctor is not accepting appointments in this clinic.',
                ], 422);
            }

            if ($appointmentType === 'OPD' && (int) $doctor->clinic_appointment !== 1) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Selected doctor does not accept OPD appointments.',
                ], 422);
            }

            if ($appointmentType === 'Video Consultant' && (int) $doctor->video_appointment !== 1) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Selected doctor does not accept video appointments.',
                ], 422);
            }

            if ($appointmentType === 'Emergency' && (int) $doctor->emergency_appointment !== 1) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Selected doctor does not accept emergency appointments.',
                ], 422);
            }

            try {
                $patient = $this->resolveOrCreatePatientByUserIdForClinic($userId, $clinicId);
            } catch (\RuntimeException $e) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            $slotQuery = $appointmentType === 'Video Consultant'
                ? TimeSlotsVideoModel::query()
                : TimeSlotsModel::query();

            $slotExists = $slotQuery
                ->where('doct_id', $doctorId)
                ->where('clinic_id', $clinicId)
                ->where('day', $appointmentDay)
                ->where('time_start', '<=', $request->time)
                ->where('time_end', '>', $request->time)
                ->exists();

            if (!$slotExists) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Selected time slot is not available for this doctor in this clinic.',
                ], 422);
            }

            $alreadyBooked = AppointmentModel::query()
                ->where('doct_id', $doctorId)
                ->where('clinic_id', $clinicId)
                ->whereDate('date', $request->date)
                ->where('time_slots', $timeSlotValue)
                ->whereNotIn('status', ['Cancelled', 'Rejected'])
                ->exists();

            if ($alreadyBooked) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'This appointment slot is already booked.',
                ], 422);
            }

            $idPaymentType = $request->id_payment_type
                ? (int) $request->id_payment_type
                : null;

            $paymentType = null;
            if ($idPaymentType) {
                $paymentType = DB::table('payment_types')
                    ->select('id', 'name', 'active')
                    ->where('id', $idPaymentType)
                    ->first();

                if (!$paymentType || (int) $paymentType->active !== 1) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid payment type.',
                    ], 422);
                }
            }

            $paymentStatus = $this->resolveAppointmentPaymentStatusByType($idPaymentType);

            $appointment = new AppointmentModel();
            $appointment->patient_id = (int) $patient->id;
            $appointment->status = $request->status ?? 'Pending';
            $appointment->date = $request->date;
            $appointment->time_slots = $timeSlotValue;
            $appointment->duration_minutes = $request->duration_minutes ?? 15;
            $appointment->amount = $amount;
            $appointment->doct_id = $doctorId;
            $appointment->clinic_id = $clinicId;
            $appointment->dept_id = $request->dept_id ?? $doctor->department;
            $appointment->type = $appointmentType;
            $appointment->id_payment = null;
            $appointment->payment_status = $paymentStatus;
            $appointment->payment_reference = $request->payment_reference ?? null;
            $appointment->payment_provider = $request->payment_provider ?? null;
            $appointment->source = $request->source ?? 'Android App';
            $appointment->created_at = $timeStamp;
            $appointment->updated_at = $timeStamp;

            if ($paymentStatus === 'Paid') {
                $appointment->payment_confirmed_at = $timeStamp;
            }

            $appointment->save();

            if (isset($request->coupon_id)) {
                $couponUse = new CouponUseModel();
                $couponUse->user_id = $userId;
                $couponUse->clinic_id = $clinicId;
                $couponUse->appointment_id = $appointment->id;
                $couponUse->coupon_id = $request->coupon_id;
                $couponUse->created_at = $timeStamp;
                $couponUse->updated_at = $timeStamp;
                $couponUse->save();
            }

            if ($paymentStatus === 'Paid') {
                $invoice = new AppointmentInvoiceModel();
                $invoice->patient_id = (int) $patient->id;
                $invoice->user_id = $userId;
                $invoice->clinic_id = $clinicId;
                $invoice->appointment_id = $appointment->id;
                $invoice->status = $paymentStatus;
                $invoice->total_amount = $amount;
                $invoice->invoice_date = $date;
                $invoice->created_at = $timeStamp;
                $invoice->updated_at = $timeStamp;
                $invoice->coupon_title = $request->coupon_title ?? null;
                $invoice->coupon_value = $request->coupon_value ?? null;
                $invoice->coupon_off_amount = $request->coupon_off_amount ?? null;
                $invoice->coupon_id = $request->coupon_id ?? null;
                $invoice->save();

                $invoiceItem = new AppointmentInvoiceItemModel();
                $invoiceItem->invoice_id = $invoice->id;
                $invoiceItem->description = $request->invoice_description ?? $appointmentType;
                $invoiceItem->quantity = 1;
                $invoiceItem->clinic_id = $clinicId;
                $invoiceItem->unit_price = $fee > 0 ? $fee : $amount;
                $invoiceItem->service_charge = $request->service_charge ?? 0;
                $invoiceItem->total_price = $request->unit_total_amount ?? $amount;
                $invoiceItem->unit_tax = $request->tax ?? 0;
                $invoiceItem->unit_tax_amount = $request->unit_tax_amount ?? 0;
                $invoiceItem->created_at = $timeStamp;
                $invoiceItem->updated_at = $timeStamp;
                $invoiceItem->save();

                if ($idPaymentType) {
                    $providerReference = $request->provider_reference
                        ?? ('APPT-' . $appointment->id . '-' . now()->timestamp);

                    $paymentId = $this->createPaymentForAppointment(
                        $appointment,
                        $userId,
                        $idPaymentType,
                        $paymentType->name,
                        $request->payment_provider ?? null,
                        $request->payment_method_code ?? null,
                        $providerReference,
                        $amount
                    );

                    $appointment->id_payment = $paymentId;
                    $appointment->save();
                }
            }

            DB::commit();

            $notificationCentralController = new NotificationCentralController();
            $notificationCentralController->sendAppointmentNotificationToUsers($appointment->id);

            return response()->json([
                'response' => 200,
                'status' => true,
                'message' => 'successfully',
                'message_detail' => 'First appointment created successfully.',
                'data' => $appointment,
                'id' => $appointment->id,
                'appointment_id' => $appointment->id,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'error ' . $e->getMessage(),
            ], 500);
        }
    }
}
