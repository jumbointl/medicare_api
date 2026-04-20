<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppointmentModel;

use App\Models\DoctorsReviewModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Http\Request;

class DoctorsReviewController extends Controller
{


  public function getData(Request $request)
  {


      // Define the base query

      $query = DB::table("doctors_review")
      ->select('doctors_review.*',
      'patients.f_name as f_name',
      'patients.l_name as l_name',
      'users.f_name as doct_f_name',
      'users.l_name as doct_l_name'
      )
      ->join("users", "users.id", "=", "doctors_review.doctor_id")
      ->join("patients", "patients.id", "=", "doctors_review.user_id")
       ->orderBy('doctors_review.created_at','DESC');
      if (!empty($request->doctor_id)) {
       
        $query->where('doctors_review.doctor_id', '=', $request->doctor_id);
        
    }
      
          if (!empty($request->start_date)) {
            $query->whereDate('doctors_review.created_at', '>=', $request->start_date);
        }
        
        if (!empty($request->end_date)) {
            $query->whereDate('doctors_review.created_at', '<=', $request->end_date);
        }
  
        if (!empty($request->clinic_id)) {
          $query->where('doctors_review.clinic_id', $request->clinic_id);
      }

      if ($request->has('search')) {
          $search = $request->input('search');
          $query->where(function ($q) use ($search) {
              $q->where('doctors_review.appointment_id', 'like', '%' . $search . '%')
              ->orWhere('doctors_review.user_id', 'like', '%' . $search . '%')
              ->orWhere('doctors_review.id', 'like', '%' . $search . '%')
              ->orWhere('doctors_review.description', 'like', '%' . $search . '%')    
              ->orWhere(DB::raw('CONCAT(patients.f_name, " ", patients.l_name)'), 'like', '%' . $search . '%')
              ->orWhere(DB::raw('CONCAT(users.f_name, " ", users.l_name)'), 'like', '%' . $search . '%');
          });
      }

  
      $total_record = $query->get()->count();
      if ($request->filled(['start', 'end'])) {
          $start = (int) $request->start;
          $end = (int) $request->end;
          $query->skip($start)->take($end - $start);
      }
  
      // Fetch paginated data
      $data = $query->get();
      if (!empty($request->doctor_id)) {
        $totalReviewPoints = $data->sum('points'); // Assuming 'review_points' is the column name for review points

        // Count the number of reviews
        $numberOfReviews = $data->count();
           // Calculate the average rating
           $averageRating = $numberOfReviews > 0 ? number_format($totalReviewPoints / $numberOfReviews, 2) : '0.00';
        $response = [
          'total_review_points' => $totalReviewPoints,
          'number_of_reviews' => $numberOfReviews,
          'average_rating'=>$averageRating,
          "response" => 200,
          "total_record" => $total_record,
          'data' => $data,
      ];
      }
      else{
        $response = [
          "response" => 200,
          "total_record" => $total_record,
          'data' => $data,
      ];
      }
      

      return response()->json($response, 200);
  }


    function addData(Request $request)
    {
        
        $validator = Validator::make(request()->all(), [
            'doctor_id' => 'required',
            'points' => 'required',
            'appointment_id'=> 'required'
        
            
    ]);
  
    if ($validator->fails())
      return response (["response"=>400],400);
        else{
        
                  try{
                    if(isset($request->appointment_id)){
                    $alreadyExists = DoctorsReviewModel::where('appointment_id', '=', $request->appointment_id)->first();
                    if ($alreadyExists != null) {
                        return Helpers::errorResponse("you have already submitted a review for this appointment");
                    }
                }
                    $timeStamp= date("Y-m-d H:i:s");
                    $dataModel=new DoctorsReviewModel;
                    $appData = AppointmentModel::where('id', '=', $request->appointment_id)->first();
                    $dataModel->doctor_id = $request->doctor_id ;
                    $dataModel->points = $request->points;
           
                    $dataModel->user_id = $appData->patient_id;

                    if($appData){   $dataModel->clinic_id = $appData->clinic_id;}
 
                    if(isset($request->description)){
                      $dataModel->description  = $request->description;
                    }
                    if(isset($request->appointment_id)){
                        $dataModel->appointment_id   = $request->appointment_id ;
                      }
                  
                    $dataModel->created_at=$timeStamp;
                    $dataModel->updated_at=$timeStamp;
                    $qResponce= $dataModel->save();
                       if($qResponce){
                        
                        return Helpers::successWithIdResponse("successfully",$dataModel->id);
                
                        }else 
                    {
                        return Helpers::errorResponse("error");
                    }
                    
                               
                  }catch(\Exception $e){
              
                    return Helpers::errorResponse("error");
                  }
                
            
      
       }
       
      }
}
