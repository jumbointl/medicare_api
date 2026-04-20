<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SpecializationModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;

class SpecializationController extends Controller
{
        //add new data
        function addData(Request $request){

    
            $validator = Validator::make(request()->all(), [
                'title' => 'required'
          ]);
          if ($validator->fails())
          return response (["response"=>400],400);
         
          try{
                $alreadyAddedModel= SpecializationModel::where("title",$request->title)->first();
                if($alreadyAddedModel)
                {
                    return Helpers::errorResponse("title already exists");
                
                }else{
                 
    
                    $timeStamp= date("Y-m-d H:i:s");
                    $dataModel=new SpecializationModel;
                    
                    $dataModel->title = $request->title ;
                   
                    $dataModel->created_at=$timeStamp;
                    $dataModel->updated_at=$timeStamp;
                   
                    $qResponce = $dataModel->save();
                    if($qResponce)
                   {
              
                    
                    return Helpers::successWithIdResponse("successfully",$dataModel->id);}
                
                    else 
                    { 
                        
                        return Helpers::errorResponse("error");}
                }
               
            }
    
         catch(\Exception $e){
               
                  
                        return Helpers::errorResponse("error");
                      }
    }

    // Update data
function updateData(Request $request){


    $validator = Validator::make(request()->all(), [
        'id' => 'required'
  ]);
  if ($validator->fails())
  return response (["response"=>400],400);
    try{

        $dataModel= SpecializationModel::where("id",$request->id)->first();
        if(isset($request->title)){
            $alreadyExists = SpecializationModel::where('title', '=', $request->title)->where('id',"!=",$request->id)->first();
            if ($alreadyExists != null)
            {
                return Helpers::errorResponse("title already exists");
            }
            else{
                $dataModel->title= $request->title;
            }
        }


        $timeStamp= date("Y-m-d H:i:s");
        $dataModel->updated_at=$timeStamp;
                $qResponce= $dataModel->save();
                if($qResponce)
                {
              
                    return Helpers::successResponse("successfully");}
    
                else 
                {
               
                    return Helpers::errorResponse("error");}
    }
    

 catch(\Exception $e){
                return Helpers::errorResponse("error");
              }
            }

            
            //Delete Data
        function deleteData(Request $request){

            $validator = Validator::make(request()->all(), [
                'id' => 'required'
          ]);
          if ($validator->fails())
          return response (["response"=>400],400);
            try{
        
                $dataModel= SpecializationModel::where("id",$request->id)->first();

                        $qResponce= $dataModel->delete();
                        if($qResponce)
                        {
                      
                            return Helpers::successResponse("successfully");}
            
                        else 
                        {
                       
                            return Helpers::errorResponse("error");}
            }
            
        
         catch(\Exception $e){
                        return Helpers::errorResponse("error");
                      }
    }

    // get data

    function getData()
    {

      $data = DB::table("specialization")
      ->select('specialization.*'
      )
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

      $data = DB::table("specialization")
      ->select('specialization.*')
      ->where('id','=',$id)
        ->first();
      
            $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
        }
}
