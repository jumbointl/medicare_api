<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AppointmentCheckinModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\V1\NotificationCentralController;

class AppointmentCheckinController extends Controller
{

  function deleteData(Request $request)
  {

    $initialCheck = false;
    $validator = Validator::make(request()->all(), [
      'id' => 'required'
    ]);
    if ($validator->fails())
      return response(["response" => 400], 400);


    if ($initialCheck)
      return response(["response" => 400], 400);

    try {
      $timeStamp = date("Y-m-d H:i:s");
      $dataModel = AppointmentCheckinModel::where("id", $request->id)->first();


      $qResponce = $dataModel->delete();
      if ($qResponce) {
        $notificationCentralController = new NotificationCentralController();
        $notificationCentralController->sendAppointmentCheckInNotificationToUsers($dataModel->appointment_id, null, null, "Delete");
        return Helpers::successResponse("successfully");
      }
    } catch (\Exception $e) {

      return Helpers::errorResponse("error $e");
    }
  }

  function getDataById($id)
  {

    $data = DB::table("appointment_checkin")
      ->select('appointment_checkin.*')
      ->where('appointment_checkin.id', '=', $id)
      ->first();
    $response = [
      "response" => 200,
      'data' => $data,
    ];

    return response($response, 200);
  }

  public function getData(Request $request)
  {
    // Define the base query
    $query = DB::table("appointment_checkin")
      ->select(
        'appointment_checkin.*',
        'appointments.doct_id',
        'users.f_name as doct_f_name',
        'users.l_name as doct_l_name',
        'patients.f_name as patient_f_name',
        'patients.l_name as patient_l_name'
      )

      ->join('appointments', 'appointments.id', '=', 'appointment_checkin.appointment_id')

      ->join('patients', 'patients.id', '=', 'appointments.patient_id')
      ->join('users', 'users.id', '=', 'appointments.doct_id')
      ->where('appointments.status', "Confirmed");

    // Apply filters efficiently
    if ($request->filled('doctor_id')) {
      $query->where('appointments.doct_id', $request->doctor_id);
    }

    if ($request->filled('start_date')) {
      $query->whereDate('appointment_checkin.date', '>=', $request->start_date);
    }

    if ($request->filled('end_date')) {
      $query->whereDate('appointment_checkin.date', '<=', $request->end_date);
    }

    if ($request->filled('clinic_id')) {
      $query->where('appointment_checkin.clinic_id', $request->clinic_id);
    }
    if ($request->filled('docto_id')) {
      $query->where('appointments.doct_id', $request->docto_id);
    }
    if ($request->filled('date')) {
      $query->whereDate('appointment_checkin.date', $request->date);
    }

    // Apply search filter
    if ($request->filled('search')) {
      $search = $request->input('search');
      $query->where(function ($q) use ($search) {
        $q->where('appointment_checkin.appointment_id', 'like', "%$search%")
          ->orWhereRaw('CONCAT(patients.f_name, " ", patients.l_name) LIKE ?', ["%$search%"])
          ->orWhereRaw('CONCAT(users.f_name, " ", users.l_name) LIKE ?', ["%$search%"]);
      });
    }

    // Clone query before applying limit for count optimization
    $total_record = $query->clone()->count();

    // Apply pagination if start & end are provided
    if ($request->filled(['start', 'end'])) {
      $start = $request->start;
      $limit = $request->end - $start;
      $query->skip($start)->take($limit);
    }

    // Apply orderBy at the end to maximize index utilization
    $data = $query->orderByDesc('appointment_checkin.date')
      ->orderByDesc('appointment_checkin.time')
      ->get();

    return response()->json([
      "response" => 200,
      "total_record" => $total_record,
      'data' => $data,
    ], 200);
  }

  function addData(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'appointment_id' => 'required',
      'time' => 'required',
      'date' => 'required'
    ]);

    if ($validator->fails()) {
      return response(["response" => 400], 400);
    }

    // Fetch appointment
    $appData = DB::table("appointments")
      ->where('id', $request->appointment_id)
      ->first();

    if (!$appData) {
      return Helpers::errorResponse("No appointment found!");
    }

    if ($appData->status !== "Confirmed") {
      return Helpers::errorResponse("You can only check in for a confirmed appointment.");
    }

    // Check if already checked-in for same appointment + date
    $alreadyExists = AppointmentCheckinModel::where('appointment_id', $request->appointment_id)
      ->where('date', $request->date)
      ->first();

    if ($alreadyExists) {
      return Helpers::errorResponse("Already checked in");
    }

    try {
      // ✅ Token generation per CLINIC + DATE
      $lastToken = AppointmentCheckinModel::where('date', $request->date)
        ->where('clinic_id', $appData->clinic_id)
        ->max('token');

      $lastTokenNumber = $lastToken ? intval($lastToken) : 0;
      $newToken = $lastTokenNumber + 1;
      $formattedToken = str_pad($newToken, 3, '0', STR_PAD_LEFT);

      $dataModel = new AppointmentCheckinModel();
      $dataModel->appointment_id = $request->appointment_id;
      $dataModel->time = $request->time;
      $dataModel->date = $request->date;
      $dataModel->clinic_id = $appData->clinic_id;
      $dataModel->token = $formattedToken;
      $dataModel->created_at = now();
      $dataModel->updated_at = now();

      if ($dataModel->save()) {
        $notificationCentralController = new NotificationCentralController();
        $notificationCentralController->sendAppointmentCheckInNotificationToUsers(
          $request->appointment_id,
          $request->date,
          $request->time,
          "Add"
        );

        return response([
          "response" => 200,
          "status" => true,
          "message" => "Successfully checked in",
          "id" => $dataModel->id,
          "token" => $formattedToken
        ], 200);
      }
    } catch (\Exception $e) {
      return Helpers::errorResponse($e->getMessage());
    }
  }



  function updateDetails(Request $request)
  {

    $validator = Validator::make(request()->all(), [
      'id' => 'required'
    ]);
    if ($validator->fails())
      return response(["response" => 400], 400);

    try {
      $timeStamp = date("Y-m-d H:i:s");
      $dataModel = AppointmentCheckinModel::where("id", $request->id)->first();


      if (isset($request->time))
        $dataModel->time = $request->time;

      $dataModel->updated_at = $timeStamp;


      $qResponce = $dataModel->save();


      if ($qResponce) {
        return Helpers::successWithIdResponse("successfully", $dataModel->id);
      }
    } catch (\Exception $e) {

      return Helpers::errorResponse("error");
    }
  }
}
