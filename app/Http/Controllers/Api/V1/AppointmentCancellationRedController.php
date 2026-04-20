<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppointmentCancellationRedModel;
use App\Models\AppointmentModel;
use App\Models\User;
use App\Models\AppointmentInvoiceModel;
use App\Models\AppointmentStatusLogModel;
use App\Models\AllTransactionModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\V1\ZoomVideoCallController;
use App\Http\Controllers\Api\V1\NotificationCentralController;

class AppointmentCancellationRedController extends Controller
{


    function RejectAndRefund(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'appointment_id' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);

        try {
            DB::beginTransaction(); 
                $timeStamp = date("Y-m-d H:i:s");
                $appointmentModel = AppointmentModel::where("id", $request->appointment_id)->first();
              
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
                          
                $appointmentModelPaymentStatus = $appointmentModel->payment_status;
                    $appointmentModel->status="Rejected"; 
                    if(  $appointmentModelPaymentStatus=="Paid"){
                    $appointmentModel->payment_status="Refunded";  }
                    $qResponceApp = $appointmentModel->save();
                    if(!$qResponceApp){
                        DB::rollBack(); 
                        return Helpers::errorResponse("error");
                    }

                        
                        $appointmentData= DB::table("appointments")
                        ->select('appointments.*','patients.user_id')
                        ->join("patients","patients.id",'=','appointments.patient_id')
                           ->where("appointments.id","=", $request->appointment_id)
                          ->first();
                          $userId=$appointmentData->user_id;
                          $patient_id=$appointmentData->patient_id;
                          if($userId==null||$patient_id==null){
                            DB::rollBack(); 
                            return Helpers::errorResponse("error");
                          }
                        $dataASLModel=new AppointmentStatusLogModel; 
                        $dataASLModel->appointment_id  =  $request->appointment_id;
                        $dataASLModel->user_id  = $userId;
                        $dataASLModel->status  = "Rejected";
                        $dataASLModel->clinic_id = $appointmentModel->clinic_id;
                        
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
                    $invoiceModel = AppointmentInvoiceModel::where("appointment_id", $request->appointment_id)->first();
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
                  $dataTXNModel-> appointment_id= $request->appointment_id;
                  $dataTXNModel->created_at=$timeStamp;
                  $dataTXNModel->updated_at=$timeStamp;
                  $dataTXNModel->notes ="Refund against appointment id - " .$request->appointment_id;  
                  $dataTXNModel->clinic_id = $appointmentModel->clinic_id;
                  $dataTXNModel->is_wallet_txn =1;
                  $dataTXNModel-> last_wallet_amount =$userOldAmount;
                  $dataTXNModel-> new_wallet_amount =$userNewAmount;
                  $qResponceApp = $dataTXNModel->save();
                  if(!$qResponceApp){
                    DB::rollBack(); 
                    return Helpers::errorResponse("error");
                }
                  $invoiceModel = AppointmentInvoiceModel::where("appointment_id", $request->appointment_id)->first();
        
                if(!$invoiceModel){
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
                  $invoiceModel = AppointmentInvoiceModel::where("appointment_id", $request->appointment_id)->first();
        
                if(!$invoiceModel){
                    DB::rollBack(); 
                    return Helpers::errorResponse("error");
                }
              $invoiceModel->status="Cancelled";
              $qResponceApp = $invoiceModel->save();
              if(!$qResponceApp){
                DB::rollBack(); 
                return Helpers::errorResponse("error");
            }
            }
       
              
            DB::commit(); 
  
            if ($appointmentModel->type == "Video Consultant") {
                // Pass dependencies if needed
                $zoomController = new ZoomVideoCallController();
                $zoomController->deleteMeeting($appointmentModel->id,$appointmentModel->meeting_id); // Ensure it doesn't rely on constructor dependencies
            }

            $notificationCentralController = new NotificationCentralController();
            $notificationCentralController->sendAppointmentRejectSatusNotificationToUsers($request->appointment_id);
            
            if(  $appointmentModelPaymentStatus=="Paid"){
               
                $notificationCentralController->sendWalletRefundNotificationToUsersAgainstRejected($request->appointment_id, $refundAmount);   
            }
            return Helpers::successWithIdResponse("successfully", $request->appointment_id);
            
            
        } catch (\Exception $e) {

            DB::rollBack(); 
            return Helpers::errorResponse("error");
        }
    }

    function cancleAndRefund(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'appointment_id' => 'required',
            'status' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);

        try {
            DB::beginTransaction(); 
                $timeStamp = date("Y-m-d H:i:s");
                $dataModel = new AppointmentCancellationRedModel;
                $appointmentModel = AppointmentModel::where("id", $request->appointment_id)->first();
              
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

                $dataModel->appointment_id = $request->appointment_id;
                $dataModel->status = $request->status;
                $dataModel->created_at = $timeStamp;
                $dataModel->updated_at = $timeStamp;

                $qResponce = $dataModel->save();
                if(!$qResponce){
                    DB::rollBack(); 
                    return Helpers::errorResponse("error");
                }               
                $appointmentModelPaymentStatus = $appointmentModel->payment_status;
                    $appointmentModel->current_cancel_req_status=$request->status;
                    $appointmentModel->status="Cancelled"; 
                    if(  $appointmentModelPaymentStatus=="Paid"){
                    $appointmentModel->payment_status="Refunded";  }
                    $qResponceApp = $appointmentModel->save();
                    if(!$qResponceApp){
                        DB::rollBack(); 
                        return Helpers::errorResponse("error");
                    }
                       
                    $appointmentData= DB::table("appointments")
                    ->select('appointments.*','patients.user_id')
                    ->join("patients","patients.id",'=','appointments.patient_id')
                       ->where("appointments.id","=", $request->appointment_id)
                      ->first();
                      $userId=$appointmentData->user_id;
                      $patient_id=$appointmentData->patient_id;
                      if($userId==null||$patient_id==null){
                        DB::rollBack(); 
                        return Helpers::errorResponse("error");
                      }
                    $dataASLModel=new AppointmentStatusLogModel; 
                    $dataASLModel->appointment_id  =  $request->appointment_id;
                    $dataASLModel->user_id  = $userId;
                    $dataASLModel->status  = "Cancelled";
                    $dataASLModel->patient_id  =$patient_id;
                    $dataASLModel->clinic_id = $appointmentModel->clinic_id;
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
                    $invoiceModel = AppointmentInvoiceModel::where("appointment_id", $request->appointment_id)->first();
                    if(!$invoiceModel){
                        DB::rollBack(); 
                        return Helpers::errorResponse("error");
                    }
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
                  $dataTXNModel-> appointment_id= $request->appointment_id;
                  $dataTXNModel->transaction_type ="Credited";
                  $dataTXNModel->created_at=$timeStamp;
                  $dataTXNModel->updated_at=$timeStamp;
                  $dataTXNModel->notes ="Refund against appointment id - " .$request->appointment_id;  
                  $dataTXNModel->is_wallet_txn =1;
                  $dataTXNModel->clinic_id = $appointmentModel->clinic_id;
              
                  $dataTXNModel-> last_wallet_amount =$userOldAmount;
                  $dataTXNModel-> new_wallet_amount =$userNewAmount;
                  $qResponceApp = $dataTXNModel->save();
                  if(!$qResponceApp){
                    DB::rollBack(); 
                    return Helpers::errorResponse("error");
                }
                    $invoiceModel = AppointmentInvoiceModel::where("appointment_id", $request->appointment_id)->first();
        
                if(!$invoiceModel){
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
                    $invoiceModel = AppointmentInvoiceModel::where("appointment_id", $request->appointment_id)->first();
        
                if(!$invoiceModel){
                    DB::rollBack(); 
                    return Helpers::errorResponse("error");
                }
              $invoiceModel->status="Cancelled";
              $qResponceApp = $invoiceModel->save();
              if(!$qResponceApp){
                DB::rollBack(); 
                return Helpers::errorResponse("error");
            }
            }
       
            
            DB::commit(); 
                if ($appointmentModel->type == "Video Consultant") {
                // Pass dependencies if needed
                $zoomController = new ZoomVideoCallController();
                $zoomController->deleteMeeting($appointmentModel->id,$appointmentModel->meeting_id); // Ensure it doesn't rely on constructor dependencies
            }
            $notificationCentralController = new NotificationCentralController();
            $notificationCentralController->sendAppointmentCancellationStatusNotificationToUsers($request->appointment_id);
                    
            if(  $appointmentModelPaymentStatus=="Paid"){
               
                $notificationCentralController->sendWalletRefundNotificationToUsersAgainsCancellation($request->appointment_id, $refundAmount);   
            }
            
            return Helpers::successWithIdResponse("successfully", $request->appointment_id);
            
        } catch (\Exception $e) {

            DB::rollBack(); 
            return Helpers::errorResponse("error");
        }
    }
    function addData(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'appointment_id' => 'required',
            'status' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);

        try {
            DB::beginTransaction(); 
                $timeStamp = date("Y-m-d H:i:s");
                $dataModel = new AppointmentCancellationRedModel;
                $appointmentModel = AppointmentModel::where("id", $request->appointment_id)->first();
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

                $dataModel->appointment_id = $request->appointment_id;
                $dataModel->status = $request->status;

                $dataModel->created_at = $timeStamp;
                $dataModel->updated_at = $timeStamp;

                $qResponce = $dataModel->save();
                if ($qResponce) {
                    $appointmentData= DB::table("appointments")
                    ->select('appointments.*','patients.user_id')
                    ->join("patients","patients.id",'=','appointments.patient_id')
                       ->where("appointments.id","=", $request->appointment_id)
                      ->first();
                      $userId=$appointmentData->user_id;
                      $patient_id=$appointmentData->patient_id;
                      if($userId==null||$patient_id==null){
                        DB::rollBack(); 
                        return Helpers::errorResponse("error");
                      }
                    $dataASLModel=new AppointmentStatusLogModel; 
                    $dataASLModel->appointment_id  =  $request->appointment_id;
                    $dataASLModel->user_id  = $userId;
                    $dataASLModel->status  =  $request->status;
                    $dataASLModel->patient_id  =$patient_id;
                    $dataASLModel->created_at=$timeStamp;
                    $dataASLModel->updated_at=$timeStamp;
                    $dataASLModel->clinic_id = $appointmentModel->clinic_id;
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
                    $notificationCentralController->sendAppointmentCancellationNotificationToUsers($request->appointment_id,$request->status);
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
         

            $alreadyAddedModel = AppointmentCancellationRedModel::where("id", $request->id)->first();
            if ($alreadyAddedModel) {
                $appId=$alreadyAddedModel->appointment_id;
                $qResponce =$alreadyAddedModel->delete();
                if ($qResponce) {

                    $appointmentModel = AppointmentModel::where("id", $appId)->first();
                    if($appointmentModel==null){
                        DB::rollBack(); 
                        return Helpers::errorResponse("error");
                    }
                    if($appointmentModel->current_cancel_req_status=="Approved"){
                        DB::rollBack(); 
                        return Helpers::errorResponse("You cannot delete the status, cancleation request is approved");
                    }

                    $current_cancel_req_status=null;
                    $alreadyAddedModelN = AppointmentCancellationRedModel::where("appointment_id", $appId)->orderBy('created_at','DESC')->first();
                    if($alreadyAddedModelN!=null){
                        $current_cancel_req_status=$alreadyAddedModelN->status;
                    }
                    
                    $appointmentData= DB::table("appointments")
                    ->select('appointments.*','patients.user_id')
                    ->join("patients","patients.id",'=','appointments.patient_id')
                       ->where("appointments.id","=", $appId)
                      ->first();
                      $userId=$appointmentData->user_id;
                      $patient_id=$appointmentData->patient_id;
                      if($userId==null||$patient_id==null){
                        DB::rollBack(); 
                        return Helpers::errorResponse("error");
                      }
                      $timeStamp = date("Y-m-d H:i:s");
                    $dataASLModel=new AppointmentStatusLogModel; 
                    $dataASLModel->appointment_id  =  $appId;
                    $dataASLModel->user_id  = $userId;
                    $dataASLModel->status  =  "Deleted";
                    $dataASLModel->patient_id  =$patient_id;
                    $dataASLModel->clinic_id = $appointmentModel->clinic_id;
                    $dataASLModel->created_at=$timeStamp;
                    $dataASLModel->updated_at=$timeStamp;
                    $qResponceApp = $dataASLModel->save();
                    if(!$qResponceApp){
                      DB::rollBack(); 
                      return Helpers::errorResponse("error");
                  }

                    $appointmentModel = AppointmentModel::where("id", $appId)->first();
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
            return Helpers::errorResponse("error");
        }
    }
        // get data
        function getDataByAppointmentId($id)
        {
      
          $data = DB::table("appointment_cancellation_req")
          ->select('appointment_cancellation_req.*'
          )
           ->where("appointment_cancellation_req.appointment_id","=",$id)
           ->orderBy('appointment_cancellation_req.created_at','DESC')
            ->get();
          
                $response = [
                    "response"=>200,
                    'data'=>$data,
                ];
            
          return response($response, 200);
        }

           function deleteDataByUser(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'appointment_id' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);

        try {
            DB::beginTransaction(); 
            $alreadyAddedModel = AppointmentCancellationRedModel::where("appointment_id", $request->appointment_id)->first();
            if ($alreadyAddedModel) {
                       $appointmentModel = AppointmentModel::where("id", $request->appointment_id)->first();
                if($appointmentModel==null){
                    DB::rollBack(); 
                    return Helpers::errorResponse("error");
                }
                if($appointmentModel->current_cancel_req_status!="Initiated"){
                    DB::rollBack(); 
                    return Helpers::errorResponse("Sorry! you cannot delete the request");
                }
                $qResponce = AppointmentCancellationRedModel::where("appointment_id", $request->appointment_id)->delete();

                if ($qResponce) {

                    $appointmentData= DB::table("appointments")
                    ->select('appointments.*','patients.user_id')
                    ->join("patients","patients.id",'=','appointments.patient_id')
                       ->where("appointments.id","=", $request->appointment_id)
                      ->first();
                      $userId=$appointmentData->user_id;
                      $patient_id=$appointmentData->patient_id;
                      if($userId==null||$patient_id==null){
                        DB::rollBack(); 
                        return Helpers::errorResponse("error");
                      }
                      $timeStamp = date("Y-m-d H:i:s");
                    $dataASLModel=new AppointmentStatusLogModel; 
                    $dataASLModel->appointment_id  =  $request->appointment_id;
                    $dataASLModel->user_id  = $userId;
                    $dataASLModel->status  =  "Deleted";
                    $dataASLModel->patient_id  =$patient_id;
                    $dataASLModel->clinic_id = $appointmentModel->clinic_id;
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
                    $notificationCentralController->sendAppointmentCancellationDeleteByUserNotificationToUsers($request->appointment_id);
                    return Helpers::successWithIdResponse("successfully", $request->appointment_id);
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

            return Helpers::errorResponse("error");
        }
    }

}
