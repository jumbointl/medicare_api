<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LabAppointmentCancellationReqModel;
use App\Models\LabBookingModel;
use App\Models\User;
use App\Models\AppointmentInvoiceModel;
use App\Models\AppointmentStatusLogModel;
use App\Models\AllTransactionModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\V1\ZoomVideoCallController;
use App\Http\Controllers\Api\V1\NotificationCentralController;

class LabAppointmentCancellationReqController extends Controller
{


  function RejectAndRefundLabBooking(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'lab_booking_id' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);

        try {
            DB::beginTransaction(); 
                $timeStamp = date("Y-m-d H:i:s");
                $appointmentModel = LabBookingModel::where("id", $request->lab_booking_id)->first();
              
                if($appointmentModel==null){
                    DB::rollBack(); 
                    return Helpers::errorResponse("error");
                }

                if($appointmentModel->status=="Rejected"){
                    DB::rollBack(); 
                    return Helpers::errorResponse("Already Rejected");
                }
                if($appointmentModel->status=="Complete"||$appointmentModel->status=="Cancelled"||$appointmentModel->status=="Visited"){
                    DB::rollBack(); 
                    return Helpers::errorResponse("Appointment cannot be reject");
                }
            
                $invoiceModel = AppointmentInvoiceModel::where("lab_booking_id", $request->lab_booking_id)->first();
                if(!$invoiceModel){
                    DB::rollBack(); 
                    return Helpers::errorResponse("error");
                }

                          
                $appointmentModelPaymentStatus = $invoiceModel->status;
                    $appointmentModel->status="Rejected"; 
                 
                    $qResponceApp = $appointmentModel->save();
                    if(!$qResponceApp){
                        DB::rollBack(); 
                        return Helpers::errorResponse("error");
                    }

                        
                        $appointmentData= DB::table("lab_booking")
                        ->select('lab_booking.*','patients.user_id')
                        ->join("patients","patients.id",'=','lab_booking.lab_patient_id')
                              ->join("pathologist", "pathologist.id", '=', 'lab_booking.pathology_id')
                           ->where("lab_booking.id","=", $request->lab_booking_id)
                          ->first();

                          $userId=$appointmentData->user_id;
                          $patient_id=$appointmentData->lab_patient_id;
                          if($userId==null||$patient_id==null){
                            DB::rollBack(); 
                            return Helpers::errorResponse("error");
                          }
                          
                        $dataASLModel=new AppointmentStatusLogModel; 
                        $dataASLModel->lab_booking_id  =  $request->lab_booking_id;
                        $dataASLModel->user_id  = $userId;
                        $dataASLModel->status  = "Rejected";
                    
                        
                        $dataASLModel->patient_id  =$patient_id;
                        $dataASLModel->created_at=$timeStamp;
                        $dataASLModel->updated_at=$timeStamp;
                        $qResponceApp = $dataASLModel->save();
                        if(!$qResponceApp){
                          DB::rollBack(); 
                          return Helpers::errorResponse("error");
                      }
                        

                    if(  $appointmentModelPaymentStatus=="Paid"){

        
                    $userModel = User::where("id", $userId)->first();
                    if(!$userModel){
                        DB::rollBack(); 
                        return Helpers::errorResponse("error");
                    }
                    $userOldAmount=$userModel->wallet_amount;
                    $invoiceModel = AppointmentInvoiceModel::where("lab_booking_id", $request->lab_booking_id)->first();
                    if(!$invoiceModel){
                        DB::rollBack(); 
                        return Helpers::errorResponse("error");
                    }
                    $refundAmount=$invoiceModel->total_amount;
                    //$userNewAmount=$invoiceModel->total_amount;
                    if($userModel->wallet_amount==0){
                       $userNewAmount=$refundAmount;
                    }
                    else if($userModel->wallet_amount!=0){  
                        $userNewAmount= $userOldAmount+$refundAmount;
                    }
                    $userModel->wallet_amount=$userNewAmount;
                    $qResponceWU= $userModel->save();
                    if(!$qResponceWU){
                        DB::rollBack(); 
                        return Helpers::errorResponse("error");
                    }
                    
                  $dataTXNModel=new AllTransactionModel; 
                  $dataTXNModel->amount  = $refundAmount;
                  $dataTXNModel->user_id  = $userId;
                  $dataTXNModel->patient_id  = $patient_id;
                  $dataTXNModel->transaction_type ="Credited";
                  $dataTXNModel-> lab_booking_id= $request->lab_booking_id;
                  $dataTXNModel->created_at=$timeStamp;
                  $dataTXNModel->updated_at=$timeStamp;
                  $dataTXNModel->notes ="Refund against lab booking id - " .$request->lab_booking_id;  
                  $dataTXNModel->is_wallet_txn =1;
                  $dataTXNModel-> last_wallet_amount =$userOldAmount;
                  $dataTXNModel-> new_wallet_amount =$userNewAmount;
                  $qResponceApp = $dataTXNModel->save();
                  if(!$qResponceApp){
                    DB::rollBack(); 
                    return Helpers::errorResponse("error");
                    
                }
                   $invoiceModel->status="Refunded";
              $qResponceApp = $invoiceModel->save();
              if(!$qResponceApp){
                DB::rollBack(); 
                return Helpers::errorResponse("error");
            }
            }
            else{
                  
              $invoiceModel->status="Cancelled";
              $qResponceApp = $invoiceModel->save();
              if(!$qResponceApp){
                DB::rollBack(); 
                return Helpers::errorResponse("error");
            }
            }
       
            
            DB::commit(); 
  
           $notificationCentralController = new NotificationCentralController();
           $notificationCentralController->sendLabAppointmentRejectSatusNotificationToUsers($request->lab_booking_id);
            
            if(  $appointmentModelPaymentStatus=="Paid"){
               
                $notificationCentralController->sendWalletRefundLabNotificationToUsersAgainstRejected($request->lab_booking_id, $refundAmount);   
            }
            return Helpers::successWithIdResponse("successfully", $request->lab_booking_id);
            
            
        } catch (\Exception $e) {

            DB::rollBack(); 
            return Helpers::errorResponse("error $e");
        }
    }



    function cancleAndRefund(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'lab_booking_id' => 'required',
            'status' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);

        try {
            DB::beginTransaction(); 
                $timeStamp = date("Y-m-d H:i:s");
                $dataModel = new LabAppointmentCancellationReqModel;
                $appointmentModel = LabBookingModel::where("id", $request->lab_booking_id)->first();
              
                if($appointmentModel==null){
                    DB::rollBack(); 
                    return Helpers::errorResponse("error");
                }
                $current_cancel_req_status=$appointmentModel->current_cancel_req_status;
                if($current_cancel_req_status==$request->status){
                    DB::rollBack(); 
                    return Helpers::errorResponse("Already requested");
                }
                if(isset( $request->notes)){
                    $dataModel->notes = $request->notes;
                }

                $dataModel->lab_booking_id = $request->lab_booking_id;
                $dataModel->status = $request->status;
                $dataModel->created_at = $timeStamp;
                $dataModel->updated_at = $timeStamp;

                $qResponce = $dataModel->save();
                if(!$qResponce){
                    DB::rollBack(); 
                    return Helpers::errorResponse("error");
                }     
                                 
                $invoiceModel = AppointmentInvoiceModel::where("lab_booking_id", $request->lab_booking_id)->first();
                  if(!$invoiceModel){
                    DB::rollBack(); 
                    return Helpers::errorResponse("error");
                }
                 
                $appointmentModelPaymentStatus = $invoiceModel->status;
                    $appointmentModel->current_cancel_req_status=$request->status;
                    $appointmentModel->status="Cancelled"; 
                    $qResponceApp = $appointmentModel->save();
                    if(!$qResponceApp){
                        DB::rollBack(); 
                        return Helpers::errorResponse("error");
                    }
                       
                    $appointmentData= DB::table("lab_booking")
                    ->select('lab_booking.*','patients.user_id')
                    ->join("patients","patients.id",'=','lab_booking.lab_patient_id')
                       ->where("lab_booking.id","=", $request->lab_booking_id)
                      ->first();
                      $userId=$appointmentData->user_id;
                      $patient_id=$appointmentData->lab_patient_id;
                      if($userId==null||$patient_id==null){
                        DB::rollBack(); 
                        return Helpers::errorResponse("error");
                      }
                    $dataASLModel=new AppointmentStatusLogModel; 
                    $dataASLModel->lab_booking_id  =  $request->lab_booking_id;
                    $dataASLModel->user_id  = $userId;
                    $dataASLModel->status  = "Cancelled";
                    $dataASLModel->patient_id  =$patient_id;
                    $dataASLModel->created_at=$timeStamp;
                    $dataASLModel->updated_at=$timeStamp;
                    $qResponceApp = $dataASLModel->save();
                    if(!$qResponceApp){
                      DB::rollBack(); 
                      return Helpers::errorResponse("error");
                  }
                    if(  $appointmentModelPaymentStatus=="Paid"){

            
                    $userModel = User::where("id", $userId)->first();
                    if(!$userModel){
                        DB::rollBack(); 
                        return Helpers::errorResponse("error");
                    }
                    $userOldAmount=$userModel->wallet_amount;
                
                    $refundAmount=$invoiceModel->total_amount;
                   // $userNewAmount=$invoiceModel->total_amount;
                    if($userModel->wallet_amount==0){
                       $userNewAmount=$refundAmount;
                    }
                    else if($userModel->wallet_amount!=0){  
                        $userNewAmount= $userOldAmount+$refundAmount;
                    }
                    $userModel->wallet_amount=$userNewAmount;
                    $qResponceWU= $userModel->save();
                    if(!$qResponceWU){
                        DB::rollBack(); 
                        return Helpers::errorResponse("error");
                    }
                    
                  $dataTXNModel=new AllTransactionModel; 
                  $dataTXNModel->amount  = $refundAmount;
                  $dataTXNModel->user_id  = $userId;
                  $dataTXNModel->patient_id  = $patient_id;
                  $dataTXNModel-> lab_booking_id= $request->lab_booking_id;
                  $dataTXNModel->transaction_type ="Credited";
                  $dataTXNModel->created_at=$timeStamp;
                  $dataTXNModel->updated_at=$timeStamp;
                  $dataTXNModel->notes ="Refund against lab booking id - " .$request->lab_booking_id;  
                  $dataTXNModel->is_wallet_txn =1;
              
                  $dataTXNModel-> last_wallet_amount =$userOldAmount;
                  $dataTXNModel-> new_wallet_amount =$userNewAmount;
                  $qResponceApp = $dataTXNModel->save();
                  if(!$qResponceApp){
                    DB::rollBack(); 
                    return Helpers::errorResponse("error");
                }
                  $invoiceModel->status="Refunded";
              $qResponceApp = $invoiceModel->save();
              if(!$qResponceApp){
                DB::rollBack(); 
                return Helpers::errorResponse("error");
            }
            }else{
                  $invoiceModel->status="Cancelled";
              $qResponceApp = $invoiceModel->save();
              if(!$qResponceApp){
                DB::rollBack(); 
                return Helpers::errorResponse("error");
            }
            }
               
            
            DB::commit(); 
          

           $notificationCentralController = new NotificationCentralController();
           $notificationCentralController->sendLabAppointmentCancellationStatusNotificationToUsers($request->lab_booking_id);

           
            if(  $appointmentModelPaymentStatus=="Paid"){
               
                $notificationCentralController->sendWalletRefundLabNotificationToUsersAgainsCancellation($request->lab_booking_id, $refundAmount);   
            }
            return Helpers::successWithIdResponse("successfully", $request->lab_booking_id);
            
        } catch (\Exception $e) {

            DB::rollBack(); 
            return Helpers::errorResponse("error");
        }
    }
    function addData(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'lab_booking_id' => 'required',
            'status' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);

        try {
            DB::beginTransaction(); 
                $timeStamp = date("Y-m-d H:i:s");
                $dataModel = new LabAppointmentCancellationReqModel;
                $appointmentModel = LabBookingModel::where("id", $request->lab_booking_id)->first();
                if($appointmentModel==null){
                    DB::rollBack(); 
                    return Helpers::errorResponse("error");
                }
                $current_cancel_req_status=$appointmentModel->current_cancel_req_status;
                if($current_cancel_req_status==$request->status){
                    DB::rollBack(); 
                    return Helpers::errorResponse("Already requested");
                }
                if(isset( $request->notes)){
                    $dataModel->notes = $request->notes;
                }

                $dataModel->lab_booking_id = $request->lab_booking_id;
                $dataModel->status = $request->status;

                $dataModel->created_at = $timeStamp;
                $dataModel->updated_at = $timeStamp;

                $qResponce = $dataModel->save();
                if ($qResponce) {
                    $appointmentData= DB::table("lab_booking")
                    ->select('lab_booking.*','patients.user_id')
                    ->join("patients","patients.id",'=','lab_booking.lab_patient_id')
                       ->where("lab_booking.id","=", $request->lab_booking_id)
                      ->first();
                      $userId=$appointmentData->user_id;
                      $patient_id=$appointmentData->lab_patient_id;
                      if($userId==null||$patient_id==null){
                        DB::rollBack(); 
                        return Helpers::errorResponse("error");
                      }
                    $dataASLModel=new AppointmentStatusLogModel; 
                    $dataASLModel->lab_booking_id  =  $request->lab_booking_id;
                    $dataASLModel->user_id  = $userId;
                    $dataASLModel->status  =  $request->status;
                    $dataASLModel->patient_id  =$patient_id;
                    $dataASLModel->created_at=$timeStamp;
                    $dataASLModel->updated_at=$timeStamp;

                    $qResponceApp = $dataASLModel->save();
                    if(!$qResponceApp){
                      DB::rollBack(); 
                      return Helpers::errorResponse("error");
                  }
                 
                    $appointmentModel->current_cancel_req_status=$request->status;
                    $qResponceApp = $appointmentModel->save();
                    if(!$qResponceApp){
                        DB::rollBack(); 
                        return Helpers::errorResponse("error");
                    }
                    DB::commit(); 
                   $notificationCentralController = new NotificationCentralController();
                   $notificationCentralController->sendLabAppointmentCancellationNotificationToUsers($request->lab_booking_id,$request->status);
                    return Helpers::successWithIdResponse("successfully", $dataModel->id);
                } else {
                    DB::rollBack(); 
                    return Helpers::errorResponse("error");
                }
            
        } catch (\Exception $e) {

            DB::rollBack(); 
            return Helpers::errorResponse("error $e");
        }
    }

    function deleteData(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'id' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);

        try {
            DB::beginTransaction(); 
         

            $alreadyAddedModel = LabAppointmentCancellationReqModel::where("id", $request->id)->first();
            if ($alreadyAddedModel) {
                $appId=$alreadyAddedModel->lab_booking_id;
                $qResponce =$alreadyAddedModel->delete();
                if ($qResponce) {

                    $appointmentModel = LabBookingModel::where("id", $appId)->first();
                    if($appointmentModel==null){
                        DB::rollBack(); 
                        return Helpers::errorResponse("error");
                    }
                    if($appointmentModel->current_cancel_req_status=="Approved"){
                        DB::rollBack(); 
                        return Helpers::errorResponse("You cannot delete the status, cancleation request is approved");
                    }

                    $current_cancel_req_status=null;
                    $alreadyAddedModelN = LabAppointmentCancellationReqModel::where("lab_booking_id", $appId)->orderBy('created_at','DESC')->first();
                    if($alreadyAddedModelN!=null){
                        $current_cancel_req_status=$alreadyAddedModelN->status;
                    }
                    
                    $appointmentData= DB::table("lab_booking")
                    ->select('lab_booking.*','patients.user_id')
                    ->join("patients","patients.id",'=','lab_booking.lab_patient_id')
                       ->where("lab_booking.id","=", $appId)
                      ->first();
                      $userId=$appointmentData->user_id;
                      $patient_id=$appointmentData->lab_patient_id;
                      if($userId==null||$patient_id==null){
                        DB::rollBack(); 
                        return Helpers::errorResponse("error");
                      }
                      $timeStamp = date("Y-m-d H:i:s");
                    $dataASLModel=new AppointmentStatusLogModel; 
                    $dataASLModel->lab_booking_id  =  $appId;
                    $dataASLModel->user_id  = $userId;
                    $dataASLModel->status  =  "Deleted";
                    $dataASLModel->patient_id  =$patient_id;
                    $dataASLModel->created_at=$timeStamp;
                    $dataASLModel->updated_at=$timeStamp;
                    $qResponceApp = $dataASLModel->save();
                    if(!$qResponceApp){
                      DB::rollBack(); 
                      return Helpers::errorResponse("error");
                  }

                    $appointmentModel = LabBookingModel::where("id", $appId)->first();
                    $appointmentModel->current_cancel_req_status=$current_cancel_req_status;
                    $qResponceApp = $appointmentModel->save();
                    if(!$qResponceApp){
                        DB::rollBack(); 
                        return Helpers::errorResponse("error");
                    }
                    DB::commit(); 
                    return Helpers::successWithIdResponse("successfully", $request->id);
                } else {

                    DB::rollBack(); 
                    return Helpers::errorResponse("error");
                }
            } else {
                DB::rollBack(); 

                return Helpers::errorResponse("error");
            }
        } catch (\Exception $e) {
            DB::rollBack(); 
            return Helpers::errorResponse("error $e");
        }
    }

      public function getData(Request $request)
    {
        // Define the base query
        $query = DB::table("lab_booking_cancellation_req")
            ->select(
                'lab_booking_cancellation_req.*' ,
                'lab_booking.pathology_id as pathology_id', 
         
            )
            ->leftJoin('lab_booking', 'lab_booking.id', '=', 'lab_booking_cancellation_req.lab_booking_id')
             ->orderBy('lab_booking_cancellation_req.created_at','DESC');
  
        // Apply filters efficiently
        if ($request->filled('lab_booking_id')) {
            $query->where("lab_booking_cancellation_req.lab_booking_id", "=", $request->lab_booking_id);
        }
            if ($request->filled('pathology_id')) {
            $query->where("lab_booking.pathology_id", "=", $request->pathology_id);
        }
    
     
        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('lab_booking_cancellation_req.id', 'like', "%$search%")
                    ->orWhere('lab_booking_cancellation_req.lab_booking_id', 'like', "%$search%");
                   
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
            "data" => $data
        ], 200);
    }

           function deleteDataByUser(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'lab_booking_id' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);

        try {
            DB::beginTransaction(); 
            $alreadyAddedModel = LabAppointmentCancellationReqModel::where("lab_booking_id", $request->lab_booking_id)->first();
            if ($alreadyAddedModel) {
                       $appointmentModel = LabBookingModel::where("id", $request->lab_booking_id)->first();
                if($appointmentModel==null){
                    DB::rollBack(); 
                    return Helpers::errorResponse("error");
                }
                if($appointmentModel->current_cancel_req_status!="Initiated"){
                    DB::rollBack(); 
                    return Helpers::errorResponse("Sorry! you cannot delete the request");
                }
                $qResponce = LabAppointmentCancellationReqModel::where("lab_booking_id", $request->lab_booking_id)->delete();

                if ($qResponce) {

                   $appointmentData= DB::table("lab_booking")
                    ->select('lab_booking.*','patients.user_id')
                    ->join("patients","patients.id",'=','lab_booking.lab_patient_id')
                       ->where("lab_booking.id","=", $request->lab_booking_id)
                      ->first();
                      $userId=$appointmentData->user_id;
                      $patient_id=$appointmentData->lab_patient_id;
                      if($userId==null||$patient_id==null){
                        DB::rollBack(); 
                        return Helpers::errorResponse("error");
                      }
                      $timeStamp = date("Y-m-d H:i:s");
                    $dataASLModel=new AppointmentStatusLogModel; 
                    $dataASLModel->lab_booking_id  =  $request->lab_booking_id;
                    $dataASLModel->user_id  = $userId;
                    $dataASLModel->status  =  "Deleted";
                    $dataASLModel->patient_id  =$patient_id;
                    $dataASLModel->created_at=$timeStamp;
                    $dataASLModel->updated_at=$timeStamp;
                    $qResponceApp = $dataASLModel->save();
                    if(!$qResponceApp){
                      DB::rollBack(); 
                      return Helpers::errorResponse("error");
                  }
                    $appointmentModel->current_cancel_req_status=null;
                    $qResponceApp = $appointmentModel->save();
                    if(!$qResponceApp){
                        DB::rollBack(); 
                        return Helpers::errorResponse("error");
                    }
                    DB::commit(); 
                   $notificationCentralController = new NotificationCentralController();
                   $notificationCentralController->sendLabAppointmentCancellationDeleteByUserNotificationToUsers($request->lab_booking_id);
                    return Helpers::successWithIdResponse("successfully", $request->lab_booking_id);
                } else {

                    DB::rollBack(); 
                    return Helpers::errorResponse("error");
                }
            } else {
                DB::rollBack(); 

                return Helpers::errorResponse("error");
            }
        } catch (\Exception $e) {
            DB::rollBack(); 

            return Helpers::errorResponse("error $e");
        }
    }

}
