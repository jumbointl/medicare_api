<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserNotificationModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserNotificationController extends Controller
{

  function checkNotificaitonSeen($userId){

    // Get the latest notification sent to the user
    $lastNotification = DB::table('user_notification')
    ->select('user_notification.*')
     ->where(function($query) use ($userId) {
        $query->where('user_notification.user_id', '=', null)
              ->orWhere('user_notification.user_id', '=', $userId);
    })
    ->orderBy('user_notification.created_at', 'DESC')
    ->first();
  

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
            "data" => [
              "dot_status" => true
           ],
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


      $query = DB::table("user_notification")
          ->select(
              'user_notification.*'

          )
          ->orderBy('user_notification.created_at','DESC');

          if (!empty($request->start_date)) {
            $query->whereDate('user_notification.created_at', '>=', $request->start_date);
        }
        
        if (!empty($request->end_date)) {
            $query->whereDate('user_notification.created_at', '<=', $request->end_date);
        }
  

      if ($request->has('search')) {
          $search = $request->input('search');
          $query->where(function ($q) use ($search) {
              $q->where('user_notification.title', 'like', '%' . $search . '%')
                  ->orWhere('user_notification.body', 'like', '%' . $search . '%');

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

    $data = DB::table('user_notification')
      ->select('user_notification.*')
      ->orderBy('user_notification.created_at','DESC')
      ->get();
    $response = [
      "response" => 200,
      'data' => $data,
    ];

    return response($response, 200);
  }

  function getDataById($id)
  {

    $data = DB::table("user_notification")
      ->select('user_notification.*')
      ->where("user_notification.id", "=", $id)
      ->first();
    $response = [
      "response" => 200,
      'data' => $data,
    ];

    return response($response, 200);
  }
  function getDataByDate($uid,$date)
  {

    $data = DB::table('user_notification')
      ->select('user_notification.*')
      ->where(function($query) use ($uid) {
        $query->where('user_notification.user_id', '=', null)
              ->orWhere('user_notification.user_id', '=', $uid);
    })
    ->where('user_notification.created_at', '>=', $date)
      ->orderBy('user_notification.created_at','DESC')
      ->get();
    $response = [
      "response" => 200,
      'data' => $data,
    ];

    return response($response, 200);
  }

  // add new users
  function addData(Request $request)
  {


    $validator = Validator::make(request()->all(), [
      'title' => 'required',
      'body' => 'required'
    ]);

    if ($validator->fails())

      return response(["response" => 400], 400);

      try {
        DB::beginTransaction();
        $timeStamp = date("Y-m-d H:i:s");
        $userModel = new UserNotificationModel;
        $userModel->title = $request->title;
        $userModel->body = $request->body;
        $userModel->created_at = $timeStamp;
        $userModel->updated_at = $timeStamp;
        $image_url="";
        if (isset($request->image)) {
          $userModel->image = $request->hasFile('image') ? Helpers::uploadImage('notification/', $request->file('image')) : null;
          $image_url=env('APP_URL')."/public/storage/" .$userModel->image;
        
        }
        if (isset($request->topic)){
        app('App\Http\Controllers\Api\V1\SendNotificationController')->sendFirebaseNotificationToTopic($request->title,$request->body,$image_url,$request->topic); 
        }
        if (isset($request->token)){
            app('App\Http\Controllers\Api\V1\SendNotificationController')->sendFirebaseNotificationToToken($request->title,$request->body,$image_url,$request->token); 
            }
        $qResponce = $userModel->save();

        if ($qResponce) {
          DB::commit();
          return Helpers::successWithIdResponse("successfully", $userModel->id);
        } else {
          DB::rollBack();
          return Helpers::errorResponse("error");
        }


      } catch (\Exception $e) {
        DB::rollBack();
        return Helpers::errorResponse("error $e");
      }

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
              $dataModel= UserNotificationModel::where("id",$request->id)->first();
              $oldImage=$dataModel->image;
        
              if($oldImage!=null){
               Helpers::deleteImage($oldImage);
              }
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
