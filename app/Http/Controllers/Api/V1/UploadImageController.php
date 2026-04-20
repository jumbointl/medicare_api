<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UploadImageController extends Controller
{
    //add new data
    function uploadBlogImage(Request $request){

    
        $validator = Validator::make(request()->all(), [
            'image' => 'required'
      ]);
      if ($validator->fails())
      return response (["response"=>400],400);
     
      try{
        
                 $image =  $request->hasFile('image') ? Helpers::uploadImage('blog_content/', $request->file('image')) : null;
                
                
            
                if($image )
               {
                DB::commit();
                
                return Helpers::successWithIdResponse("successfully",$image);}
            
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
        'image' => 'required'
  ]);
  if ($validator->fails())
  return response (["response"=>400],400);
    try{
       
  
                Helpers::deleteImage($request->image);
            
           return Helpers::successResponse("successfully");
    
    
    }
    

 catch(\Exception $e){
          
                return Helpers::errorResponse("error");
              }
            }

}
