<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LoginScreenImageModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;



class LoginScreenController extends Controller
{
    // add new users
    function addData(Request $request){
        
        $validator = Validator::make(request()->all(), [
          'image' => 'required',
          
    ]);
        
    if ($validator->fails())
      return response (["response"=>400],400);
        else{
          
                  try{
                    DB::beginTransaction();
                    $timeStamp= date("Y-m-d H:i:s");
                    $dataModel=new LoginScreenImageModel;
                  
                       $dataModel->image =  $request->hasFile('image') ? Helpers::uploadImage('loginscreen/', $request->file('image')) : null;
                  

                    if(isset($request->Preferences)){
                        $dataModel->Preferences= $request->Preferences;
                    }
                                           
                    $dataModel->created_at=$timeStamp;
                    $dataModel->updated_at=$timeStamp;
    
                    $qResponce= $dataModel->save();

                
                    if(!$qResponce)
                   { DB::rollBack();
                    return Helpers::errorResponse("error"); }
                
                    DB::commit();
                    return Helpers::successWithIdResponse("successfully",$dataModel->id);
              
        
                               
                  }catch(\Exception $e){
                    DB::rollBack();
                    return Helpers::errorResponse("error $e");
                  }
                
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
    
            //user data
            $dataModel= LoginScreenImageModel::where("id",$request->id)->first();
            if(isset($request->preferences)){
                $dataModel->preferences= $request->preferences;
            }
    
          
            if(isset($request->image)){
                if($request->hasFile('image') ){
                $oldImage = $dataModel->image;
                $dataModel->image =  Helpers::uploadImage('loginscreen/', $request->file('image'));
                if(isset($oldImage)){
                    if($oldImage!="def.png"){
                        Helpers::deleteImage($oldImage);
                    }
                }
            }
            }
            $res=$dataModel->save();
            if($res){
                DB::commit();
                return Helpers::successResponse("successfully");
                
            }else{
                DB::rollBack();
                    return Helpers::errorResponse("error");
            }
            
        }
        
     catch(\Exception $e){
        DB::rollBack();
                    return Helpers::errorResponse("error");
                  }
                }

            function getData(Request $request)
            {
            
            
                $data = DB::table("login_screen_image")
                    ->select('login_screen_image.*')
                    ->orderBy('login_screen_image.Preferences','ASC')
                    ->get();
            
                $response = [
                    "response" => 200,
                    'data' => $data
                ];
            
                return response($response, 200);
            }
        
                   // get data by id
        
            function getDataById($id)
            {
        
                $data = DB::table("login_screen_image")
                ->select('login_screen_image.*')
                ->where('login_screen_image.id',$id)
                ->first();
        
            $response = [
                "response" => 200,
                'data' => $data,
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
                        DB::beginTransaction();
                
                        //user data
                        $dataModel= LoginScreenImageModel::where("id",$request->id)->first();
                         
                            $oldImage = $dataModel->image;
                            if(isset($oldImage)){
                                if($oldImage!="def.png"){
                                    Helpers::deleteImage($oldImage);
                                }
                            }
                        
                        
                        $res=$dataModel->delete();
                        if($res){
                            DB::commit();
                            return Helpers::successResponse("successfully");
                            
                        }else{
                            DB::rollBack();
                                return Helpers::errorResponse("error");
                        }
                        
                    }
            
             catch(\Exception $e){
                DB::rollBack();
                            return Helpers::errorResponse("error");
                          }
                        }
}

