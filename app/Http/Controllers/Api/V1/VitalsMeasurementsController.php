<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\VitalsMeasurementsModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\ImageModel;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
class VitalsMeasurementsController extends Controller
{
    
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
                  $dataModel= VitalsMeasurementsModel::where("id",$request->id)->first();
                  
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

    function updateData(Request $request){
        $initialCheck=false;
        $validator = Validator::make(request()->all(), [
          'id'=>'required'
      ]);
      if ($validator->fails())
      return response (["response"=>400],400);
      
               try{
                  $timeStamp= date("Y-m-d H:i:s");
                  $dataModel= VitalsMeasurementsModel::where("id",$request->id)->first();
        
                  if(isset($request->date)){$dataModel->date = $request->date ;} 
                  if(isset($request->time)){$dataModel->time = $request->time ;} 
                 if(isset($request->bp_systolic)){$dataModel->bp_systolic = $request->bp_systolic ;} 
                 if(isset($request->bp_diastolic)){$dataModel->bp_diastolic = $request->bp_diastolic ;} 
                 if(isset($request->weight)){$dataModel->weight = $request->weight ;} 
                 if(isset($request->spo2)){$dataModel->spo2 = $request->spo2 ;} 
                 if(isset($request->temperature)){$dataModel->temperature = $request->temperature ;} 
                 if(isset($request->sugar_random)){$dataModel->sugar_random = $request->sugar_random ;} 
                 if(isset($request->sugar_fasting)){$dataModel->sugar_fasting = $request->sugar_fasting ;} 
         
                 
                     $dataModel->updated_at=$timeStamp;
         
               
             $qResponce= $dataModel->save();
             if($qResponce)
             {
              
                 return Helpers::successResponse("successfully");
               }
 
             else 
             {
            
                 return Helpers::errorResponse("error");
               }
             
            }
                catch(\Exception $e){
                    return Helpers::errorResponse("error");
                }
        
          }
    function getData()
    {

      $data = DB::table("vitals_measurements")
      ->select('vitals_measurements.*'
      )
            ->orderBy("vitals_measurements.date","DESC")
            ->orderBy("vitals_measurements.time","DESC")
        ->get();
      
            $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
        }
    
    function getDataById($id)
    {

      $data = DB::table("vitals_measurements")
      ->select('vitals_measurements.*'
      )
       ->where("vitals_measurements.id","=",$id)
        ->first();
            $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
    }
    function getDataByUserId($id)
    {

      $data = DB::table("vitals_measurements")
      ->select('vitals_measurements.*'
      )
       ->where("vitals_measurements.user_id","=",$id)
       ->orderBy("vitals_measurements.date","DESC")
       ->orderBy("vitals_measurements.time","DESC")
        ->get();
            $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
    }
    function getDataByFamilyMemberId($id)
    {

      $data = DB::table("vitals_measurements")
      ->select('vitals_measurements.*'
      )
       ->where("vitals_measurements.family_member_id","=",$id)
       ->orderBy("vitals_measurements.date","DESC")
       ->orderBy("vitals_measurements.time","DESC")
        ->get();
            $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
    }
    function getDataByFamilyMemberIdType(Request $request)
    {
    
      $validator = Validator::make(request()->all(), [
        'family_member_id' => 'required',
        'type' => 'required'
        
]);


if ($validator->fails())
  return response (["response"=>400],400);

      $id=$request->family_member_id;
      $type=$request->type;

      $query = DB::table("vitals_measurements")
      ->select('vitals_measurements.*'
      )
       ->where("vitals_measurements.family_member_id","=",$id)
       ->where("vitals_measurements.type","=",$type)
       ->orderBy("vitals_measurements.date","DESC")
       ->orderBy("vitals_measurements.time","DESC");

       if (isset($request->start_date) && isset($request->end_date)) {
        // Apply the date range filter using whereBetween
        $query->whereBetween('vitals_measurements.date', [$request->start_date, $request->end_date]);
    }
      
    $data=$query->get();
      
            $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
    }
  
    function addData(Request $request)
    {
        DB::beginTransaction();
        
        $validator = Validator::make(request()->all(), [
            'user_id' => 'required',
            'family_member_id' => 'required',
              'date'=>'required',
                   'time'=>'required'

            
    ]);
  
    if ($validator->fails())
      return response (["response"=>400],400);
        else{
        
                  try{
                    $timeStamp= date("Y-m-d H:i:s");
                    $date= $request->date ;
                    $time=  $request->time ;
                    $dataModel=new VitalsMeasurementsModel;
                    $dataModel->user_id = $request->user_id ;
                   if(isset($request->bp_systolic)){$dataModel->bp_systolic = $request->bp_systolic ;} 
                   if(isset($request->bp_diastolic)){$dataModel->bp_diastolic = $request->bp_diastolic ;} 
                   if(isset($request->weight)){$dataModel->weight = $request->weight ;} 
                   if(isset($request->spo2)){$dataModel->spo2 = $request->spo2 ;} 
                   if(isset($request->temperature)){$dataModel->temperature = $request->temperature ;} 
                   if(isset($request->sugar_random)){$dataModel->sugar_random = $request->sugar_random ;} 
                   if(isset($request->sugar_fasting)){$dataModel->sugar_fasting = $request->sugar_fasting ;} 
                  $dataModel->family_member_id = $request->family_member_id ;
                   if(isset($request->type)){$dataModel->type = $request->type ;}
                   $dataModel->date = $date;
                   $dataModel->time = $time ;
            
                    $dataModel->created_at=$timeStamp;
                    $dataModel->updated_at=$timeStamp;
                    $qResponce= $dataModel->save();
                    if($qResponce)
                    {
                        DB::commit();
                        return Helpers::successResponse("successfully");
                      }
        
                    else 
                    {
                        DB::rollBack();
                   
                        return Helpers::errorResponse("error");
                      }
                               
                  }catch(\Exception $e){
                    DB::rollBack();
              
                    return Helpers::errorResponse("error $e");
                  }
      
       }
       
      }

}
