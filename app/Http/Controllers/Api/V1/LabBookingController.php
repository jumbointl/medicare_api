<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppointmentModel;
use App\Models\LabBookingModel;
use App\Models\LabTestCartModel;
use App\Models\AppointmentInvoiceModel;
use App\Models\AppointmentPaymentModel;
use App\Models\AppointmentStatusLogModel;
use App\Models\AppointmentInvoiceItemModel;
use App\Models\AllTransactionModel;
use App\Models\User;
use App\Models\LabBookingItemModel;
use App\Models\FamilyMembersModel;
use App\Models\PatientModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\V1\ZoomVideoCallController;
use App\Models\CouponUseModel;
use App\Http\Controllers\Api\V1\NotificationCentralController;
use App\Models\PatientClinicModel;

class LabBookingController extends Controller
{
    protected function resolvePaymentStatusByType(?int $idPaymentType): string
    {
        if (is_null($idPaymentType)) {
            return 'Unpaid';
        }

        return $idPaymentType >= 8000 ? 'Paid' : 'Unpaid';
    }

    function appointmentResch(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'id' => 'required',
            'date' => 'required',
        ]);

        if ($validator->fails()) {
            return response(["response" => 400], 400);
        }

        try {
            DB::beginTransaction();

            $dataModel = LabBookingModel::where("id", $request->id)->first();
            if ($dataModel == null) {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }

            $oldDate = $dataModel->date;
            $currentStatus = $dataModel->status;
            if ($currentStatus == "Rejected" || $currentStatus == "Cancelled") {
                DB::rollBack();
                return Helpers::errorResponse("Cannot update status");
            }

            $dataModel->status = 'Rescheduled';
            $dataModel->date = $request->date;
            $timeStamp = date("Y-m-d H:i:s");
            $dataModel->updated_at = $timeStamp;
            $qResponce = $dataModel->save();

            if (!$qResponce) {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }

            $appointmentData = DB::table("lab_booking")
                ->select('lab_booking.*', 'patients.user_id')
                ->join("patients", "patients.id", '=', 'lab_booking.lab_patient_id')
                ->join("pathologist", "pathologist.id", '=', 'lab_booking.pathology_id')
                ->where("lab_booking.id", "=", $request->id)
                ->first();

            $userId = $appointmentData->user_id;
            $patient_id = $appointmentData->lab_patient_id;
            if ($patient_id == null) {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }

            $dataASLModel = new AppointmentStatusLogModel;
            $dataASLModel->lab_booking_id = $request->id;
            $dataASLModel->user_id = $userId;
            $dataASLModel->status = "Rescheduled";
            $dataASLModel->patient_id = $patient_id;
            $dataASLModel->notes = "Lab Booking " . $oldDate . " rescheduled to " . $request->date;
            $dataASLModel->created_at = $timeStamp;
            $dataASLModel->updated_at = $timeStamp;
            $qResponceApp = $dataASLModel->save();

            if (!$qResponceApp) {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }

            DB::commit();

            $notificationCentralController = new NotificationCentralController();
            $notificationCentralController->sendLabRshNotificationToUsers($request->id, $oldDate);

            return Helpers::successResponse("successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("error $e");
        }
    }

    function updateStatusToPaid(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'lab_booking_id' => 'required',
            'payment_method' => 'required',
            'id_payment_type' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response(["response" => 400], 400);
        }

        try {
            DB::beginTransaction();

            $lab_booking_id = $request->lab_booking_id;
            $timeStamp = date("Y-m-d H:i:s");
            $idPaymentType = (int) $request->id_payment_type;
            $resolvedPaymentStatus = $this->resolvePaymentStatusByType($idPaymentType);

            $dataInvoiceModel = AppointmentInvoiceModel::where('lab_booking_id', $lab_booking_id)->first();

            if ($dataInvoiceModel == null) {
                throw new \Exception('Error');
            }

            $dataTXNModel = new AllTransactionModel;
            $dataTXNModel->amount = $dataInvoiceModel->total_amount;
            $dataTXNModel->user_id = $dataInvoiceModel->user_id;
            $dataTXNModel->patient_id = $dataInvoiceModel->patient_id;
            $dataTXNModel->pathologist_id = $dataInvoiceModel->pathology_id;
            $dataTXNModel->lab_booking_id = $dataInvoiceModel->lab_booking_id;
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
            $dataPaymentModel->invoice_id = $dataInvoiceModel->id;
            $dataPaymentModel->amount = $dataInvoiceModel->total_amount;
            $dataPaymentModel->payment_time_stamp = $timeStamp;
            $dataPaymentModel->pathology_id = $dataInvoiceModel->pathology_id;
            $dataPaymentModel->payment_method = $request->payment_method;
            $dataPaymentModel->created_at = $timeStamp;
            $dataPaymentModel->updated_at = $timeStamp;
            $qResponcePayment = $dataPaymentModel->save();

            $dataTXNModel->save();

            $dataInvoiceModel->status = $resolvedPaymentStatus;
            $resDataInvoiceModel = $dataInvoiceModel->save();
            if (!$resDataInvoiceModel) {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }

            DB::commit();
            return Helpers::successWithIdResponse("successfully", $lab_booking_id);
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("error");
        }
    }

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

            $dataModel = LabBookingModel::where("id", $request->id)->first();
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
                $appointmentData = DB::table("lab_booking")
                    ->select('lab_booking.*', 'patients.user_id')
                    ->join("patients", "patients.id", '=', 'lab_booking.lab_patient_id')
                    ->join("pathologist", "pathologist.id", '=', 'lab_booking.pathology_id')
                    ->where("lab_booking.id", "=", $request->id)
                    ->first();

                $userId = $appointmentData->user_id;
                $patient_id = $appointmentData->lab_patient_id;
                if ($patient_id == null) {
                    DB::rollBack();
                    return Helpers::errorResponse("error");
                }

                $dataASLModel = new AppointmentStatusLogModel;
                $dataASLModel->lab_booking_id = $request->id;
                $dataASLModel->user_id = $userId;
                $dataASLModel->status = $request->status;
                $dataASLModel->patient_id = $patient_id;
                $dataASLModel->created_at = $timeStamp;
                $dataASLModel->updated_at = $timeStamp;
                $qResponceApp = $dataASLModel->save();

                if (!$qResponceApp) {
                    DB::rollBack();
                    return Helpers::errorResponse("error");
                }

                DB::commit();

                $notificationCentralController = new NotificationCentralController();
                $notificationCentralController->sendLabAppointmentSatusChangeNotificationToUsers($request->id, $request->status);

                return Helpers::successResponse("successfully");
            } else {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("error $e");
        }
    }

    // add new data
    function addData(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'status' => 'required',
            'date' => 'required',
            'pathology_id' => 'required',
            'id_payment_type' => 'nullable|integer',
            'lab_test_ids' => 'required',
            'total_amount' => 'required'
        ]);

        if ($validator->fails()) {
            return response([
                "response" => 400,
                "errors" => $validator->errors()
            ], 400);
        }

        try {
            DB::beginTransaction();
            $timeStamp = date("Y-m-d H:i:s");
            $date = date("Y-m-d");

            if (!isset($request->family_member_id) && !isset($request->patient_id)) {
                DB::rollBack();
                return Helpers::errorResponse("No Family Member Or Patient Id");
            }

            $idPaymentType = isset($request->id_payment_type) ? (int) $request->id_payment_type : null;

            if (is_null($idPaymentType) && isset($request->payment_status)) {
                $resolvedPaymentStatus = $request->payment_status === 'Paid' ? 'Paid' : 'Unpaid';
            } else {
                $resolvedPaymentStatus = $this->resolvePaymentStatusByType($idPaymentType);
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
                    ->where('pathology_id', $request->pathology_id)
                    ->first();

                if ($dataPatientModelExists == null) {
                    $dataPatientModel = new PatientModel;
                    $dataPatientModel->f_name = $dataFamilyModel->f_name;
                    $dataPatientModel->l_name = $dataFamilyModel->l_name;
                    $dataPatientModel->phone = $dataFamilyModel->phone;
                    $dataPatientModel->user_id = $dataFamilyModel->user_id;
                    $dataPatientModel->isd_code = $dataFamilyModel->isd_code;
                    $dataPatientModel->dob = $dataFamilyModel->dob;
                    $dataPatientModel->pathology_id = $request->pathology_id;
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
            } else {
                $dataPatientModelExists = PatientModel::where('id', $request->patient_id)->first();
                if ($dataPatientModelExists == null) {
                    DB::rollBack();
                    return Helpers::errorResponse("error");
                }
            }

            $dataModel = new LabBookingModel;
            $dataModel->lab_patient_id = $patientId;
            $dataModel->status = $request->status;
            $dataModel->date = $request->date;
            $dataModel->prescription_id = $request->prescription_id ?? null;
            $dataModel->pathology_id = $request->pathology_id;
            $dataModel->source = $request->source;
            $dataModel->created_at = $timeStamp;
            $dataModel->updated_at = $timeStamp;
            $dataModel->save();

            $dataInvoiceModel = new AppointmentInvoiceModel;
            $dataInvoiceModel->patient_id = $patientId;
            $dataInvoiceModel->user_id = $request->user_id;
            $dataInvoiceModel->pathology_id = $request->pathology_id;
            $dataInvoiceModel->lab_booking_id = $dataModel->id;
            $dataInvoiceModel->status = $resolvedPaymentStatus;
            $dataInvoiceModel->total_amount = $request->total_amount;
            $dataInvoiceModel->invoice_date = $date;
            $dataInvoiceModel->created_at = $timeStamp;
            $dataInvoiceModel->updated_at = $timeStamp;
            $dataInvoiceModel->coupon_title = $request->coupon_title;
            $dataInvoiceModel->coupon_value = $request->coupon_value;
            $dataInvoiceModel->coupon_off_amount = $request->coupon_off_amount;
            $dataInvoiceModel->coupon_id = $request->coupon_id;
            $qResponceInvoice = $dataInvoiceModel->save();

            if (isset($request->payment_transaction_id)) {
                $dataTXNModel = new AllTransactionModel;
                $dataTXNModel->lab_booking_id = $dataModel->id;
                $dataTXNModel->payment_transaction_id = $request->payment_transaction_id;
                $dataTXNModel->amount = $request->total_amount;
                $dataTXNModel->user_id = $request->user_id;
                $dataTXNModel->patient_id = $patientId;
                $dataTXNModel->pathologist_id = $request->pathology_id;
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

                $dataPaymentModel = new AppointmentPaymentModel;
                $dataPaymentModel->txn_id = $dataTXNModel->id;
                $dataPaymentModel->invoice_id = $dataInvoiceModel->id;
                $dataPaymentModel->amount = $request->total_amount;
                $dataPaymentModel->payment_time_stamp = $timeStamp;
                $dataPaymentModel->pathology_id = $request->pathology_id;
                $dataPaymentModel->payment_method = $request->payment_method;
                $dataPaymentModel->created_at = $timeStamp;
                $dataPaymentModel->updated_at = $timeStamp;
                $qResponcePayment = $dataPaymentModel->save();
            }

            foreach ($request->lab_test_ids as $labTestId) {
                try {
                    $dataModelLabBIM = new LabBookingItemModel;
                    $dataModelLabBIM->lab_booking_id = $dataModel->id;
                    $dataModelLabBIM->lab_test_id = $labTestId['lab_id'];
                    $dataModelLabBIM->created_at = $timeStamp;
                    $dataModelLabBIM->updated_at = $timeStamp;
                    $qResponce = $dataModelLabBIM->save();

                    if ($qResponce) {
                        if (isset($request->coupon_id)) {
                            $dataCouponUseModel = new CouponUseModel;
                            $dataCouponUseModel->user_id = $request->user_id;
                            $dataCouponUseModel->pathology_id = $request->pathology_id;
                            $dataCouponUseModel->lab_booking_id = $dataModel->id;
                            $dataCouponUseModel->coupon_id = $request->coupon_id;
                            $dataCouponUseModel->created_at = $timeStamp;
                            $dataCouponUseModel->updated_at = $timeStamp;
                            $dataCouponUseModel->save();

                            if (!$dataCouponUseModel) {
                                DB::rollBack();
                                throw new \Exception('Error');
                            }
                        }

                        $dataInvoiceItemModel = new AppointmentInvoiceItemModel;
                        $dataInvoiceItemModel->invoice_id = $dataInvoiceModel->id;
                        $dataInvoiceItemModel->description = $labTestId['test_title'];
                        $dataInvoiceItemModel->lab_booking_item_id = $dataModelLabBIM->id;
                        $dataInvoiceItemModel->quantity = 1;
                        $dataInvoiceItemModel->pathology_id = $request->pathology_id;
                        $dataInvoiceItemModel->unit_price = $labTestId['fee'];
                        $dataInvoiceItemModel->total_price = $labTestId['total_amount'];
                        $dataInvoiceItemModel->created_at = $timeStamp;
                        $dataInvoiceItemModel->updated_at = $timeStamp;
                        $qResponceInvoiceItem = $dataInvoiceItemModel->save();

                        LabTestCartModel::where('lab_test_id', $labTestId['lab_id'])
                            ->where('user_id', $request->user_id)
                            ->delete();
                    } else {
                        DB::rollBack();
                        throw new \Exception('Error');
                    }
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw new \Exception("Error: {$e->getMessage()}");
                }
            }

            DB::commit();

            $notificationCentralController = new NotificationCentralController();
            $notificationCentralController->sendLabNotificationToUsers($dataModel->id);

            return Helpers::successWithIdResponse("successfully", $dataModel->id);
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("error $e");
        }
    }

    public function getData(Request $request)
    {
        $query = DB::table("lab_booking")
            ->select(
                'lab_booking.*',
                'patients.user_id',
                'patients.f_name as patient_f_name',
                'patients.l_name as patient_l_name',
                'patients.phone as patient_phone',
                'pathologist.title as pathology_title',
                'pathologist.image as pathology_image',
                'appointment_invoice.status as payment_status'
            )
            ->Leftjoin('patients', 'patients.id', '=', 'lab_booking.lab_patient_id')
            ->Leftjoin('pathologist', 'pathologist.id', '=', 'lab_booking.pathology_id')
            ->Leftjoin('appointment_invoice', 'appointment_invoice.lab_booking_id', '=', 'lab_booking.id')
            ->orderBy("lab_booking.date", "DESC");

        if ($request->filled('start_date')) {
            $query->whereDate('lab_booking.date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('lab_booking.date', '<=', $request->end_date);
        }

        if ($request->filled('status')) {
            $status = explode(',', $request->status);
            $query->whereIn('lab_booking.status', $status);
        }

        if ($request->filled('user_id')) {
            $query->where('patients.user_id', '=', $request->user_id);
        }

        if ($request->filled('patient_id')) {
            $query->where('lab_booking.lab_patient_id', '=', $request->patient_id);
        }

        if ($request->filled('pathology_id')) {
            $query->where('lab_booking.pathology_id', '=', $request->pathology_id);
        }

        if ($request->filled('current_cancel_req_status')) {
            $query->where('lab_booking.current_cancel_req_status', '=', $request->current_cancel_req_status);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('lab_booking.lab_patient_id', 'like', "%$search%")
                    ->orWhereRaw('CONCAT(patients.f_name, " ", patients.l_name) LIKE ?', ["%$search%"])
                    ->orWhere('patients.phone', 'like', "%$search%")
                    ->orWhere('lab_booking.id', 'like', "%$search%")
                    ->orWhere('lab_booking.status', 'like', "%$search%")
                    ->orWhere('lab_booking.date', 'like', "%$search%")
                    ->orWhere('appointment_invoice.status', 'like', "%$search%")
                    ->orWhere('lab_booking.current_cancel_req_status', 'like', "%$search%");
            });
        }

        $total_record = $query->count();

        if ($request->filled(['start', 'end'])) {
            $start = $request->start;
            $limit = $request->end - $start;
            $query->skip($start)->take($limit);
        }

        $data = $query->get();

        return response()->json([
            "response" => 200,
            "total_record" => $total_record,
            "data" => $data
        ], 200);
    }

    function getDataById($id)
    {
        $data = DB::table("lab_booking")
            ->select(
                'lab_booking.*',
                'patients.mrn as patient_mrn',
                'patients.f_name as patient_f_name',
                'patients.l_name as patient_l_name',
                'patients.l_name as patient_l_name',
                'patients.mrn as patient_mrn',
                'patients.phone as patient_phone',
                'patients.gender as patient_gender',
                'patients.user_id',
                'pathologist.title as pathology_title',
                "pathologist.address as pathology_address",
                'pathologist.image as pathology_thumb_image',
                'pathologist.phone as pathology_phone',
                'pathologist.phone_second as pathology_phone_second',
                'pathologist.whatsapp as pathology_whatsapp',
                'pathologist.email as pathology_email',
                'pathologist.latitude as pathology_latitude',
                'pathologist.longitude as pathology_longitude',
                'pathologist.is_show_contact_box',
                'pathologist.title as pathology_title',
                'pathologist.image as pathology_image',
                'appointment_invoice.status as payment_status'
            )
            ->Join('patients', 'patients.id', '=', 'lab_booking.lab_patient_id')
            ->join('pathologist', 'pathologist.id', '=', 'lab_booking.pathology_id')
            ->join('appointment_invoice', 'appointment_invoice.lab_booking_id', '=', 'lab_booking.id')
            ->where('lab_booking.id', '=', $id)
            ->first();

        if ($data != null) {
            $data->lab_test_items = DB::table("lab_booking_items")
                ->select(
                    'lab_booking_items.*',
                    'pathology_test.title',
                    'pathology_test.sub_title',
                    'pathology_test.report_day',
                    'pathology_test.amount'
                )
                ->join('pathology_test', 'pathology_test.id', '=', 'lab_booking_items.lab_test_id')
                ->where('lab_booking_items.lab_booking_id', '=', $data->id)
                ->get();

            foreach ($data->lab_test_items as $item) {
                $item->sub_tests = DB::table("pathology_subtest")
                    ->where("test_id", $item->lab_test_id)
                    ->get();
            }

            $dataDR = DB::table("lab_review")
                ->select('lab_review.*')
                ->where("lab_review.path_id", "=", $data->pathology_id)
                ->get();

            $totalReviewPoints = $dataDR->sum('points');
            $numberOfReviews = $dataDR->count();
            $averageRating = $numberOfReviews > 0 ? number_format($totalReviewPoints / $numberOfReviews, 2) : '0.00';

            $data->total_review_points = $totalReviewPoints;
            $data->number_of_reviews = $numberOfReviews;
            $data->average_rating = $averageRating;

            $dataDApp = DB::table("lab_booking")
                ->select('lab_booking.*')
                ->where("lab_booking.pathology_id", "=", $data->pathology_id)
                ->get();

            $data->total_booking_done = count($dataDApp);
        }

        $response = [
            "response" => 200,
            'data' => $data,
        ];

        return response($response, 200);
    }
}