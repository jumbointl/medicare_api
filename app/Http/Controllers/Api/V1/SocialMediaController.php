<?php

namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SocialMediaModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;

class SocialMediaController extends Controller
{

    function getDataAllData()
    {


      $data = DB::table("social_media")
      ->select('social_media.*')
      ->orderBy("social_media.created_at","ASC")
        ->get();
      
            $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
    }
    function addData(Request $request)
    {
        
        $validator = Validator::make(request()->all(), [
            'title' => 'required',
            'url' => 'required'
    ]);
        
    if ($validator->fails())
      return response (["response"=>400],400);
        else{
        
               $alreadyExists = SocialMediaModel::where('title', '=', $request->title)->first();
                if ($alreadyExists === null) {
                  try{
                    $timeStamp= date("Y-m-d H:i:s");
                    $dataModel=new SocialMediaModel;
                    $dataModel->title = $request->title;
                    $dataModel->url = $request->url;
                    $dataModel->created_at=$timeStamp;
                    $dataModel->updated_at=$timeStamp;

                    if(isset($request->image)){
        
                      $dataModel->image =  $request->hasFile('image') ? Helpers::uploadImage('socialmedia/', $request->file('image')) : null;
                }
                
                    $qResponce= $dataModel->save();
                       if($qResponce){
                   return Helpers::successResponse("successfully");
                        }
                        else {      return response($response, 200);}
                  
                               
                  }catch(\Exception $e){
              
                    return response($response, 200);
                  }
                }
                
                else {
                  return Helpers::errorResponse("title already exists");

                }
      
       }
       
      }
      function deleteData(Request $request){
    
        $validator = Validator::make(request()->all(), [
            'id' => 'required'
      ]);
      if ($validator->fails())
      return response (["response"=>400],400);
        try{
            DB::beginTransaction();
            $dataModel= SocialMediaModel::where("id",$request->id)->first();
          
            if($dataModel==null){
              DB::rollBack();
              return Helpers::errorResponse("error");
      
            }
            $oldImage=$dataModel->image;
            if(isset($oldImage)){
             Helpers::deleteImage($oldImage);
            }
               $qResponce= $dataModel->delete();
                 
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
        }
        
    
     catch(\Exception $e){
        DB::rollBack();
                    return Helpers::errorResponse("error");
                  }
                }
                
                function updateData(Request $request){
    
                  $validator = Validator::make(request()->all(), [
                      'id' => 'required'
                ]);
                if ($validator->fails())
                return response (["response"=>400],400);
                  try{
                      DB::beginTransaction();
                      $dataModel= SocialMediaModel::where("id",$request->id)->first();
                      if(isset($request->title)){
                          $alreadyExists = SocialMediaModel::where('title', '=', $request->title)->where('id',"!=",$request->id)->first();
                          if ($alreadyExists != null)
                          {
                              return Helpers::errorResponse("title already exists");
                          }
                          else{
                              $dataModel->title= $request->title;
                          }
                      }
                  
                    
                      if(isset($request->image)){
                          if($request->hasFile('image') ){
              
                          $oldImage = $dataModel->image;
                          $dataModel->image =  Helpers::uploadImage('socialmedia/', $request->file('image'));
                          if(isset($oldImage)){
                              if($oldImage!="def.png"){
                                  Helpers::deleteImage($oldImage);
                              }
                          }
                      }
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

                          function removeImage(Request $request){


                            $validator = Validator::make(request()->all(), [
                                'id' => 'required'
                          ]);
                          if ($validator->fails())
                          return response (["response"=>400],400);
                            try{
                                $dataModel= SocialMediaModel::where("id",$request->id)->first();
                          
                        
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
 
}