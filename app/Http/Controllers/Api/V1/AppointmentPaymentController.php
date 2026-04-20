<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppointmentPaymentModel;
use App\Models\AllTransactionModel;

use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;

class AppointmentPaymentController extends Controller
{
   


    function getDataById($id)
    {
        $data = DB::table("appointment_payments")
        ->select('appointment_payments.*',
        'all_transaction.user_id',
        'all_transaction.patient_id',
        'all_transaction.appointment_id',
        'patients.f_name as patient_f_name',
        'patients.l_name as patient_l_name',
        'users.f_name as user_f_name',
        'users.l_name as user_l_name'
        )
        ->where("appointment_payments.id",$id)
        ->Join('all_transaction','all_transaction.id','=','appointment_payments.txn_id')
        ->LeftJoin('patients','patients.id','=','all_transaction.patient_id')
        ->LeftJoin('users','users.id','=','all_transaction.user_id')
        ->OrderBy('appointment_payments.created_at','DESC')
        
          ->first();

              $response = [
                  "response"=>200,
                  'data'=>$data,
              ];
          
        return response($response, 200);
        }

        public function getData(Request $request)
        {
            // Define the base query
            $query = DB::table("appointment_payments")
                ->select(
                    'appointment_payments.*',
                    'all_transaction.user_id',
                    'all_transaction.patient_id',
                    'all_transaction.appointment_id',
                         'all_transaction.lab_booking_id',
                    'patients.f_name as patient_f_name',
                    'patients.l_name as patient_l_name',
                    'users.f_name as user_f_name',
                    'users.l_name as user_l_name',
                    'appointments.doct_id'
                )
                ->join('all_transaction', 'all_transaction.id', '=', 'appointment_payments.txn_id')
                ->leftJoin('patients', 'patients.id', '=', 'all_transaction.patient_id')
                ->leftJoin('users', 'users.id', '=', 'all_transaction.user_id')
                ->leftJoin('appointments', 'appointments.id', '=', 'all_transaction.appointment_id')
                ->orderBy('appointment_payments.created_at', 'DESC');
        
            // Apply filters only when values are provided
            if ($request->filled('doctor_id')) {
                $query->where('appointments.doct_id', $request->doctor_id);
            }
            if ($request->filled('clinic_id')) {
                $query->where('appointment_payments.clinic_id', $request->clinic_id );
            }
            if ($request->filled('appointment_id')) {
                $query->where('all_transaction.appointment_id', $request->appointment_id);
            }
              if ($request->filled('pathology_id')) {
                $query->where('appointment_payments.pathology_id', $request->pathology_id );
            }


        
            if ($request->filled('start_date')) {
                $query->whereDate('appointment_payments.created_at', '>=', $request->start_date);
            }
        
            if ($request->filled('end_date')) {
                $query->whereDate('appointment_payments.created_at', '<=', $request->end_date);
            }
         if ($request->filled('lab_booking_id' )) {
                $query->where('all_transaction.lab_booking_id', $request->lab_booking_id );
            }
            // Apply search filter
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->whereRaw('CONCAT(patients.f_name, " ", patients.l_name) LIKE ?', ["%$search%"])
                        ->orWhereRaw('CONCAT(users.f_name, " ", users.l_name) LIKE ?', ["%$search%"])
                        ->orWhere('appointment_payments.id', 'like', "%$search%")
                        ->orWhere('appointment_payments.txn_id', 'like', "%$search%")
                        ->orWhere('appointment_payments.invoice_id', 'like', "%$search%")
                        ->orWhere('appointment_payments.payment_method', 'like', "%$search%")
                        ->orWhere('all_transaction.user_id', 'like', "%$search%")
                        ->orWhere('all_transaction.patient_id', 'like', "%$search%")
                        ->orWhere('all_transaction.appointment_id', 'like', "%$search%")
                        ->orWhere('appointment_payments.amount', 'like', "%$search%");
                });
            }
        
            // Count total records before pagination
            $total_record = $query->count();
        
            // Handle pagination
            if ($request->filled(['start', 'end'])) {
                $query->skip($request->start)->take($request->end - $request->start);
            }
        
            $data = $query->get();
        
            return response()->json([
                "response" => 200,
                "total_record" => $total_record,
                "data" => $data,
            ], 200);
        }
        

           
}