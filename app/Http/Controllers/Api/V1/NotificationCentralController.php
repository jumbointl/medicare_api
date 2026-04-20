<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserNotificationModel;
use App\Models\DoctorNotificationModel;
use App\Models\AdminNotificationModel;
use App\Models\LabNotificationModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class NotificationCentralController extends Controller
{


    // Lab

      //Appointment Start
    function sendLabNotificationToUsers($appId){

        $appointmentData= DB::table("lab_booking")
                        ->select('lab_booking.*',
                        'patients.user_id',
                        'patients.f_name',
                        'patients.l_name',
                        "pathologist.title as pathology_title",
                     
                        )
                        ->join("patients","patients.id",'=','lab_booking.lab_patient_id')
                        ->join("pathologist","pathologist.id",'=','lab_booking.pathology_id')
                        ->where("lab_booking.id","=", $appId)
                        ->first();
                  $pathName=$appointmentData->pathology_title;
                  $patientName=$appointmentData->f_name ." ". $appointmentData->l_name;
                  $pathology_id=$appointmentData->pathology_id;
                  // Format the date to '2nd October 2024'
                  $date = Carbon::parse($appointmentData->date)->format('jS F Y');

            if($appointmentData!=null&&$appointmentData->user_id!=null){
                    $title="Lab Appointment Successfully Booked!";
                    $body="Your appointment with lab $pathName on $date is booked.";

                    $this->addUserNotificationTable($title,$body,$appointmentData->user_id,null,null,null,null,$appId);
            }

            if($appointmentData!=null&&$pathology_id!=null){
                $title="New Lab Appointment Booked for $date";
                $body="You have a new lab appointment with $patientName on $date. Please review the patient’s information and be prepared for the visit.";

                $this->addLabNotificationTable($title,$body,$pathology_id,$appId);


        }
        
        if($appointmentData!=null){
            $title="New Lab Appointment";
            $body="A new lab appointment has been booked for $patientName with lab $pathName on $date";
            $this->addAdminotificationTable($title,$body,null,null,$appId);
            
    }

    }

     function sendLabRshNotificationToUsers($appId,$oldDateGet){

        $appointmentData= DB::table("lab_booking")
                        ->select('lab_booking.*',
                        'patients.user_id',
                        'patients.f_name',
                        'patients.l_name',
                        "pathologist.title as pathology_title",
                        )
                        ->join("patients","patients.id",'=','lab_booking.lab_patient_id')
                        ->join("pathologist","pathologist.id",'=','lab_booking.pathology_id')
                        ->where("lab_booking.id","=", $appId)
                        ->first();
                  $pathName=$appointmentData->pathology_title;
                  $patientName=$appointmentData->f_name ." ". $appointmentData->l_name;
                  $pathology_id=$appointmentData->pathology_id;
                  // Format the date to '2nd October 2024'
                  $date = Carbon::parse($appointmentData->date)->format('jS F Y');
                  $oldDate = Carbon::parse($oldDateGet)->format('jS F Y');
                  // Assuming $appointmentData->time_slots contains time in 'H:i:s' format
        

            if($appointmentData!=null&&$appointmentData->user_id!=null){
                $title="Lab Appointment Rescheduled";
                $body="Your appointment with lab $pathName on $oldDate has been rescheduled to $date. Please review the new schedule and contact us if you have any questions.";
            
                    $this->addUserNotificationTable($title,$body,$appointmentData->user_id,null,null,null,null,$appId);
            }

            if($appointmentData!=null&&$pathology_id!=null){
                $title="Lab Appointment Rescheduled";
                $body="The lab appointment for $patientName on $oldDate has been rescheduled to $date. Please review your updated schedule";
                
                    $this->addLabNotificationTable($title,$body,$pathology_id,$appId);
        }
        
        if($appointmentData!=null){
            $title="Lab Appointment Rescheduled";
            $body= "You have successfully rescheduled the appointment for $patientName with lab $pathName from $oldDate to $date.";
            $this->addAdminotificationTable($title,$body,null,null,$appId);
    }

    

} 

 function sendLabAppointmentSatusChangeNotificationToUsers($appId,$status){

        $appointmentData= DB::table("lab_booking")
                        ->select('lab_booking.*',
                        'patients.user_id',
                        'patients.f_name',
                        'patients.l_name',
                        "pathologist.title as pathology_title",
                        
                        )
                        ->join("patients","patients.id",'=','lab_booking.lab_patient_id')
                        ->join("pathologist","pathologist.id",'=','lab_booking.pathology_id')
                        ->where("lab_booking.id","=", $appId)
                        ->first();
                  $pathName=$appointmentData->pathology_title;
                  $patientName=$appointmentData->f_name ." ". $appointmentData->l_name;
                  $pathology_id=$appointmentData->pathology_id;
                  // Format the date to '2nd October 2024'
                  $date = Carbon::parse($appointmentData->date)->format('jS F Y');
            


            if($appointmentData!=null&&$appointmentData->user_id!=null){

                if($status=="Confirmed"){
                       $title="Lab Appointment Confirmed";
                        $body="Your lab appointment with $pathName on $date has been confirmed. Please arrive on time.";
                }
                else if($status=="Visited"){
                    $title="Lab Appointment Visited";
                    $body="Your lab appointment with $pathName on $date has been marked as Visited. We hope your visit went well.";
                      }

               else if($status=="Completed"){
                    $title="Lab Appointment Completed";
                    $body="Your lab appointment with $pathName on $date has been marked as completed. We hope your visit went well.";
                      }

                      else if($status=="Rejected"){
                        $title="Lab Appointment Rejected";
                        $body="Your lab appointment request with $pathName on $date has been rejected.";
                          }
                          else if($status=="Pending"){
                            $title="Lab Appointment Marked as Pending";
                            $body="Your lab appointment request with $pathName on $date has been marked as pending. We will notify you once it's confirmed.";
                              }
                                 $this->addUserNotificationTable($title,$body,$appointmentData->user_id,null,null,null,null,$appId);
                
            }

            if($appointmentData!=null&&$pathology_id!=null){

                if($status=="Confirmed"){
                    $title="Lab Appointment Confirmed";
                      $body="The appointment with $patientName on $date has been confirmed.";
                     }
                     else if($status=="Completed"){
                        $title="Lab Appointment Marked as Completed";
                        $body="The appointment with $patientName on $date has been marked as completed.";
                          }
                     else if($status=="Visited"){
                        $title="Lab Appointment Marked as Visited";
                        $body="The appointment with $patientName on $date has been marked as Visited.";
                          }

                          else if($status=="Rejected"){
                            $title="Lab Appointment Rejected";
                            $body="The appointment request from $patientName on $date has been rejected.";
                              }

                              else if($status=="Pending"){
                                $title="Lab Appointment Marked as Pending";
                                 $body="The appointment with $patientName on $date has been marked as pending.";
                                  }
                                     $this->addLabNotificationTable($title,$body,$pathology_id,$appId);


               

             }
        
        if($appointmentData!=null){
            if($status=="Confirmed"){
                $title="Lab Appointment Confirmed";
                  $body="The appointment for $patientName with $pathName on $date has been confirmed.";
                  }
                  else if($status=="Visited"){
                    $title="Lab Appointment Visited";
                    $body="The appointment for $patientName with $pathName on $date has been marked as Visited.";
                      }
                  else if($status=="Completed"){
                    $title="Lab Appointment Completed";
                    $body="The appointment for $patientName with $pathName on $date has been marked as completed.";
                      }
                      else if($status=="Rejected"){
                        $title="Lab Appointment Rejected";
                        $body="The appointment request for $patientName with $pathName on $date has been rejected.";
                          }

                          else if($status=="Pending"){
                            $title="Lab Appointment Marked as Pending";
                            $body="The appointment for $patientName with $pathName on $date has been marked as pending.";
                              }
                               $this->addAdminotificationTable($title,$body,null,null,$appId);
            
    }

    } 

        function sendLabAppointmentCancellationNotificationToUsers($appId,$status){

              $appointmentData= DB::table("lab_booking")
                        ->select('lab_booking.*',
                        'patients.user_id',
                        'patients.f_name',
                        'patients.l_name',
                        "pathologist.title as pathology_title",
                        
                        )
                        ->join("patients","patients.id",'=','lab_booking.lab_patient_id')
                        ->join("pathologist","pathologist.id",'=','lab_booking.pathology_id')
                        ->where("lab_booking.id","=", $appId)
                        ->first();
                  $pathName=$appointmentData->pathology_title;
                  $patientName=$appointmentData->f_name ." ". $appointmentData->l_name;
                  $pathology_id=$appointmentData->pathology_id;

                  $date = Carbon::parse($appointmentData->date)->format('jS F Y');
            
        

            if($appointmentData!=null&&$appointmentData->user_id!=null){
                if($status=="Initiated"){
                    $title="Cancellation Request Initiated";
                    $body="Your request to cancel the lab appointment with $pathName on $date has been initiated. We will notify you once it is processed.";
                }
                else  if($status=="Processing"){
                    $title="Cancellation Request Processing";
                    $body="Your request to cancel the appointment with $pathName on $date is currently being processed. You will receive an update once the process is complete.";
                }
                else  if($status=="Approved"){
                    $title="Cancellation Request Approved";
                    $body="Your request to cancel the appointment with $pathName on $date has been approved. The appointment is now canceled.";
                }
                else  if($status=="Rejected"){
                    $title="Cancellation Request Rejected";
                    $body="Your request to cancel the appointment with $pathName on $date has been rejected. Please contact the clinic for further assistance.";
                }
                   $this->addUserNotificationTable($title,$body,$appointmentData->user_id,null,null,null,null,$appId);

        
            }

            if($appointmentData!=null&&$pathology_id!=null){
                if($status=="Initiated"){
                    $title="Cancellation Request Processing  for $patientName";
                    $body="A cancellation request has been initiated for the lab appointment with $patientName on $date. Please monitor the request for further updates.";
                }
                else  if($status=="Processing"){
                    $title="Cancellation Request Processing";
                    $body="The cancellation request for the lab appointment with $patientName on $date is currently being processed. Please monitor the request for further updates.";
                }
                else  if($status=="Approved"){
                    $title="Cancellation Request Approved for $patientName";
                    $body="The lab appointment with $patientName on $date has been successfully canceled.";
                }
                else  if($status=="Rejected"){
                    $title="Cancellation Request Rejected for $patientName";
                    $body="The cancellation request for the lab appointment with $patientName on $date has been rejected. The appointment remains scheduled as planned.";
                }
                   $this->addLabNotificationTable($title,$body,$pathology_id,$appId);
               
        }
        
        if($appointmentData!=null){
              if($status=="Initiated"){
                    $title="Cancellation Request Initiated for $patientName";
                    $body="A cancellation request has been initiated for the lab appointment with $patientName on $date. Please monitor the request for further updates.";
                }
                else  if($status=="Processing"){
                    $title="Cancellation Request Processing";
                    $body="The cancellation request for the lab appointment with $patientName on $date is currently being processed. Please ensure all necessary steps are completed in the admin panel.";
                }
                else  if($status=="Approved"){
                    $title="Cancellation Request Approved for $patientName";
                    $body="The lab appointment with $patientName on $date has been successfully canceled.";
                }
                else  if($status=="Rejected"){
                    $title="Cancellation Request Rejected for $patientName";
                    $body="The cancellation request for the lab appointment with $patientName on $date has been rejected. The appointment remains scheduled as planned. Please follow up if necessary.";
                }
                 $this->addAdminotificationTable($title,$body,null,null,$appId);
            
    }

    }

      function sendLabAppointmentCancellationDeleteByUserNotificationToUsers($appId){

                        $appointmentData= DB::table("lab_booking")
                        ->select('lab_booking.*',
                        'patients.user_id',
                        'patients.f_name',
                        'patients.l_name',
                        "pathologist.title as pathology_title",
                        
                        )
                        ->join("patients","patients.id",'=','lab_booking.lab_patient_id')
                        ->join("pathologist","pathologist.id",'=','lab_booking.pathology_id')
                        ->where("lab_booking.id","=", $appId)
                        ->first();
                  $pathName=$appointmentData->pathology_title;
                  $patientName=$appointmentData->f_name ." ". $appointmentData->l_name;
                  $pathology_id=$appointmentData->pathology_id;
                  $date = Carbon::parse($appointmentData->date)->format('jS F Y');
            

            if($appointmentData!=null&&$appointmentData->user_id!=null){
                $title="Cancellation Request Deleted";
                $body="Your request to cancel the appointment with lab $pathName on $date has been deleted. Your appointment remains scheduled as planned.";
                   $this->addUserNotificationTable($title,$body,$appointmentData->user_id,null,null,null,null,$appId);
            }

            if($appointmentData!=null&&$pathology_id!=null){
                $title="Cancellation Request Withdrawn";
                $body="$patientName has withdrawn their request to cancel the lab appointment on $date. The appointment remains on your schedule.";
                   $this->addLabNotificationTable($title,$body,$pathology_id,$appId);

        }
        
        if($appointmentData!=null){
            $title="Appointment Cancellation Request Withdrawn";
            $body="$patientName has deleted their request to cancel the appointment with lab $pathName on $date. The appointment remains scheduled.";
            $this->addAdminotificationTable($title,$body,null,null,$appId);
        
    }

    } 
       function sendLabAppointmentRejectSatusNotificationToUsers($appId){

                        $appointmentData= DB::table("lab_booking")
                        ->select('lab_booking.*',
                        'patients.user_id',
                        'patients.f_name',
                        'patients.l_name',
                        "pathologist.title as pathology_title",
                        
                        )
                        ->join("patients","patients.id",'=','lab_booking.lab_patient_id')
                        ->join("pathologist","pathologist.id",'=','lab_booking.pathology_id')
                        ->where("lab_booking.id","=", $appId)
                        ->first();
                  $pathName=$appointmentData->pathology_title;
                  $patientName=$appointmentData->f_name ." ". $appointmentData->l_name;
                  $pathology_id=$appointmentData->pathology_id;
                  // Format the date to '2nd October 2024'
                  $date = Carbon::parse($appointmentData->date)->format('jS F Y');
            
 

            if($appointmentData!=null&&$appointmentData->user_id!=null){
                $title="Appointment Rejected";
                $body="Your appointment with lab $pathName on $date has been successfully rejected.";
                   $this->addUserNotificationTable($title,$body,$appointmentData->user_id,null,null,null,null,$appId);
                   
            }

            if($appointmentData!=null&&$pathology_id!=null){
                $title="Appointment Rejected";
                $body= "The lab appointment with $patientName on $date has been rejected. Please check your updated schedule.";
                   $this->addLabNotificationTable($title,$body,$pathology_id,$appId);
                
            

        }
        
        if($appointmentData!=null){
            $title="Appointment Rejected";
            $body= "The lab appointment with $patientName on $date has been rejected.";
             $this->addAdminotificationTable($title,$body,null,null,$appId);
    }

    } 
      function sendWalletRefundLabNotificationToUsersAgainstRejected($appId,$amount){

                                $appointmentData= DB::table("lab_booking")
                        ->select('lab_booking.*',
                        'patients.user_id',
                        'patients.f_name',
                        'patients.l_name',
                        "pathologist.title as pathology_title",
                        
                        )
                        ->join("patients","patients.id",'=','lab_booking.lab_patient_id')
                        ->join("pathologist","pathologist.id",'=','lab_booking.pathology_id')
                        ->where("lab_booking.id","=", $appId)
                        ->first();
                  $pathName=$appointmentData->pathology_title;
                  $patientName=$appointmentData->f_name ." ". $appointmentData->l_name;
                  $pathology_id=$appointmentData->pathology_id;
                  // Format the date to '2nd October 2024'
                  $date = Carbon::parse($appointmentData->date)->format('jS F Y');
            
   

            if($appointmentData!=null&&$appointmentData->user_id!=null){
                $title="Refund Processed";
                $body="The amount of $amount for your rejected lab appointment with $pathName on $date has been refunded to your wallet. You can use this balance for future appointments.";
            
                $this->addUserNotificationTable($title,$body,$appointmentData->user_id,null,null,null,null,$appId);
            }

            if($appointmentData!=null&&$pathology_id!=null){
                $title="Refund Issued";
                $body= "A refund of $amount has been successfully issued to $patientName for the lab rejected appointment with $pathName on $date.";
                
                  $this->addLabNotificationTable($title,$body,$pathology_id,$appId);
             

        }
        
        if($appointmentData!=null){
            $title="Refund Issued";
            $body= "A refund of $amount has been successfully issued to $patientName for the rejected appointment with $pathName on $date.";
               $this->addAdminotificationTable($title,$body,null,null,$appId);
    }

    }
     function sendLabAppointmentCancellationStatusNotificationToUsers($appId){

             $appointmentData= DB::table("lab_booking")
                        ->select('lab_booking.*',
                        'patients.user_id',
                        'patients.f_name',
                        'patients.l_name',
                        "pathologist.title as pathology_title",
                        
                        )
                        ->join("patients","patients.id",'=','lab_booking.lab_patient_id')
                        ->join("pathologist","pathologist.id",'=','lab_booking.pathology_id')
                        ->where("lab_booking.id","=", $appId)
                        ->first();
                  $pathName=$appointmentData->pathology_title;
                  $patientName=$appointmentData->f_name ." ". $appointmentData->l_name;
                  $pathology_id=$appointmentData->pathology_id;
                  // Format the date to '2nd October 2024'
                  $date = Carbon::parse($appointmentData->date)->format('jS F Y');
            
                  // Assuming $appointmentData->time_slots contains time in 'H:i:s' format
                

            if($appointmentData!=null&&$appointmentData->user_id!=null){
                $title="Lab Appointment Cancelled Reuest Approved";
                $body="Your lab appointment with $pathName on $date at has been successfully cancelled.";
                      $this->addUserNotificationTable($title,$body,$appointmentData->user_id,null,null,null,null,$appId);
            }

            if($appointmentData!=null&&$pathology_id!=null){
                $title="Lab Appointment Cancelled Reuest Approved";
                $body= "The lab appointment with $patientName on $date at has been cancelled. Please check your updated schedule.";
                      $this->addLabNotificationTable($title,$body,$pathology_id,$appId);
                

        }
        
        if($appointmentData!=null){
            $title="Lab Appointment Cancelled Reuest Approved";
            $body= "The lab appointment with $patientName on $date at has been cancelled.";
               $this->addAdminotificationTable($title,$body,null,null,$appId);
           
    }

    }  
    
    function sendWalletRefundLabNotificationToUsersAgainsCancellation($appId,$amount){

                      $appointmentData= DB::table("lab_booking")
                        ->select('lab_booking.*',
                        'patients.user_id',
                        'patients.f_name',
                        'patients.l_name',
                        "pathologist.title as pathology_title",
                        
                        )
                        ->join("patients","patients.id",'=','lab_booking.lab_patient_id')
                        ->join("pathologist","pathologist.id",'=','lab_booking.pathology_id')
                        ->where("lab_booking.id","=", $appId)
                        ->first();
                  $pathName=$appointmentData->pathology_title;
                  $patientName=$appointmentData->f_name ." ". $appointmentData->l_name;
                  $pathology_id=$appointmentData->pathology_id;
                  // Format the date to '2nd October 2024'
                  $date = Carbon::parse($appointmentData->date)->format('jS F Y');
            
            
            if($appointmentData!=null&&$appointmentData->user_id!=null){
                $title="Refund Processed";
                $body="The amount of $amount for your cancelled lab appointment with $pathName on $date has been refunded to your wallet. You can use this balance for future appointments.";
            
                       $this->addUserNotificationTable($title,$body,$appointmentData->user_id,null,null,null,null,$appId);
            }

            if($appointmentData!=null&&$pathology_id!=null){
                $title="Refund Issued";
                $body= "A refund of $amount has been successfully issued to $patientName for the cancelled lab appointment with $pathName on $date.";
                
                
                    $this->addLabNotificationTable($title,$body,$pathology_id,$appId);

        }
        
        if($appointmentData!=null){
            $title="Refund Issued";
            $body= "A refund of $amount has been successfully issued to $patientName for the cancelled lab appointment with $pathName on $date.";
               $this->addAdminotificationTable($title,$body,null,null,$appId);
    }

    }
    //Lab

    //Appointment Start
    function sendAppointmentNotificationToUsers($appId){

        $appointmentData= DB::table("appointments")
                        ->select('appointments.*',
                        'patients.user_id',
                        'patients.f_name',
                        'patients.l_name',
                        'users.f_name as doctor_f_name',
                        'users.l_name as doctor_l_name',
                        )
                        ->join("patients","patients.id",'=','appointments.patient_id')
                        ->join("users","users.id",'=','appointments.doct_id')
                        ->where("appointments.id","=", $appId)
                        ->first();
                  $doctName=$appointmentData->doctor_f_name ." ". $appointmentData->doctor_l_name;
                  $patientName=$appointmentData->f_name ." ". $appointmentData->l_name;
                  // Format the date to '2nd October 2024'
                  $date = Carbon::parse($appointmentData->date)->format('jS F Y');
            
                  // Assuming $appointmentData->time_slots contains time in 'H:i:s' format
                $time = Carbon::createFromFormat('H:i:s', $appointmentData->time_slots)->format('h:i A');

            if($appointmentData!=null&&$appointmentData->user_id!=null){
                    $title="Appointment Successfully Booked!";
                    $body="Your appointment with Doctor $doctName on $date at $time is confirmed.";
                    $this->addUserNotificationTable($title,$body,$appointmentData->user_id,$appId,null,null,null);
            }

            if($appointmentData!=null&&$appointmentData->doct_id!=null){
                $title="New Appointment Booked for $date";
                $body="You have a new appointment with $patientName on $date at $time. Please review the patient’s information and be prepared for the visit.";

                $this->addDoctorNotificationTable($title,$body,$appointmentData->doct_id,$appId,null,null);

        }
        
        if($appointmentData!=null){
            $title="New Appointment";
            $body="A new appointment has been booked for $patientName with Doctor $doctName on $date at $time";
            $this->addAdminotificationTable($title,$body,$appId,null);
    }

    }
    function sendAppointmentCancellationNotificationToUsers($appId,$status){

        $appointmentData= DB::table("appointments")
                        ->select('appointments.*',
                        'patients.user_id',
                        'patients.f_name',
                        'patients.l_name',
                        'users.f_name as doctor_f_name',
                        'users.l_name as doctor_l_name',
                        )
                        ->join("patients","patients.id",'=','appointments.patient_id')
                        ->join("users","users.id",'=','appointments.doct_id')
                        ->where("appointments.id","=", $appId)
                        ->first();
                  $doctName=$appointmentData->doctor_f_name ." ". $appointmentData->doctor_l_name;
                  $patientName=$appointmentData->f_name ." ". $appointmentData->l_name;
                  // Format the date to '2nd October 2024'
                  $date = Carbon::parse($appointmentData->date)->format('jS F Y');
            
                  // Assuming $appointmentData->time_slots contains time in 'H:i:s' format
                $time = Carbon::createFromFormat('H:i:s', $appointmentData->time_slots)->format('h:i A');

            if($appointmentData!=null&&$appointmentData->user_id!=null){
                if($status=="Initiated"){
                    $title="Cancellation Request Initiated";
                    $body="Your request to cancel the appointment with Doctor $doctName on $date at $time has been initiated. We will notify you once it is processed.";
                }
                else  if($status=="Processing"){
                    $title="Cancellation Request Processing";
                    $body="Your request to cancel the appointment with Doctor $doctName on $date at $time is currently being processed. You will receive an update once the process is complete.";
                }
                else  if($status=="Approved"){
                    $title="Cancellation Request Approved";
                    $body="Your request to cancel the appointment with Doctor $doctName on $date has been approved. The appointment is now canceled.";
                }
                else  if($status=="Rejected"){
                    $title="Cancellation Request Rejected";
                    $body="Your request to cancel the appointment with Doctor $doctName on $date has been rejected. Please contact the clinic for further assistance.";
                }

                $this->addUserNotificationTable($title,$body,$appointmentData->user_id,$appId,null,null,null);
        
            }

            if($appointmentData!=null&&$appointmentData->doct_id!=null){
                if($status=="Initiated"){
                    $title="Cancellation Request Processing  for $patientName";
                    $body="A cancellation request has been initiated for the appointment with $patientName on $date at $time. Please monitor the request for further updates.";
                }
                else  if($status=="Processing"){
                    $title="Cancellation Request Processing";
                    $body="The cancellation request for the appointment with $patientName on $date at $time is currently being processed. Please monitor the request for further updates.";
                }
                else  if($status=="Approved"){
                    $title="Cancellation Request Approved for $patientName";
                    $body="The appointment with $patientName on $date at $time has been successfully canceled.";
                }
                else  if($status=="Rejected"){
                    $title="Cancellation Request Rejected for $patientName";
                    $body="The cancellation request for the appointment with $patientName on $date at $time has been rejected. The appointment remains scheduled as planned.";
                }
                $this->addDoctorNotificationTable($title,$body,$appointmentData->doct_id,$appId,null,null);
        }
        
        if($appointmentData!=null){
              if($status=="Initiated"){
                    $title="Cancellation Request Initiated for $patientName";
                    $body="A cancellation request has been initiated for the appointment with $patientName on $date at $time. Please monitor the request for further updates.";
                }
                else  if($status=="Processing"){
                    $title="Cancellation Request Processing";
                    $body="The cancellation request for the appointment with $patientName on $date at $time is currently being processed. Please ensure all necessary steps are completed in the admin panel.";
                }
                else  if($status=="Approved"){
                    $title="Cancellation Request Approved for $patientName";
                    $body="The appointment with $patientName on $date at $time has been successfully canceled.";
                }
                else  if($status=="Rejected"){
                    $title="Cancellation Request Rejected for $patientName";
                    $body="The cancellation request for the appointment with $patientName on $date at $time has been rejected. The appointment remains scheduled as planned. Please follow up if necessary.";
                }
                $this->addAdminotificationTable($title,$body,$appId,null);
    }

    }

  
    function sendAppointmentCancellationDeleteByUserNotificationToUsers($appId){

        $appointmentData= DB::table("appointments")
                        ->select('appointments.*',
                        'patients.user_id',
                        'patients.f_name',
                        'patients.l_name',
                        'users.f_name as doctor_f_name',
                        'users.l_name as doctor_l_name',
                        )
                        ->join("patients","patients.id",'=','appointments.patient_id')
                        ->join("users","users.id",'=','appointments.doct_id')
                        ->where("appointments.id","=", $appId)
                        ->first();
                  $doctName=$appointmentData->doctor_f_name ." ". $appointmentData->doctor_l_name;
                  $patientName=$appointmentData->f_name ." ". $appointmentData->l_name;
                  // Format the date to '2nd October 2024'
                  $date = Carbon::parse($appointmentData->date)->format('jS F Y');
            
                  // Assuming $appointmentData->time_slots contains time in 'H:i:s' format
                $time = Carbon::createFromFormat('H:i:s', $appointmentData->time_slots)->format('h:i A');

            if($appointmentData!=null&&$appointmentData->user_id!=null){
                $title="Cancellation Request Deleted";
                $body="Your request to cancel the appointment with Doctor $doctName on $date has been deleted. Your appointment remains scheduled as planned.";
                    $this->addUserNotificationTable($title,$body,$appointmentData->user_id,$appId,null,null,null);
            }

            if($appointmentData!=null&&$appointmentData->doct_id!=null){
                $title="Cancellation Request Withdrawn";
                $body="$patientName has withdrawn their request to cancel the appointment on $date at $time. The appointment remains on your schedule.";
                $this->addDoctorNotificationTable($title,$body,$appointmentData->doct_id,$appId,null,null);

        }
        
        if($appointmentData!=null){
            $title="Appointment Cancellation Request Withdrawn";
            $body="$patientName has deleted their request to cancel the appointment with Dr. $doctName on $date. The appointment remains scheduled.";
            $this->addAdminotificationTable($title,$body,$appId,null);
    }

    } 

    function sendAppointmentCancellationStatusNotificationToUsers($appId){

        $appointmentData= DB::table("appointments")
                        ->select('appointments.*',
                        'patients.user_id',
                        'patients.f_name',
                        'patients.l_name',
                        'users.f_name as doctor_f_name',
                        'users.l_name as doctor_l_name',
                        )
                        ->join("patients","patients.id",'=','appointments.patient_id')
                        ->join("users","users.id",'=','appointments.doct_id')
                        ->where("appointments.id","=", $appId)
                        ->first();
                  $doctName=$appointmentData->doctor_f_name ." ". $appointmentData->doctor_l_name;
                  $patientName=$appointmentData->f_name ." ". $appointmentData->l_name;
                  // Format the date to '2nd October 2024'
                  $date = Carbon::parse($appointmentData->date)->format('jS F Y');
            
                  // Assuming $appointmentData->time_slots contains time in 'H:i:s' format
                $time = Carbon::createFromFormat('H:i:s', $appointmentData->time_slots)->format('h:i A');

            if($appointmentData!=null&&$appointmentData->user_id!=null){
                $title="Appointment Cancelled Reuest Approved";
                $body="Your appointment with Dr. $doctName on $date at $time has been successfully cancelled.";
                    $this->addUserNotificationTable($title,$body,$appointmentData->user_id,$appId,null,null,null);
            }

            if($appointmentData!=null&&$appointmentData->doct_id!=null){
                $title="Appointment Cancelled Reuest Approved";
                $body= "The appointment with $patientName on $date at $time has been cancelled. Please check your updated schedule.";
                
                $this->addDoctorNotificationTable($title,$body,$appointmentData->doct_id,$appId,null,null);

        }
        
        if($appointmentData!=null){
            $title="Appointment Cancelled Reuest Approved";
            $body= "The appointment with $patientName on $date at $time has been cancelled. The slot is now available.";
            $this->addAdminotificationTable($title,$body,$appId,null);
    }

    } 

    function sendAppointmentRejectSatusNotificationToUsers($appId){

        $appointmentData= DB::table("appointments")
                        ->select('appointments.*',
                        'patients.user_id',
                        'patients.f_name',
                        'patients.l_name',
                        'users.f_name as doctor_f_name',
                        'users.l_name as doctor_l_name',
                        )
                        ->join("patients","patients.id",'=','appointments.patient_id')
                        ->join("users","users.id",'=','appointments.doct_id')
                        ->where("appointments.id","=", $appId)
                        ->first();
                  $doctName=$appointmentData->doctor_f_name ." ". $appointmentData->doctor_l_name;
                  $patientName=$appointmentData->f_name ." ". $appointmentData->l_name;
                  // Format the date to '2nd October 2024'
                  $date = Carbon::parse($appointmentData->date)->format('jS F Y');
            
                  // Assuming $appointmentData->time_slots contains time in 'H:i:s' format
                $time = Carbon::createFromFormat('H:i:s', $appointmentData->time_slots)->format('h:i A');

            if($appointmentData!=null&&$appointmentData->user_id!=null){
                $title="Appointment Rejected";
                $body="Your appointment with Dr. $doctName on $date at $time has been successfully rejected.";
                    $this->addUserNotificationTable($title,$body,$appointmentData->user_id,$appId,null,null,null);
            }

            if($appointmentData!=null&&$appointmentData->doct_id!=null){
                $title="Appointment Rejected";
                $body= "The appointment with $patientName on $date at $time has been rejected. Please check your updated schedule.";
                
                $this->addDoctorNotificationTable($title,$body,$appointmentData->doct_id,$appId,null,null);

        }
        
        if($appointmentData!=null){
            $title="Appointment Rejected";
            $body= "The appointment with $patientName on $date at $time has been rejected. The slot is now available.";
            $this->addAdminotificationTable($title,$body,$appId,null);
    }

    } 

    function sendWalletRefundNotificationToUsersAgainsCancellation($appId,$amount){

        $appointmentData= DB::table("appointments")
                        ->select('appointments.*',
                        'patients.user_id',
                        'patients.f_name',
                        'patients.l_name',
                        'users.f_name as doctor_f_name',
                        'users.l_name as doctor_l_name',
                        )
                        ->join("patients","patients.id",'=','appointments.patient_id')
                        ->join("users","users.id",'=','appointments.doct_id')
                        ->where("appointments.id","=", $appId)
                        ->first();
                  $doctName=$appointmentData->doctor_f_name ." ". $appointmentData->doctor_l_name;
                  $patientName=$appointmentData->f_name ." ". $appointmentData->l_name;
                  // Format the date to '2nd October 2024'
                  $date = Carbon::parse($appointmentData->date)->format('jS F Y');
            
                  // Assuming $appointmentData->time_slots contains time in 'H:i:s' format
                $time = Carbon::createFromFormat('H:i:s', $appointmentData->time_slots)->format('h:i A');

            if($appointmentData!=null&&$appointmentData->user_id!=null){
                $title="Refund Processed";
                $body="The amount of $amount for your cancelled appointment with Dr. $doctName on $date has been refunded to your wallet. You can use this balance for future appointments.";
            
                    $this->addUserNotificationTable($title,$body,$appointmentData->user_id,$appId,null,null,null);
            }

            if($appointmentData!=null&&$appointmentData->doct_id!=null){
                $title="Refund Issued";
                $body= "A refund of $amount has been successfully issued to $patientName for the cancelled appointment with Dr. $doctName on $date.";
                
                
                $this->addDoctorNotificationTable($title,$body,$appointmentData->doct_id,$appId,null,null);

        }
        
        if($appointmentData!=null){
            $title="Refund Issued";
            $body= "A refund of $amount has been successfully issued to $patientName for the cancelled appointment with Dr. $doctName on $date.";
            $this->addAdminotificationTable($title,$body,$appId,null);
    }

    } 

    
    function sendWalletRefundNotificationToUsersAgainstRejected($appId,$amount){

        $appointmentData= DB::table("appointments")
                        ->select('appointments.*',
                        'patients.user_id',
                        'patients.f_name',
                        'patients.l_name',
                        'users.f_name as doctor_f_name',
                        'users.l_name as doctor_l_name',
                        )
                        ->join("patients","patients.id",'=','appointments.patient_id')
                        ->join("users","users.id",'=','appointments.doct_id')
                        ->where("appointments.id","=", $appId)
                        ->first();
                  $doctName=$appointmentData->doctor_f_name ." ". $appointmentData->doctor_l_name;
                  $patientName=$appointmentData->f_name ." ". $appointmentData->l_name;
                  // Format the date to '2nd October 2024'
                  $date = Carbon::parse($appointmentData->date)->format('jS F Y');
            
                  // Assuming $appointmentData->time_slots contains time in 'H:i:s' format
                $time = Carbon::createFromFormat('H:i:s', $appointmentData->time_slots)->format('h:i A');

            if($appointmentData!=null&&$appointmentData->user_id!=null){
                $title="Refund Processed";
                $body="The amount of $amount for your rejected appointment with Dr. $doctName on $date has been refunded to your wallet. You can use this balance for future appointments.";
            
                    $this->addUserNotificationTable($title,$body,$appointmentData->user_id,$appId,null,null,null);
            }

            if($appointmentData!=null&&$appointmentData->doct_id!=null){
                $title="Refund Issued";
                $body= "A refund of $amount has been successfully issued to $patientName for the rejected appointment with Dr. $doctName on $date.";
                
                
                $this->addDoctorNotificationTable($title,$body,$appointmentData->doct_id,$appId,null,null);

        }
        
        if($appointmentData!=null){
            $title="Refund Issued";
            $body= "A refund of $amount has been successfully issued to $patientName for the rejected appointment with Dr. $doctName on $date.";
            $this->addAdminotificationTable($title,$body,$appId,null);
    }

    } 

    function sendAppointmentSatusChangeNotificationToUsers($appId,$status){

        $appointmentData= DB::table("appointments")
                        ->select('appointments.*',
                        'patients.user_id',
                        'patients.f_name',
                        'patients.l_name',
                        'users.f_name as doctor_f_name',
                        'users.l_name as doctor_l_name',
                        )
                        ->join("patients","patients.id",'=','appointments.patient_id')
                        ->join("users","users.id",'=','appointments.doct_id')
                        ->where("appointments.id","=", $appId)
                        ->first();
                  $doctName=$appointmentData->doctor_f_name ." ". $appointmentData->doctor_l_name;
                  $patientName=$appointmentData->f_name ." ". $appointmentData->l_name;
                  // Format the date to '2nd October 2024'
                  $date = Carbon::parse($appointmentData->date)->format('jS F Y');
            
                  // Assuming $appointmentData->time_slots contains time in 'H:i:s' format
                $time = Carbon::createFromFormat('H:i:s', $appointmentData->time_slots)->format('h:i A');

            if($appointmentData!=null&&$appointmentData->user_id!=null){

                if($status=="Confirmed"){
                       $title="Appointment Confirmed";
                        $body="Your appointment with Dr. $doctName on $date at $time has been confirmed. Please arrive on time.";
                }
                else if($status=="Visited"){
                    $title="Appointment Visited";
                    $body="Your appointment with Dr. $doctName on $date has been marked as Visited. We hope your visit went well.";
                      }

               else if($status=="Completed"){
                    $title="Appointment Completed";
                    $body="Your appointment with Dr. $doctName on $date has been marked as completed. We hope your visit went well.";
                      }

                      else if($status=="Rejected"){
                        $title="Appointment Rejected";
                        $body="Your appointment request with Dr. $doctName on $date has been rejected. Please try booking another slot or contact the clinic.";
                          }
                          else if($status=="Pending"){
                            $title="Appointment Marked as Pending";
                            $body="Your appointment request with Dr. $doctName on $date has been marked as pending. We will notify you once it's confirmed.";
                              }
                
                    $this->addUserNotificationTable($title,$body,$appointmentData->user_id,$appId,null,null,null);
            }

            if($appointmentData!=null&&$appointmentData->doct_id!=null){

                if($status=="Confirmed"){
                    $title="Appointment Confirmed";
                      $body="The appointment with $patientName on $date at $time has been confirmed.";
                     }
                     else if($status=="Completed"){
                        $title="Appointment Marked as Completed";
                        $body="The appointment with $patientName on $date has been marked as completed.";
                          }
                     else if($status=="Visited"){
                        $title="Appointment Marked as Visited";
                        $body="The appointment with $patientName on $date has been marked as Visited.";
                          }

                          else if($status=="Rejected"){
                            $title="Appointment Rejected";
                            $body="The appointment request from $patientName on $date at $time has been rejected.";
                              }

                              else if($status=="Pending"){
                                $title="Appointment Marked as Pending";
                                 $body="The appointment with $patientName on $date has been marked as pending.";
                                  }
                $this->addDoctorNotificationTable($title,$body,$appointmentData->doct_id,$appId,null,null);

             }
        
        if($appointmentData!=null){
            if($status=="Confirmed"){
                $title="Appointment Confirmed";
                  $body="The appointment for $patientName with Dr. $doctName on $date at $time has been confirmed.";
                  }
                  else if($status=="Visited"){
                    $title="Appointment Visited";
                    $body="The appointment for $patientName with Dr. $doctName on $date has been marked as Visited.";
                      }
                  else if($status=="Completed"){
                    $title="Appointment Completed";
                    $body="The appointment for $patientName with Dr. $doctName on $date has been marked as completed.";
                      }
                      else if($status=="Rejected"){
                        $title="Appointment Rejected";
                        $body="The appointment request for $patientName with Dr. $doctName on $date has been rejected.";
                          }

                          else if($status=="Pending"){
                            $title="Appointment Marked as Pending";
                            $body="The appointment for $patientName with Dr. $doctName on $date has been marked as pending.";
                              }
            
            $this->addAdminotificationTable($title,$body,$appId,null);
    }

    } 

    function sendWalletRshNotificationToUsersAgainstRejected($appId,$oldDateGet,$oldTimeGet){

        $appointmentData= DB::table("appointments")
                        ->select('appointments.*',
                        'patients.user_id',
                        'patients.f_name',
                        'patients.l_name',
                        'users.f_name as doctor_f_name',
                        'users.l_name as doctor_l_name',
                        )
                        ->join("patients","patients.id",'=','appointments.patient_id')
                        ->join("users","users.id",'=','appointments.doct_id')
                        ->where("appointments.id","=", $appId)
                        ->first();
                  $doctName=$appointmentData->doctor_f_name ." ". $appointmentData->doctor_l_name;
                  $patientName=$appointmentData->f_name ." ". $appointmentData->l_name;
                  // Format the date to '2nd October 2024'
                  $date = Carbon::parse($appointmentData->date)->format('jS F Y');
                  $oldDate = Carbon::parse($oldDateGet)->format('jS F Y');
                  // Assuming $appointmentData->time_slots contains time in 'H:i:s' format
                $time = Carbon::createFromFormat('H:i:s', $appointmentData->time_slots)->format('h:i A');
                $oldTime = Carbon::createFromFormat('H:i:s', $oldTimeGet)->format('h:i A');


            if($appointmentData!=null&&$appointmentData->user_id!=null){
                $title="Appointment Rescheduled";
                $body="Your appointment with Dr. $doctName on $oldDate at $oldTime has been rescheduled to $date at $time. Please review the new schedule and contact us if you have any questions.";
            
                    $this->addUserNotificationTable($title,$body,$appointmentData->user_id,$appId,null,null,null);
            }

            if($appointmentData!=null&&$appointmentData->doct_id!=null){
                $title="Appointment Rescheduled";
                $body="The appointment for $patientName on $oldDate at $oldTime has been rescheduled to $date at $time. Please review your updated schedule";
                
                $this->addDoctorNotificationTable($title,$body,$appointmentData->doct_id,$appId,null,null);

        }
        
        if($appointmentData!=null){
            $title="Appointment Rescheduled";
            $body= "You have successfully rescheduled the appointment for $patientName with Dr. $doctName from $oldDate at $oldTime to $date at $time.";
            $this->addAdminotificationTable($title,$body,$appId,null);
    }

    } 
    //Appointment End

    //Prescription
    function sendPrescrptionNotificationToUsers($appId,$prId,$status)
    {
       

        $appointmentData= DB::table("appointments")
                        ->select('appointments.*',
                        'patients.user_id',
                        'patients.f_name',
                        'patients.l_name',
                        'users.f_name as doctor_f_name',
                        'users.l_name as doctor_l_name',
                        )
                        ->join("patients","patients.id",'=','appointments.patient_id')
                        ->join("users","users.id",'=','appointments.doct_id')
                        ->where("appointments.id","=", $appId)
                        ->first();
                  $doctName=$appointmentData->doctor_f_name ." ". $appointmentData->doctor_l_name;
                  $patientName=$appointmentData->f_name ." ". $appointmentData->l_name;
                  // Format the date to '2nd October 2024'
                  $date = Carbon::parse($appointmentData->date)->format('jS F Y');
            
                  // Assuming $appointmentData->time_slots contains time in 'H:i:s' format
                $time = Carbon::createFromFormat('H:i:s', $appointmentData->time_slots)->format('h:i A');
                if($status=="Add"){
                    if($appointmentData!=null&&$appointmentData->user_id!=null){
                        $title="New Prescription Added!";
                        $body="A new prescription has been added by Dr. $doctName for your recent visit on $date.";
                        $this->addUserNotificationTable($title,$body,$appointmentData->user_id,$appId,null,$prId,null);
                }
    
                if($appointmentData!=null&&$appointmentData->doct_id!=null){
                    $title="Prescription Issued";
                    $body="You have successfully issued a prescription for $patientName.";
    
                    $this->addDoctorNotificationTable($title,$body,$appointmentData->doct_id,$appId,$prId,null);
    
                }

           }

           if($status=="Update"){
            if($appointmentData!=null&&$appointmentData->user_id!=null){
                $title="Prescription Updated";
                $body="Your prescription from Dr. $doctName has been updated. Please review the new details in the app";
                $this->addUserNotificationTable($title,$body,$appointmentData->user_id,$appId,null,$prId,null);
        }

        if($appointmentData!=null&&$appointmentData->doct_id!=null){
            $title="Prescription Updated";
            $body="You have updated the prescription for $patientName on $date.";

            $this->addDoctorNotificationTable($title,$body,$appointmentData->doct_id,$appId,$prId,null);

    }

        }
        
        if($status=="Delete"){
            if($appointmentData!=null&&$appointmentData->user_id!=null){
                $title="Prescription Deleted";
                $body="Your prescription from Dr. $doctName has been deleted.";
                $this->addUserNotificationTable($title,$body,$appointmentData->user_id,$appId,null,$prId,null);
        }

        if($appointmentData!=null&&$appointmentData->doct_id!=null){
            $title="Prescription Deleted";
            $body="You have deleted the prescription for $patientName on $date.";

            $this->addDoctorNotificationTable($title,$body,$appointmentData->doct_id,$appId,$prId,null);

    }

        }
    }

   //Prescription End

//Files
    //Prescription
    function sendFileNotificationToUsers($fileID,$status){
       

        $appointmentData= DB::table("patient_files")
                        ->select('patient_files.*',
                        'patients.user_id',
                        'patients.f_name',
                        'patients.l_name'
                        )
                        ->join("patients","patients.id",'=','patient_files.patient_id')
                        ->where("patient_files.id","=", $fileID)
                        ->first();
                  $fileName=$appointmentData->file_name;
                  $patientName=$appointmentData->f_name ." ". $appointmentData->l_name;
                  // Format the date to '2nd October 2024'

                if($status=="Add"){
                    if($appointmentData!=null&&$appointmentData->user_id!=null){
                        $title="Patient File $fileName Added Successfully";
                        $body="A new file, $fileName has been added for $patientName. Please review the patient's details and medical history for any necessary follow-up.";
                        $this->addUserNotificationTable($title,$body,$appointmentData->user_id,null,null,null,$fileID);
                }
    
           }

           if($status=="Update"){
            if($appointmentData!=null&&$appointmentData->user_id!=null){
                $title="Patient File $fileName Updated Successfully";
                $body="$fileName has been Updated for $patientName. Please review the patient's details and medical history for any necessary follow-up.";
                $this->addUserNotificationTable($title,$body,$appointmentData->user_id,null,null,null,$fileID);
        }


        }
        
        // if($status=="Delete"){
        //     if($appointmentData!=null&&$appointmentData->user_id!=null){
        //         $title="Patient File Deleted";
        //         $body="Your prescription from Dr. $doctName has been deleted.";
        //         $this->addUserNotificationTable($title,$body,$appointmentData->user_id,null,null,null,$fileID);
        // }

        // }
    }
//Files End


   //Appointment Check in


      function sendAppointmentCheckInNotificationToUsers($appId,$dateG,$timeG,$status){

        $appointmentData= DB::table("appointments")
                        ->select('appointments.*',
                        'patients.user_id',
                        'patients.f_name',
                        'patients.l_name',
                        'users.f_name as doctor_f_name',
                        'users.l_name as doctor_l_name',
                        )
                        ->join("patients","patients.id",'=','appointments.patient_id')
                        ->join("users","users.id",'=','appointments.doct_id')
                        ->where("appointments.id","=", $appId)
                        ->first();
                  $doctName=$appointmentData->doctor_f_name ." ". $appointmentData->doctor_l_name;
                  $patientName=$appointmentData->f_name ." ". $appointmentData->l_name;
                  // Format the date to '2nd October 2024'
                  $date = $dateG==null?Carbon::parse($appointmentData->date)->format('jS F Y'):Carbon::parse($dateG)->format('jS F Y');
            
                  // Assuming $appointmentData->time_slots contains time in 'H:i:s' format
                $time = $timeG==null?Carbon::parse($appointmentData->time_slots)->format('jS F Y'):Carbon::createFromFormat('H:i:s', $timeG)->format('h:i A');
                if($status=="Add"){

                    if($appointmentData!=null&&$appointmentData->user_id!=null){
                        $title="Checked In Successfully";
                        $body="You have successfully checked in for your appointment with Dr. $doctName on $date.. Please wait for your turn.";
                        $this->addUserNotificationTable($title,$body,$appointmentData->user_id,$appId,null,null,null);
                }
    
                if($appointmentData!=null&&$appointmentData->doct_id!=null){
                    $title="Patient Checked In";
                    $body="$patientName has checked in for their appointment on $date. Please be ready for the consultation.";
    
                    $this->addDoctorNotificationTable($title,$body,$appointmentData->doct_id,$appId,null,null);
    
                }

        }

        else if($status=="Delete"){

            if($appointmentData!=null&&$appointmentData->user_id!=null){
                $title="Check-In Cancelled";
                $body="Your check-in for the appointment with Dr. $doctName on $date has been cancelled. Please check the app for further details.";
                $this->addUserNotificationTable($title,$body,$appointmentData->user_id,$appId,null,null,null);
        }

        if($appointmentData!=null&&$appointmentData->doct_id!=null){
            $title="Check-In Cancelled";
            $body="$patientName has cancelled their check-in for the appointment on $date at $time.";

            $this->addDoctorNotificationTable($title,$body,$appointmentData->doct_id,$appId,null,null);

        }

}
       
    }

    //wallet
    function sendWalletNotificationToUsers($uid,$transaction_type,$amount,$txnId){

        $userData= DB::table("users")
                        ->select('users.*',
                        'users.f_name',
                        'users.l_name'
                        )
                        ->where('users.id','=',$uid)
                        ->first();
                        if($userData==null){
                            return;
                        }
                  $uName=$userData->f_name ." ". $userData->l_name;

                if($transaction_type=="Credited"){
                    $title="Wallet Credited";
                    $body="An amount of $amount has been credited to your wallet.";
                }
                
                else if($transaction_type=="Debited"){
                    $title="Wallet Debited";
                    $body="An amount of $amount has been debited to your wallet.";
                }
                 
                    $this->addUserNotificationTable($title,$body,$uid,null,$txnId,null,null);
            

            if($transaction_type=="Credited"){
                $title="Wallet Credited";
                $body="$uName wallet has been credited with $amount.";
            }
          else  if($transaction_type=="Debited"){
                $title="Wallet Debited";
                $body="$uName wallet has been debited with $amount.";
            }
       
            $this->addAdminotificationTable($title,$body,null,$txnId);
    

    }
    
  
    function addUserNotificationTable($title,$body,$user_id,$appId,$txnId,$prescription_id,$file_id,$lab_booking_id=null){
                    $timeStamp = date("Y-m-d H:i:s");
                    $userModel = new UserNotificationModel;
                    $userModel->title = $title;
                    $userModel->body = $body;
                    $userModel->user_id = $user_id;
                    $userModel->txn_id = $txnId;
                    $userModel->appointment_id = $appId; 
                    $userModel->file_id = $file_id; 
                    $userModel->lab_booking_id = $lab_booking_id;  
                    $userModel->prescription_id = $prescription_id;  
                    $userModel->created_at = $timeStamp;
                    $userModel->updated_at = $timeStamp;
                    $res=  $userModel->save();
                    if($res){
                      $userData= DB::table("users")
                      ->select('users.*'  )
                      ->where('users.id','=',$user_id)
                      ->first();
                      if($userData){
                          if($userData->fcm!=null){
                              app('App\Http\Controllers\Api\V1\SendNotificationController')->sendFirebaseNotificationToToken($title,$body,'',$userData->fcm); 
                          }
                          if($userData->web_fcm!=null){
                              app('App\Http\Controllers\Api\V1\SendNotificationController')->sendFirebaseNotificationToToken($title,$body,'',$userData->web_fcm); 
                          }
                      }
                 
                
                    }
    }

    
    function addDoctorNotificationTable($title,$body,$doct_id,$appId,$prescription_id,$file_id){
        $timeStamp = date("Y-m-d H:i:s");
        $userModel = new DoctorNotificationModel;
        $userModel->title = $title;
        $userModel->body = $body;
        $userModel->doctor_id = $doct_id;
        $userModel->prescription_id = $prescription_id;  
        $userModel->appointment_id = $appId; 
        $userModel->file_id = $file_id; 
        $userModel->created_at = $timeStamp;
        $userModel->updated_at = $timeStamp;
      $res=  $userModel->save();
      if($res){
        $userData= DB::table("users")
        ->select('users.*'  )
        ->where('users.id','=',$doct_id)
        ->first();
        if($userData){
            if($userData->fcm!=null){
                app('App\Http\Controllers\Api\V1\SendNotificationController')->sendFirebaseNotificationToToken($title,$body,'',$userData->fcm); 
            }
            if($userData->web_fcm!=null){
                app('App\Http\Controllers\Api\V1\SendNotificationController')->sendFirebaseNotificationToToken($title,$body,'',$userData->web_fcm); 
            }
        }
   

      }
    }

         
    function addLabNotificationTable($title,$body,$pathology_id,$lab_booking_id,){
        $timeStamp = date("Y-m-d H:i:s");
        $userModel = new LabNotificationModel;
        $userModel->title = $title;
        $userModel->body = $body;
        $userModel->pathology_id = $pathology_id;
        $userModel->lab_booking_id = $lab_booking_id; 
        $userModel->created_at = $timeStamp;
        $userModel->updated_at = $timeStamp;
         $res=  $userModel->save();
      if($res){
          $pathData= DB::table("pathologist")
        ->select('pathologist.*'  )
        ->where('pathologist.id','=',$pathology_id)
        ->first();
        $userData= DB::table("users")
        ->select('users.*'  )
        ->where('users.id','=',$pathData->user_id)
        ->first();
        if($userData){
            if($userData->fcm!=null){
                app('App\Http\Controllers\Api\V1\SendNotificationController')->sendFirebaseNotificationToToken($title,$body,'',$userData->fcm); 
            }
            if($userData->web_fcm!=null){
                app('App\Http\Controllers\Api\V1\SendNotificationController')->sendFirebaseNotificationToToken($title,$body,'',$userData->web_fcm); 
            }
        }
   

      }


}

function addAdminotificationTable($title,$body,$appId,$txnId,$lab_booking_id=null){
    $timeStamp = date("Y-m-d H:i:s");
    $userModel = new AdminNotificationModel;
    $userModel->title = $title;
    $userModel->body = $body;
    $userModel->lab_booking_id = $lab_booking_id;
    $userModel->appointment_id = $appId; 
    $userModel->txn_id = $txnId;
    $userModel->created_at = $timeStamp;
    $userModel->updated_at = $timeStamp;
    $clinicId=null;
    if($appId){
        $appointmentData= DB::table("appointments")
                        ->select('appointments.*',
                        )
                        ->where("appointments.id","=", $appId)
                        ->first();
                        if($appointmentData){
                            $clinicId =$appointmentData->clinic_id;
                        }
    }


    elseif($txnId){
        $txnData= DB::table("all_transaction")
                        ->select('all_transaction.*',
                        )
                        ->where("all_transaction.id","=", $txnId)
                        ->first();
                        if($txnData){
                            $clinicId =$txnData->clinic_id;
                        }
    }
    $userModel->clinic_id = $clinicId;
    $res=  $userModel->save();
    if($res){
    $this->sendNotificationToAllAdminAndFrontDesk($title,$body,$clinicId);

    }
}
   
function sendNotificationToAllAdminAndFrontDesk($title,$body,$clinicId){
    $userData = DB::table("users_role_assign")
    ->select(
        'users_role_assign.*',
        'users.f_name',
        'users.l_name',
        'users.web_fcm',
        'users.fcm',
          'users.clinic_id'
    )
    ->join('users', 'users.id', '=', 'users_role_assign.user_id')
    ->where('users.clinic_id', '=', $clinicId)  // Filter by specific clinic
    ->where(function ($query) {
        $query->where('users_role_assign.role_id', 14)  // Admin
              ->orWhere('users_role_assign.role_id', 16);  // Front desk
    })
    ->get();

      foreach ($userData as $user) {

          if($user->fcm!=null){
              app('App\Http\Controllers\Api\V1\SendNotificationController')->sendFirebaseNotificationToToken($title,$body,'',$user->fcm); 
          }
          if($user->web_fcm!=null){
              app('App\Http\Controllers\Api\V1\SendNotificationController')->sendFirebaseNotificationToToken($title,$body,'',$user->web_fcm); 
          }
    }

}

}

