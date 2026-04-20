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


class AppointmentController extends Controller
{
    private AgoraJoinDataService $agoraJoinDataService;

    public function __construct(AgoraJoinDataService $agoraJoinDataService)
    {
        $this->agoraJoinDataService = $agoraJoinDataService;
    }

    protected function resolveAppointmentPaymentStatusByType(?int $idPaymentType): string
    {
        if (is_null($idPaymentType)) {
            return 'Unpaid';
        }

        return $idPaymentType >= 8000 ? 'Paid' : 'Unpaid';
    }

    protected function isPaidByType(?int $idPaymentType): bool
    {
        return !is_null($idPaymentType) && $idPaymentType >= 8000;
    }

    // update status to paid/unpaid according to payment type
    function updateStatusToPaid(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'appointment_id' => 'required',
            'payment_method' => 'required',
            'id_payment_type' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response(["response" => 400], 400);
        }

        try {
            DB::beginTransaction();

            $appointment_id = $request->appointment_id;
            $timeStamp = date("Y-m-d H:i:s");
            $date = date("Y-m-d");
            $idPaymentType = (int) $request->id_payment_type;
            $resolvedPaymentStatus = $this->resolveAppointmentPaymentStatusByType($idPaymentType);

            $dataInvoiceModel = AppointmentInvoiceModel::where('appointment_id', $appointment_id)->first();

            if ($dataInvoiceModel == null) {
                throw new \Exception('Error');
            }

            $dataTXNModel = new AllTransactionModel;
            $dataTXNModel->amount  = $dataInvoiceModel->total_amount;
            $dataTXNModel->user_id  = $dataInvoiceModel->user_id;
            $dataTXNModel->patient_id  = $dataInvoiceModel->patient_id;
            $dataTXNModel->clinic_id  = $dataInvoiceModel->clinic_id;
            $dataTXNModel->transaction_type = "Debited";
            $dataTXNModel->created_at = $timeStamp;
            $dataTXNModel->updated_at = $timeStamp;

            $qResponceTxn = $dataTXNModel->save();
            if (!$qResponceTxn) {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }

            $dataPaymentModel = new AppointmentPaymentModel;
            $dataPaymentModel->txn_id = $dataTXNModel->id;
            $dataPaymentModel->invoice_id   = $dataInvoiceModel->id;
            $dataPaymentModel->amount   = $dataInvoiceModel->total_amount;
            $dataPaymentModel->payment_time_stamp   = $timeStamp;
            $dataPaymentModel->clinic_id  = $dataInvoiceModel->clinic_id;
            $dataPaymentModel->payment_method   = $request->payment_method;
            $dataPaymentModel->created_at = $timeStamp;
            $dataPaymentModel->updated_at = $timeStamp;
            $qResponcePayment = $dataPaymentModel->save();

            $dataTXNModel->appointment_id = $appointment_id;
            $dataTXNModel->save();

            $dataInvoiceModel->status = $resolvedPaymentStatus;
            $resDataInvoiceModel = $dataInvoiceModel->save();
            if (!$resDataInvoiceModel) {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }

            $appModel = AppointmentModel::where("id", $appointment_id)->first();
            $appModel->payment_status = $resolvedPaymentStatus;
            $resAppModel = $appModel->save();
            if (!$resAppModel) {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }

            DB::commit();
            return Helpers::successWithIdResponse("successfully", $appointment_id);
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("error");
        }
    }

    // add new data
    function addData(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'status' => 'required',
            'date' => 'required',
            'time_slots' => 'required',
            'doct_id' => 'required',
            'dept_id' => 'required',
            'type' => 'required',
            'id_payment_type' => 'nullable|integer',
            'total_amount' => 'required',
            'fee' => 'required',
            'invoice_description' => 'required'
        ]);

        if ($validator->fails()) {
            return response(["response" => 400], 400);
        }

        try {
            DB::beginTransaction();
            $timeStamp = date("Y-m-d H:i:s");
            $date = date("Y-m-d");

            if (isset($request->family_member_id) && isset($request->patient_id)) {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }

            $doctModel = DB::table("doctors")
                ->select('doctors.clinic_id', 'doctors.video_provider')
                ->where('doctors.user_id', '=', $request->doct_id)
                ->first();

            $clinicId = $doctModel->clinic_id ?? null;
            $doctorVideoProvider = strtolower(trim($doctModel->video_provider ?? 'agora'));

            if ($doctModel == null || $clinicId == null) {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }

            $idPaymentType = isset($request->id_payment_type) ? (int) $request->id_payment_type : null;

            if (is_null($idPaymentType) && isset($request->payment_status)) {
                $resolvedPaymentStatus = $request->payment_status === 'Paid' ? 'Paid' : 'Unpaid';
            } else {
                $resolvedPaymentStatus = $this->resolveAppointmentPaymentStatusByType($idPaymentType);
            }

            $patientId = $request->patient_id;

            if (!isset($request->patient_id)) {
                if (!isset($request->family_member_id)) {
                    DB::rollBack();
                    return Helpers::errorResponse("error");
                }

                $dataFamilyModel = FamilyMembersModel::where('id', $request->family_member_id)->first();
                if ($dataFamilyModel == null) {
                    DB::rollBack();
                    return Helpers::errorResponse("error");
                }

                $dataPatientModelExists = PatientModel::where('f_name', $dataFamilyModel->f_name)
                    ->where('l_name', $dataFamilyModel->l_name)
                    ->where('phone', $dataFamilyModel->phone)
                    ->where('clinic_id', $clinicId)
                    ->first();

                if ($dataPatientModelExists == null) {
                    $dataPatientModel = new PatientModel;
                    $dataPatientModel->f_name = $dataFamilyModel->f_name;
                    $dataPatientModel->l_name = $dataFamilyModel->l_name;
                    $dataPatientModel->phone = $dataFamilyModel->phone;
                    $dataPatientModel->user_id = $dataFamilyModel->user_id;
                    $dataPatientModel->isd_code = $dataFamilyModel->isd_code;
                    $dataPatientModel->dob = $dataFamilyModel->dob;
                    $dataPatientModel->clinic_id = $clinicId;
                    $dataPatientModel->gender = $dataFamilyModel->gender;

                    $resPatient = $dataPatientModel->save();
                    $dataPatientModel->mrn = $dataPatientModel->id;
                    $dataPatientModel->save();

                    if (!$resPatient) {
                        DB::rollBack();
                        return Helpers::errorResponse("error");
                    }

                    $patientId = $dataPatientModel->id;
                } else {
                    $patientId = $dataPatientModelExists->id;
                }
            }

            if (isset($request->payment_transaction_id)) {
                $dataTXNModel = new AllTransactionModel;
                $dataTXNModel->payment_transaction_id = $request->payment_transaction_id;
                $dataTXNModel->amount  = $request->total_amount;
                $dataTXNModel->user_id  = $request->user_id;
                $dataTXNModel->patient_id  = $patientId;
                $dataTXNModel->clinic_id = $clinicId;
                $dataTXNModel->transaction_type = "Debited";
                $dataTXNModel->created_at = $timeStamp;
                $dataTXNModel->updated_at = $timeStamp;
                $dataTXNModel->is_wallet_txn = $request->is_wallet_txn ?? 0;

                $qResponceTxn = $dataTXNModel->save();
                if (!$qResponceTxn) {
                    DB::rollBack();
                    return Helpers::errorResponse("error");
                }

                if ($request->is_wallet_txn) {
                    $userModel = User::where("id", $request->user_id)->first();
                    if (!$userModel) {
                        DB::rollBack();
                        return Helpers::errorResponse("error");
                    }

                    $userOldAmount = $userModel->wallet_amount ?? 0;
                    $deductAmount = $request->total_amount;
                    if ($userOldAmount < $deductAmount) {
                        DB::rollBack();
                        return Helpers::errorResponse("Iinsufficient amount in wallet");
                    }

                    $userNewAmount = $userOldAmount - $deductAmount;
                    $userModel->wallet_amount = $userNewAmount;
                    $qResponceWU = $userModel->save();
                    if (!$qResponceWU) {
                        DB::rollBack();
                        return Helpers::errorResponse("error");
                    }

                    $dataTXNModel->last_wallet_amount = $userOldAmount;
                    $dataTXNModel->new_wallet_amount = $userNewAmount;
                    $qResponceTxnWalletUpdate = $dataTXNModel->save();
                    if (!$qResponceTxnWalletUpdate) {
                        DB::rollBack();
                        return Helpers::errorResponse("error");
                    }
                }
            }

            $dataModel = new AppointmentModel;
            $durationMinutes = (int) ($request->duration_minutes ?? 0);

            if ($durationMinutes <= 0) {
                $dayName = date('l', strtotime($request->date));

                $slotConfig = DB::table('time_slots')
                    ->where('doct_id', $request->doct_id)
                    ->where('day', $dayName)
                    ->first();

                $durationMinutes = (int) ($slotConfig->time_duration ?? 15);
            }

            $dataModel->patient_id = $patientId;
            $dataModel->status = $request->status;
            $dataModel->date = $request->date;
            $dataModel->time_slots = $request->time_slots;
            $dataModel->duration_minutes = $durationMinutes;
            $dataModel->doct_id = $request->doct_id;
            $dataModel->dept_id = $request->dept_id;
            $dataModel->clinic_id = $clinicId;
            $dataModel->type = $request->type;
            $dataModel->source = $request->source;
            $dataModel->payment_status = $resolvedPaymentStatus;

            if ($request->type === "Video Consultant") {
                $dataModel->video_provider = in_array($doctorVideoProvider, ['google', 'agora'], true)
                    ? $doctorVideoProvider
                    : 'agora';
            } else {
                $dataModel->video_provider = null;
                $dataModel->meeting_id = null;
                $dataModel->meeting_link = null;
            }

            if (isset($request->meeting_id)) {
                $dataModel->meeting_id = $request->meeting_id;
            }
            if (isset($request->meeting_link)) {
                $dataModel->meeting_link = $request->meeting_link;
            }

            $dataModel->created_at = $timeStamp;
            $dataModel->updated_at = $timeStamp;

            $qResponce = $dataModel->save();

            if ($qResponce) {
                if (isset($request->coupon_id)) {
                    $dataCouponUseModel = new CouponUseModel;
                    $dataCouponUseModel->user_id = $request->user_id;
                    $dataCouponUseModel->clinic_id = $clinicId;
                    $dataCouponUseModel->appointment_id  =  $dataModel->id;
                    $dataCouponUseModel->coupon_id   = $request->coupon_id;
                    $dataCouponUseModel->created_at = now();
                    $dataCouponUseModel->updated_at = now();
                    $dataCouponUseModel->save();

                    if (!$dataCouponUseModel) {
                        throw new \Exception('Error');
                    }
                }

                $dataInvoiceModel = new AppointmentInvoiceModel;
                $dataInvoiceModel->patient_id = $patientId;
                $dataInvoiceModel->user_id = $request->user_id;
                $dataInvoiceModel->clinic_id = $clinicId;
                $dataInvoiceModel->appointment_id  = $dataModel->id;
                $dataInvoiceModel->status = $resolvedPaymentStatus;
                $dataInvoiceModel->total_amount  = $request->total_amount;
                $dataInvoiceModel->invoice_date = $date;
                $dataInvoiceModel->created_at = $timeStamp;
                $dataInvoiceModel->updated_at = $timeStamp;
                $dataInvoiceModel->coupon_title = $request->coupon_title;
                $dataInvoiceModel->coupon_value = $request->coupon_value;
                $dataInvoiceModel->coupon_off_amount = $request->coupon_off_amount;
                $dataInvoiceModel->coupon_id = $request->coupon_id;
                $qResponceInvoice = $dataInvoiceModel->save();

                if ($qResponceInvoice) {
                    $dataInvoiceItemModel = new AppointmentInvoiceItemModel;
                    $dataInvoiceItemModel->invoice_id = $dataInvoiceModel->id;
                    $dataInvoiceItemModel->description  = $request->invoice_description;
                    $dataInvoiceItemModel->quantity = 1;
                    $dataInvoiceItemModel->clinic_id = $clinicId;
                    $dataInvoiceItemModel->unit_price  = $request->fee;
                    $dataInvoiceItemModel->service_charge =  $request->service_charge ?? 0;
                    $dataInvoiceItemModel->total_price = $request->unit_total_amount;
                    $dataInvoiceItemModel->unit_tax  = $request->tax ?? 0;
                    $dataInvoiceItemModel->unit_tax_amount  = $request->unit_tax_amount ?? 0;
                    $dataInvoiceItemModel->created_at = $timeStamp;
                    $dataInvoiceItemModel->updated_at = $timeStamp;

                    $qResponceInvoiceItem = $dataInvoiceItemModel->save();

                    if ($qResponceInvoiceItem) {
                        if (isset($request->payment_transaction_id)) {
                            $dataPaymentModel = new AppointmentPaymentModel;
                            $dataPaymentModel->txn_id = $dataTXNModel->id;
                            $dataPaymentModel->invoice_id   = $dataInvoiceModel->id;
                            $dataPaymentModel->amount   = $request->total_amount;
                            $dataPaymentModel->payment_time_stamp   = $timeStamp;
                            $dataPaymentModel->clinic_id = $clinicId;
                            $dataPaymentModel->payment_method   = $request->payment_method;
                            $dataPaymentModel->created_at = $timeStamp;
                            $dataPaymentModel->updated_at = $timeStamp;
                            $qResponcePayment = $dataPaymentModel->save();
                        }

                        if (isset($request->payment_transaction_id)) {
                            $dataTXNModel->appointment_id = $dataModel->id;
                            $dataTXNModel->save();
                        }

                        DB::commit();

                        if ($request->type == "Video Consultant" && $dataModel->video_provider === 'zoom') {
                            $zoomController = new ZoomVideoCallController();
                            $zoomController->createMeeting($dataModel->id, $dataModel->date, $dataModel->time_slots);
                        }

                        $notificationCentralController = new NotificationCentralController();
                        $notificationCentralController->sendAppointmentNotificationToUsers($dataModel->id);

                        return Helpers::successWithIdResponse("successfully", $dataModel->id);
                    } else {
                        throw new \Exception('Error');
                    }
                } else {
                    throw new \Exception('Error');
                }
            } else {
                throw new \Exception('Error');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("error $e");
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
    function getBookedTimeSlotsByDoctIdAndDateAndTpe(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'date' => 'required',
            'doct_id' => 'required',
            'type' => 'required'
        ]);

        if ($validator->fails()) {
            return response(["response" => 400], 400);
        }

        $data = DB::table("appointments")
            ->select(
                DB::raw('TIME_FORMAT(appointments.time_slots, "%H:%i") as time_slots'),
                'appointments.date',
                'appointments.type',
                'appointments.id as appointment_id'
            )
            ->where("appointments.status", "!=", 'Rejected')
            ->where("appointments.status", "!=", 'Completed')
            ->where("appointments.status", "!=", 'Cancelled')
            ->where("appointments.date", "=", $request->date)
            ->where("appointments.type", "=", $request->type)
            ->where("appointments.doct_id", "=", $request->doct_id)
            ->get();

        $response = [
            "response" => 200,
            'data' => $data,
        ];

        return response($response, 200);
    }

    // get data by id
    function getDataById($id)
    {
        $data = DB::table("appointments")
            ->select(
                'appointments.*',
                'patients.user_id',
                'patients.mrn as patient_mrn',
                'patients.f_name as patient_f_name',
                'patients.l_name as patient_l_name',
                'patients.phone as patient_phone',
                'patients.gender as patient_gender',
                'department.title as dept_title',
                'users.f_name as doct_f_name',
                'users.l_name as doct_l_name',
                "users.image as doct_image",
                "doctors.specialization as doct_specialization",
                'clinics.title as clinic_title',
                "clinics.address as clinics_address",
                'clinics.image as clinic_thumb_image',
                'clinics.phone as clinic_phone',
                'clinics.phone_second as clinic_phone_second',
                'clinics.whatsapp as clinic_whatsapp',
                'clinics.email as clinic_email',
                'clinics.latitude as clinic_latitude',
                'clinics.longitude as clinic_longitude',
                'clinics.ambulance_number as clinic_ambulance_number',
                'clinics.ambulance_btn_enable as clinic_ambulance_btn_enable',
            )
            ->Join('patients', 'patients.id', '=', 'appointments.patient_id')
            ->Join('department', 'department.id', '=', 'appointments.dept_id')
            ->Join('users', 'users.id', '=', 'appointments.doct_id')
            ->join('clinics', 'clinics.id', '=', 'appointments.clinic_id')
            ->LeftJoin('doctors', 'doctors.user_id', '=', 'appointments.doct_id')
            ->where('appointments.id', '=', $id)
            ->first();

        if ($data != null) {
            $dataDR = DB::table("doctors_review")
                ->select('doctors_review.*')
                ->where("doctors_review.doctor_id", "=", $data->doct_id)
                ->get();

            $totalReviewPoints = $dataDR->sum('points');
            $numberOfReviews = $dataDR->count();
            $averageRating = $numberOfReviews > 0 ? number_format($totalReviewPoints / $numberOfReviews, 2) : '0.00';

            $data->total_review_points = $totalReviewPoints;
            $data->number_of_reviews = $numberOfReviews;
            $data->average_rating = $averageRating;

            $dataDApp = DB::table("appointments")
                ->select('appointments.*')
                ->where("appointments.doct_id", "=", $data->doct_id)
                ->get();

            $data->total_appointment_done = count($dataDApp);
        }

        if ($data != null) {
            $data = $this->appendVideoFlags($data);
        }

        $response = [
            "response" => 200,
            'data' => $data,
        ];

        return response($response, 200);
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
        $query = DB::table("appointments")
            ->select(
                'appointments.*',
                'patients.user_id',
                'patients.f_name as patient_f_name',
                'patients.l_name as patient_l_name',
                'patients.phone as patient_phone',
                'department.title as dept_title',
                'users.f_name as doct_f_name',
                'users.l_name as doct_l_name',
                'users.image as doct_image',
                'doctors.specialization as doct_specialization'
            )
            ->join('patients', 'patients.id', '=', 'appointments.patient_id')
            ->join('department', 'department.id', '=', 'appointments.dept_id')
            ->join('users', 'users.id', '=', 'appointments.doct_id')
            ->leftJoin('doctors', 'doctors.user_id', '=', 'appointments.doct_id')
            ->orderBy("appointments.date", "DESC")
            ->orderBy("appointments.time_slots", "DESC");

        if ($request->filled('start_date')) {
            $query->whereDate('appointments.date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('appointments.date', '<=', $request->end_date);
        }

        if ($request->filled('status')) {
            $status = explode(',', $request->status);
            $query->whereIn('appointments.status', $status);
        }

        if ($request->filled('user_id')) {
            $query->where('patients.user_id', '=', $request->user_id);
        }

        if ($request->filled('doctor_id')) {
            $query->where('appointments.doct_id', '=', $request->doctor_id);
        }

        if ($request->filled('clinic_id')) {
            $query->where('appointments.clinic_id', '=', $request->clinic_id);
        }

        if ($request->filled('patient_id')) {
            $query->where('appointments.patient_id', '=', $request->patient_id);
        }

        if ($request->filled('type')) {
            $query->where('appointments.type', '=', $request->type);
        }

        if ($request->filled('current_cancel_req_status')) {
            $query->where('appointments.current_cancel_req_status', '=', $request->current_cancel_req_status);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('patients.user_id', 'like', "%$search%")
                    ->orWhereRaw('CONCAT(patients.f_name, " ", patients.l_name) LIKE ?', ["%$search%"])
                    ->orWhereRaw('CONCAT(users.f_name, " ", users.l_name) LIKE ?', ["%$search%"])
                    ->orWhere('patients.phone', 'like', "%$search%")
                    ->orWhere('department.title', 'like', "%$search%")
                    ->orWhere('appointments.id', 'like', "%$search%")
                    ->orWhere('appointments.status', 'like', "%$search%")
                    ->orWhere('appointments.time_slots', 'like', "%$search%")
                    ->orWhere('appointments.date', 'like', "%$search%")
                    ->orWhere('appointments.type', 'like', "%$search%")
                    ->orWhere('appointments.meeting_id', 'like', "%$search%")
                    ->orWhere('appointment_invoice.status', 'like', "%$search%")
                    ->orWhere('appointments.current_cancel_req_status', 'like', "%$search%")
                    ->orWhere('doctors.specialization', 'like', "%$search%");
            });
        }

        $total_record = $query->count();

        if ($request->filled(['start', 'end'])) {
            $start = $request->start;
            $limit = $request->end - $start;
            $query->skip($start)->take($limit);
        }

        $data = $query->get();
        foreach ($data as $item) {
            $this->appendVideoFlags($item);
        }

        return response()->json([
            "response" => 200,
            "total_record" => $total_record,
            "data" => $data,
        ], 200);
    }

    public function getVideoJoinData(Request $request, $id)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            $appointment = AppointmentModel::find((int) $id);

            if (!$appointment) {
                return response()->json([
                    'status' => false,
                    'message' => 'Appointment not found.',
                ], 404);
            }

            $patient = PatientModel::find($appointment->patient_id);

            $isPatientOwner = (int) ($patient->user_id ?? 0) === (int) $user->id;
            $isDoctorOwner = (int) $appointment->doct_id === (int) $user->id;

            if (!$isPatientOwner && !$isDoctorOwner) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have access to this appointment.',
                ], 403);
            }

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

            $provider = strtolower(trim((string) ($appointment->video_provider ?? 'agora')));
            $meetingLink = trim((string) ($appointment->meeting_link ?? ''));

            $isGoogleProvider = in_array($provider, ['google', 'google_meet', 'googlemeet', 'meet'], true);

            if ($isGoogleProvider) {
                if ($meetingLink !== '') {
                    if ((int) $appointment->patient_id === (int) $user->id) {
                        $appointment->patient_joined_at = $appointment->patient_joined_at ?? now();
                        $appointment->save();
                    }

                    return response()->json([
                        'status' => true,
                        'doctor_joined' => !empty($appointment->doctor_joined_at),
                        'waiting_for_doctor' => false,
                        'data' => [
                            'provider' => 'google',
                            'meeting_id' => $appointment->meeting_id,
                            'meeting_link' => $appointment->meeting_link,
                            'google_calendar_event_id' => $appointment->google_calendar_event_id,
                            'video_provider' => $appointment->video_provider,
                            'doctor_joined_at' => $appointment->doctor_joined_at,
                            'patient_joined_at' => $appointment->patient_joined_at,
                        ],
                    ], 200);
                }

                return response()->json([
                    'status' => false,
                    'doctor_joined' => false,
                    'waiting_for_doctor' => true,
                    'message' => 'Doctor has not opened the meeting yet',
                    'data' => [
                        'provider' => 'google',
                        'meeting_id' => null,
                        'meeting_link' => null,
                        'google_calendar_event_id' => $appointment->google_calendar_event_id,
                        'video_provider' => $appointment->video_provider,
                    ],
                ], 200);
            }

            $agoraResult = $this->agoraJoinDataService->buildJoinData(
                $appointment,
                (int) $user->id
            );

            if ((int) $appointment->patient_id === (int) $user->id) {
                $appointment->patient_joined_at = $appointment->patient_joined_at ?? now();
                $appointment->save();
            }

            return response()->json([
                'status' => true,
                'doctor_joined' => !empty($appointment->doctor_joined_at),
                'waiting_for_doctor' => false,
                'data' => $agoraResult['data'],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('AppointmentController@getVideoJoinData failed', [
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

    private function createGoogleMeetForAppointment($appointment, $user): array
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

        $start = new \DateTime(
            trim($appointment->date . ' ' . $appointment->time_slots),
            new \DateTimeZone($timeZone)
        );

        $durationMinutes = (int) ($appointment->duration_minutes ?? 15);
        if ($durationMinutes <= 0) {
            $durationMinutes = 15;
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
