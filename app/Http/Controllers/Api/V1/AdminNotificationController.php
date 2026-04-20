<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AdminNotificationModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class AdminNotificationController extends Controller
{

  public function getData(Request $request)
  {

      // Calculate the limit
      $start = $request->start;
      $end = $request->end;
      $limit = ($end - $start);

      // Define the base query


      $query = DB::table("admin_notification")
          ->select(
              'admin_notification.*'

          )
          ->orderBy('admin_notification.created_at','DESC');

          if (!empty($request->start_date)) {
            $query->whereDate('admin_notification.created_at', '>=', $request->start_date);
        }
        
        if (!empty($request->end_date)) {
            $query->whereDate('admin_notification.created_at', '<=', $request->end_date);
        }
        if (!empty($request->clinic_id)) {
          $query->where('admin_notification.clinic_id', '=', $request->clinic_id);
      }
        

      if ($request->has('search')) {
          $search = $request->input('search');
          $query->where(function ($q) use ($search) {
              $q->where('admin_notification.title', 'like', '%' . $search . '%')
                  ->orWhere('admin_notification.body', 'like', '%' . $search . '%');

          });
      }

      $total_record = $query->get()->count();
      if ($request->filled(['start', 'end'])) {
        $start = $request->start;
        $limit = $request->end - $start;
        $query->skip($start)->take($limit);
    }
    $data = $query->get();

      $response = [
          "response" => 200,
          "total_record" => $total_record,
          'data' => $data,
      ];

      return response()->json($response, 200);
  }

 
  // function getData()
  // {

  //   $data = DB::table('admin_notification')
  //     ->select('admin_notification.*')
  //     ->orderBy('admin_notification.created_at','DESC')
  //     ->get();
  //   $response = [
  //     "response" => 200,
  //     'data' => $data,
  //   ];

  //   return response($response, 200);
  // }

  function getDataById($id)
  {

    $data = DB::table("admin_notification")
      ->select('admin_notification.*')
      ->where("admin_notification.id", "=", $id)
      ->first();
    $response = [
      "response" => 200,
      'data' => $data,
    ];

    return response($response, 200);
  }


}
