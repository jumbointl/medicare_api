<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClinicDoctorModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class ClinicDoctorController extends Controller
{
    //add new data
    function addData(Request $request){

    
        $validator = Validator::make(request()->all(), [
            'clinic_id' => 'required',
             'doctor_id' => 'required'
      ]);
 
      if ($validator->fails())
      return response (["response"=>400],400);
     
      try{

        $alreadyAddedModel= ClinicDoctorModel::where("clinic_id",$request->clinic_id)->where("doctor_id",$request->doctor_id)->first();
        if($alreadyAddedModel)
        {
            return Helpers::errorResponse("Doctor already linked with clinic");
 
        }

                DB::beginTransaction();

                        
                $timeStamp= date("Y-m-d H:i:s");
                $dataModel=new ClinicDoctorModel;
                
                $dataModel->clinic_id = $request->clinic_id ;
                $dataModel->doctor_id  = $request->doctor_id;
                $dataModel->created_at=$timeStamp;
                $dataModel->updated_at=$timeStamp;
          
         
                $qResponce = $dataModel->save();
                if($qResponce)
               {
      
                DB::commit();
                
                return Helpers::successWithIdResponse("successfully",$dataModel->id);
            
            }
            
                else 
                {   DB::rollBack();
                    
                    return Helpers::errorResponse("error");}
            
           
        }

     catch(\Exception $e){
             DB::rollBack();
              
                    return Helpers::errorResponse("error $e");
                  }
}



               // get data

    function getData()
    {

      $data = DB::table("clinics")
      ->select('clinics.*',
        'cities.title as city_title',
      'states.title as state_title'
      )
      ->join('cities', 'cities.id', '=', 'clinics.city_id')
      ->join('states', 'states.id', '=', 'cities.state_id')
      ->OrderBy('created_at', 'desc')
        ->get();
      
            $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
        }

        
           // get data by id

    function getDataById($id)
    {

        $data = DB::table("cities")
      ->select('cities.*',
      'states.title as state_title'
      )
        ->join('states', 'states.id', '=', 'cities.state_id')
      ->where('cities.id','=',$id)
        ->first();
      
            $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
        }

        function deleteData(Request $request){
            $validator = Validator::make(request()->all(), [
                'id' => 'required'
          ]);
          if ($validator->fails())
          return response (["response"=>400],400);
            try{ 
                    $dataModel= ClinicDoctorModel::where("id",$request->id)->first();
               
                    $qResponce= $dataModel->delete();
                    if($qResponce){
                     

                    return Helpers::successResponse("successfully Deleted");}
                    else 
                    return Helpers::errorResponse("error");    
             
            }
        
         catch(\Exception $e){
                  
                        return Helpers::errorResponse("error");
                      }         
        
        }

}
