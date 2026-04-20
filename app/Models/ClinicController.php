<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClinicsModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\RoleAssignModel;

class ClinicController extends Controller
{
    //add new data
    function addData(Request $request){

    
        $validator = Validator::make(request()->all(), [
            'title' => 'required',
             'email' => 'required',
             'password' => 'required',
             'f_name'   => 'required',
             'l_name'   => 'required',
             'city_id'   => 'required'
      ]);
 
      if ($validator->fails())
      return response (["response"=>400],400);
     
      try{
            $alreadyAddedModel= ClinicsModel::where("title",$request->title)->where("city_id",$request->city_id)->first();
            if($alreadyAddedModel)
            {
                return Helpers::errorResponse("title already exists");
            
            }else{
                DB::beginTransaction();

                $timeStamp= date("Y-m-d H:i:s");
                $dataModel=new ClinicsModel;
                
                $dataModel->title = $request->title ;
                $dataModel->city_id  = $request->state_id;
            
                $dataModel->created_at=$timeStamp;
                $dataModel->updated_at=$timeStamp;
          
         
                $qResponce = $dataModel->save();
                if($qResponce)
               {

                $userModel=new User;
                              
                      $userModel->email= $request->email;                               
                      $userModel->password= Hash::Make($request->password);
                    
             
                    $userModel->f_name=$request->f_name;
                    $userModel->l_name=$request->l_name;
                    $qResponceC= $userModel->save();
                    if(!$qResponceC) 
                    {   DB::rollBack();
                        
                        return Helpers::errorResponse("error");
                    }

                    $dataModel= new RoleAssignModel;
                    $dataModel->role_id=21;
                    $dataModel->user_id=$userModel->id;
                    $dataModel->created_at=$timeStamp;
                    $dataModel->updated_at=$timeStamp;
                    $qResponceR =$dataModel->save();

                        if(!$qResponceR) 
                        {   DB::rollBack();
                            
                            return Helpers::errorResponse("error");
                        }

                DB::commit();
                
                return Helpers::successWithIdResponse("successfully",$dataModel->id);
            
            }
            
                else 
                {   DB::rollBack();
                    
                    return Helpers::errorResponse("error");}
            }
           
        }

     catch(\Exception $e){
             DB::rollBack();
              
                    return Helpers::errorResponse("error");
                  }
}
// Update Deapartment
function updateData(Request $request){
    
    $validator = Validator::make(request()->all(), [
        'id' => 'required'
  ]);
  if ($validator->fails())
  return response (["response"=>400],400);
    try{
        DB::beginTransaction();
        $dataModel= CityModel::where("id",$request->id)->first();
        if(isset($request->title)){
            $alreadyExists = CityModel::where('title', '=', $request->title)->where('id',"!=",$request->id)->where("state_id",$request->state_id)->first();
            if ($alreadyExists != null)
            {
                return Helpers::errorResponse("title already exists");
            }
            else{
                $dataModel->title= $request->title;
            }
        }
        if(isset($request->state_id)){
            $dataModel->state_id= $request->state_id;
        }
        if(isset($request->active)){
            $dataModel->active= $request->active;
        }

        if(isset($request->latitude)){
            $dataModel->latitude= $request->latitude;
        }
        if(isset($request->longitude)){
            $dataModel->longitude= $request->longitude;
        }

        
    

        $timeStamp= date("Y-m-d H:i:s");
        $dataModel->updated_at=$timeStamp;
                $qResponce= $dataModel->save();
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




               // get data

    function getData()
    {

      $data = DB::table("cities")
      ->select('cities.*',
      'states.title as state_title'
      )
        ->join('states', 'states.id', '=', 'cities.state_id')
      ->OrderBy('created_at', 'desc')
        ->get();
      
            $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
        }
        function getDataActive()
        {
    
          $data = DB::table("cities")
          ->select('cities.*')
          ->where('active',1)
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
                    $dataModel= CityModel::where("id",$request->id)->first();
               
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
