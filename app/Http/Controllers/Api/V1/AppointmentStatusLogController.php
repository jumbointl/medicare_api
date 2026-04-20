<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CouponUseModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;

class AppointmentStatusLogController extends Controller
{
  public function getData(Request $request)
  {
      // Define the base query
      $query = DB::table("appointment_status_log")
          ->select(
              'appointment_status_log.*',
              'patients.f_name',
              'patients.l_name',
              'appointments.doct_id'
          )
          ->join('appointments', 'appointments.id', '=', 'appointment_status_log.appointment_id')
          ->join('patients', 'patients.id', '=', 'appointment_status_log.patient_id')
          ->orderBy('appointment_status_log.created_at', 'DESC');
  
      // Apply filters efficiently
      if ($request->filled('doctor_id')) {
          $query->where('appointments.doct_id', $request->doctor_id);
      }
  
      if ($request->filled('start_date')) {
          $query->whereDate('appointment_status_log.created_at', '>=', $request->start_date);
      }
  
      if ($request->filled('end_date')) {
          $query->whereDate('appointment_status_log.created_at', '<=', $request->end_date);
      }
  
      if ($request->filled('clinic_id')) {
          $query->where('appointment_status_log.clinic_id', $request->clinic_id);
      }
  
      // Apply search filter
      if ($request->filled('search')) {
          $search = $request->input('search');
          $query->where(function ($q) use ($search) {
              $q->where('appointment_status_log.user_id', 'like', "%$search%")
                  ->orWhereRaw('CONCAT(patients.f_name, " ", patients.l_name) LIKE ?', ["%$search%"])
                  ->orWhere('appointment_status_log.status', 'like', "%$search%")
                  ->orWhere('appointment_status_log.notes', 'like', "%$search%")
                  ->orWhere('appointment_status_log.appointment_id', 'like', "%$search%")
                  ->orWhere('appointment_status_log.patient_id', 'like', "%$search%");
          });
      }
      $total_record = $query->count();
      // Handle start & end for pagination
      if ($request->filled(['start', 'end'])) {
          $start = $request->start;
          $limit = $request->end - $start;
          $query->skip($start)->take($limit);
      }
  
  
      $data = $query->get();
  
      return response()->json([
          "response" => 200,
          "total_record" => $total_record,
          "data" => $data,
      ], 200);
  }
  
    function getDataById($id)
    {      
    
        $data = DB::table("appointment_status_log")
        ->select('appointment_status_log.*',
        'patients.f_name',
        'patients.l_name'
        )
        ->join('patients','patients.id','=','appointment_status_log.patient_id')
              ->where('appointment_status_log.id','=',$id)
              ->first();
              $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
    }

}