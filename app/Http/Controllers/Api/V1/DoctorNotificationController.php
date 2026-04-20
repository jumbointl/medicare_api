<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DoctorNotificationModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class DoctorNotificationController extends Controller
{

  public function index(Request $request)
  {
      $notifications = DB::table('doctor_notification')
          ->where('doctor_id', $request->user()->id)
          ->orderByDesc('created_at')
          ->paginate(20);

      return response()->json([
          'status' => true,
          'data' => $notifications
      ], 200);
  }

  /**
   * Marcar como leída
   */
  public function markSeen(Request $request, $id)
  {
      try {
          DB::table('doctor_notification')
              ->where('id', (int) $id)
              ->where('doctor_id', $request->user()->id)
              ->update([
                  'seen_status' => 1, // ✅ Columna creada con el comando SQL
                  'updated_at' => now()
              ]);

          return response()->json([
              'status' => true,
              'message' => 'Notificación marcada como leída'
          ], 200);
      } catch (\Throwable $e) {
          return response()->json([
              'status' => false,
              'message' => 'Error al actualizar',
              'error' => $e->getMessage()
          ], 500);
      }
  }

  function checkNotificaitonSeen($userId){

    // Get the latest notification sent to the user
    $lastNotification = DoctorNotificationModel::orderBy('created_at', 'desc')->first();

    // Check if no notifications are available
    if ($lastNotification == null) {
        return response()->json([
            "response" => 200,
            "status" => false,
            "data"=>null,
            "message" => "No notifications found"
        ], 200);
    }

    // Get the user information
    $user = User::where('id', $userId)->first();

    // Check if the user exists
    if ($user == null) {
        return response()->json([
            "response" => 200,
            "status" => false,
            "data"=>null,
            "message" => "User not found"
        ], 200);
    }

    // Check if the user has never seen any notifications
    if ($user->notification_seen_at == null) {
        return response()->json([
            "response" => 200,
            "status" => false,
            "data"=>null,
            "message" => "User has not seen any notifications"
        ], 200);
    }

    // Get user seen timestamp and last notification timestamp
    $userSeenTimeStamp = $user->notification_seen_at;
    $lastNotificationTimeStamp = $lastNotification->created_at;
    // Compare the timestamps
    if ($userSeenTimeStamp >= $lastNotificationTimeStamp) {
        return response()->json([
            "response" => 200,
            "status" => true,
            "data"=>null,
            "message" => "User has seen the latest notification"
        ], 200);
    } else {
     
        return response()->json([
            "response" => 200,
            "status" => false,
           "data" => [
              "dot_status" => true
           ],
            "message" => "User has not seen the latest notification"
        ], 200);
    }

  }

  public function getDataPeg(Request $request)
  {

      // Calculate the limit
      $start = $request->start;
      $end = $request->end;
      $limit = ($end - $start);

      // Define the base query


      $query = DB::table("doctor_notification")
          ->select(
              'doctor_notification.*',
              'appointments.id as appointment_id',
              'doctors.clinic_id'

          )
          ->join('appointments','appointments.id','=','doctor_notification.appointment_id')
          ->join('doctors','doctors.user_id','=','appointments.doct_id')
          ->orderBy('doctor_notification.created_at','DESC');

          if (!empty($request->start_date)) {
            $query->whereDate('doctor_notification.created_at', '>=', $request->start_date);
        }
        
        if (!empty($request->end_date)) {
            $query->whereDate('doctor_notification.created_at', '<=', $request->end_date);
        }
        if (!empty($request->clinic_id)) {
          $query->where('doctors.clinic_id', '=', $request->clinic_id);
      }

      if ($request->has('search')) {
          $search = $request->input('search');
          $query->where(function ($q) use ($search) {
              $q->where('doctor_notification.title', 'like', '%' . $search . '%')
                  ->orWhere('doctor_notification.body', 'like', '%' . $search . '%');

          });
      }

      $total_record = $query->get()->count();
      $data = $query->skip($start)->take($limit)->get();

      $response = [
          "response" => 200,
          "total_record" => $total_record,
          'data' => $data,
      ];

      return response()->json($response, 200);
  }



  function getData()
  {

    $data = DB::table('doctor_notification')
      ->select('doctor_notification.*')
      ->orderBy('doctor_notification.created_at','DESC')
      ->get();
    $response = [
      "response" => 200,
      'data' => $data,
    ];

    return response($response, 200);
  }

  function getDataById($id)
  {

    $data = DB::table("doctor_notification")
      ->select('doctor_notification.*')
      ->where("doctor_notification.id", "=", $id)
      ->first();
    $response = [
      "response" => 200,
      'data' => $data,
    ];

    return response($response, 200);
  }
  function getDataByDoctorId($id)
  {

    $data = DB::table("doctor_notification")
      ->select('doctor_notification.*')
      ->where("doctor_notification.doctor_id", "=", $id)
      ->orderBy('doctor_notification.created_at','DESC')
      ->get();
    $response = [
      "response" => 200,
      'data' => $data,
    ];

    return response($response, 200);
  }


  function deleteData(Request $request){
      
    $initialCheck=false;
    $validator = Validator::make(request()->all(), [
      'id'=>'required'
  ]);
  if ($validator->fails()){
    return Helpers::errorResponse("error");
  }
      DB::beginTransaction();

           try{
              $timeStamp= date("Y-m-d H:i:s");
              $dataModel= DoctorNotificationModel::where("id",$request->id)->first();
              $oldImage=$dataModel->image;
         $qResponce= $dataModel->delete();
         if($qResponce)
         {
          
            DB::commit();
             return Helpers::successResponse("successfully");}

         else 
         {
            DB::rollBack();
             return Helpers::errorResponse("error");}
    
            }
            catch(\Exception $e){
                DB::rollBack();
                return Helpers::errorResponse("error");
            }
      
}

}
