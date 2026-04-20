<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClinicImageModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\RoleAssignModel;

class ClinicImagesController extends Controller
{
     //add new data
     function addData(Request $request){

    
        $validator = Validator::make(request()->all(), [
            'image' => 'required',
            'clinic_id' => 'required'
      ]);
      if ($validator->fails())
      return response (["response"=>400],400);
     
      try{
    
                DB::beginTransaction();

                $timeStamp= date("Y-m-d H:i:s");
                $dataModel=new ClinicImageModel;
                
                $dataModel->clinic_id = $request->clinic_id ;
               
                $dataModel->created_at=$timeStamp;
                $dataModel->updated_at=$timeStamp;
                if(isset($request->image)){
        
                      $dataModel->image =  $request->hasFile('image') ? Helpers::uploadImage('clinic/', $request->file('image')) : null;
                }
     
               
                $qResponce = $dataModel->save();
                if($qResponce)
               {
                DB::commit();
                
                return Helpers::successWithIdResponse("successfully",$dataModel->id);}
            
                else 
                {   DB::rollBack();
                    
                    return Helpers::errorResponse("error");}
            
           
        }

     catch(\Exception $e){
             DB::rollBack();
              
                    return Helpers::errorResponse("error");
                  }
}



 // Remove Image
function removeImage(Request $request){


    $validator = Validator::make(request()->all(), [
        'id' => 'required'
  ]);
  if ($validator->fails())
  return response (["response"=>400],400);
    try{
        $dataModel= ClinicImageModel::where("id",$request->id)->first();
  

            $oldImage = $dataModel->image;
            if(isset($oldImage)){
                if($oldImage!="def.png"){
                    Helpers::deleteImage($oldImage);
                }

                $dataModel->image=null;
            }
 
            $timeStamp= date("Y-m-d H:i:s");
            $dataModel->updated_at=$timeStamp;
            
                $qResponce= $dataModel->save();
                if($qResponce)
                return Helpers::successResponse("successfully");
    
                else 
                return Helpers::errorResponse("error");
    }
    

 catch(\Exception $e){
          
                return Helpers::errorResponse("error");
              }
            }

               // get data

    function getData(Request $request)
    {

      $query = DB::table("clinic_images")
      ->select('clinic_images.*'
    );
    if(!empty($request->clinic_id)){
        $query->where('clinic_images.clinic_id',$request->clinic_id);
    }
    $data = $query->orderBy('clinic_images.created_at','DESC') ->get();
      
            $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
        }

        

           // get data by id

    function getDataById($id)
    {

      $data = DB::table("clinic_images")
      ->select('clinic_images.*')
      ->where('id','=',$id)
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
                    $dataModel= ClinicImageModel::where("id",$request->id)->first();
                    $oldImage = $dataModel->image;
                    $qResponce= $dataModel->delete();
                    if($qResponce){
                     
                        if(isset($oldImage)){
                            if($oldImage!="def.png"){
                                Helpers::deleteImage($oldImage);
                            }         
                        }
                    return Helpers::successResponse("successfully Deleted");}
                    else 
                    return Helpers::errorResponse("error");    
             
            }
        
         catch(\Exception $e){
                  
                        return Helpers::errorResponse("error");
                      }         
        
        }

}
