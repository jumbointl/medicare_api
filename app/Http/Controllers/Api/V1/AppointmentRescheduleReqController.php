<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppointmentModel;
use App\Models\AppointmentRescheduleReqModel;
use App\Models\AppointmentStatusLogModel;
use App\CentralLogics\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AppointmentRescheduleReqController extends Controller
{
    public function addRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appointment_id' => 'required',
            'requested_date' => 'required|date',
            'requested_time_slots' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response(["response" => 400], 400);
        }

        try {
            DB::beginTransaction();
            $timeStamp = date("Y-m-d H:i:s");

            $appointmentModel = AppointmentModel::where('id', $request->appointment_id)->first();
            if ($appointmentModel == null) {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }
            if (in_array($appointmentModel->status, ['Cancelled', 'Rejected', 'Visited', 'Completed'])) {
                DB::rollBack();
                return Helpers::errorResponse("Cannot request reschedule on this appointment");
            }

            $existingInitiated = AppointmentRescheduleReqModel::where('appointment_id', $request->appointment_id)
                ->where('status', 'Initiated')
                ->first();
            if ($existingInitiated) {
                DB::rollBack();
                return Helpers::errorResponse("Already requested");
            }

            $reschReq = new AppointmentRescheduleReqModel;
            $reschReq->appointment_id = $request->appointment_id;
            $reschReq->status = 'Initiated';
            $reschReq->requested_date = $request->requested_date;
            $reschReq->requested_time_slots = $request->requested_time_slots;
            if (isset($request->notes)) {
                $reschReq->notes = $request->notes;
            }
            $reschReq->created_at = $timeStamp;
            $reschReq->updated_at = $timeStamp;
            $reschReq->save();

            $appointmentData = DB::table('appointments')
                ->select('appointments.*', 'patients.user_id')
                ->join('patients', 'patients.id', '=', 'appointments.patient_id')
                ->where('appointments.id', $request->appointment_id)
                ->first();

            $logModel = new AppointmentStatusLogModel;
            $logModel->appointment_id = $request->appointment_id;
            $logModel->user_id = $appointmentData->user_id ?? null;
            $logModel->patient_id = $appointmentData->patient_id ?? null;
            $logModel->clinic_id = $appointmentModel->clinic_id;
            $logModel->status = 'RescheduleRequested';
            $logModel->notes = 'Reschedule requested to ' . $request->requested_date . ' ' . $request->requested_time_slots;
            $logModel->created_at = $timeStamp;
            $logModel->updated_at = $timeStamp;
            $logModel->save();

            DB::commit();

            (new NotificationCentralController())->sendRescheduleRequestedNotification(
                $request->appointment_id,
                $request->requested_date,
                $request->requested_time_slots,
                $request->notes ?? null
            );

            return Helpers::successWithIdResponse('successfully', $reschReq->id);
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("error");
        }
    }

    public function approve(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return response(["response" => 400], 400);
        }

        try {
            DB::beginTransaction();
            $timeStamp = date("Y-m-d H:i:s");

            $reschReq = AppointmentRescheduleReqModel::where('id', $request->id)->first();
            if ($reschReq == null) {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }
            if ($reschReq->status !== 'Initiated') {
                DB::rollBack();
                return Helpers::errorResponse("Request already reviewed");
            }

            $appointmentModel = AppointmentModel::where('id', $reschReq->appointment_id)->first();
            if ($appointmentModel == null) {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }
            if (in_array($appointmentModel->status, ['Cancelled', 'Rejected', 'Visited', 'Completed'])) {
                DB::rollBack();
                return Helpers::errorResponse("Cannot reschedule this appointment");
            }

            $oldDate = $appointmentModel->date;
            $oldTime = $appointmentModel->time_slots;

            $appointmentModel->status = 'Rescheduled';
            $appointmentModel->date = $reschReq->requested_date;
            $appointmentModel->time_slots = $reschReq->requested_time_slots;
            $appointmentModel->updated_at = $timeStamp;
            $appointmentModel->save();

            $reschReq->status = 'Approved';
            $reschReq->reviewed_by_user_id = $request->reviewed_by_user_id ?? ($request->user()->id ?? null);
            $reschReq->reviewed_at = $timeStamp;
            $reschReq->updated_at = $timeStamp;
            $reschReq->save();

            $appointmentData = DB::table('appointments')
                ->select('appointments.*', 'patients.user_id')
                ->join('patients', 'patients.id', '=', 'appointments.patient_id')
                ->where('appointments.id', $appointmentModel->id)
                ->first();

            $logModel = new AppointmentStatusLogModel;
            $logModel->appointment_id = $appointmentModel->id;
            $logModel->user_id = $appointmentData->user_id ?? null;
            $logModel->patient_id = $appointmentData->patient_id ?? null;
            $logModel->clinic_id = $appointmentModel->clinic_id;
            $logModel->status = 'Rescheduled';
            $logModel->notes = 'Appointment ' . $oldDate . ' ' . $oldTime . ' rescheduled to ' . $reschReq->requested_date . ' ' . $reschReq->requested_time_slots . ' (request approved)';
            $logModel->created_at = $timeStamp;
            $logModel->updated_at = $timeStamp;
            $logModel->save();

            DB::commit();

            if ($appointmentModel->type == 'Video Consultant') {
                $zoomController = new ZoomVideoCallController();
                $zoomController->updateMeeting(
                    $appointmentModel->id,
                    $appointmentModel->meeting_id,
                    $reschReq->requested_date,
                    $reschReq->requested_time_slots
                );
            }

            $notif = new NotificationCentralController();
            $notif->sendWalletRshNotificationToUsersAgainstRejected($appointmentModel->id, $oldDate, $oldTime);
            $notif->sendRescheduleApprovedNotification($appointmentModel->id, $oldDate, $oldTime);

            return Helpers::successWithIdResponse('successfully', $reschReq->id);
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("error");
        }
    }

    public function reject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return response(["response" => 400], 400);
        }

        try {
            DB::beginTransaction();
            $timeStamp = date("Y-m-d H:i:s");

            $reschReq = AppointmentRescheduleReqModel::where('id', $request->id)->first();
            if ($reschReq == null) {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }
            if ($reschReq->status !== 'Initiated') {
                DB::rollBack();
                return Helpers::errorResponse("Request already reviewed");
            }

            $reschReq->status = 'Rejected';
            $reschReq->reviewed_by_user_id = $request->reviewed_by_user_id ?? ($request->user()->id ?? null);
            $reschReq->reviewed_at = $timeStamp;
            if (isset($request->notes)) {
                $reschReq->notes = $request->notes;
            }
            $reschReq->updated_at = $timeStamp;
            $reschReq->save();

            DB::commit();

            (new NotificationCentralController())->sendRescheduleRejectedNotification(
                $reschReq->appointment_id,
                $reschReq->requested_date,
                $reschReq->requested_time_slots,
                $reschReq->notes ?? null
            );

            return Helpers::successWithIdResponse('successfully', $reschReq->id);
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("error");
        }
    }

    public function deleteByUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return response(["response" => 400], 400);
        }

        try {
            DB::beginTransaction();
            $reschReq = AppointmentRescheduleReqModel::where('id', $request->id)->first();
            if ($reschReq == null) {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }
            if ($reschReq->status !== 'Initiated') {
                DB::rollBack();
                return Helpers::errorResponse("Cannot delete a reviewed request");
            }
            $reschReq->delete();
            DB::commit();
            return Helpers::successWithIdResponse('successfully', $request->id);
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("error");
        }
    }

    public function getDataByAppointmentId($id)
    {
        $data = DB::table('appointment_reschedule_req')
            ->where('appointment_id', $id)
            ->orderBy('created_at', 'DESC')
            ->get();

        return response([
            'response' => 200,
            'data' => $data,
        ], 200);
    }

    public function getInitiatedList(Request $request)
    {
        $query = DB::table('appointment_reschedule_req')
            ->join('appointments', 'appointments.id', '=', 'appointment_reschedule_req.appointment_id')
            ->where('appointment_reschedule_req.status', 'Initiated')
            ->select(
                'appointment_reschedule_req.*',
                'appointments.doct_id',
                'appointments.clinic_id',
                'appointments.patient_id',
                'appointments.date as current_date',
                'appointments.time_slots as current_time_slots',
                'appointments.type as appointment_type'
            );

        if ($request->filled('clinic_id')) {
            $query->where('appointments.clinic_id', (int) $request->clinic_id);
        }
        if ($request->filled('doct_id')) {
            $query->where('appointments.doct_id', (int) $request->doct_id);
        }

        $data = $query->orderBy('appointment_reschedule_req.created_at', 'DESC')->get();

        return response([
            'response' => 200,
            'data' => $data,
        ], 200);
    }
}
