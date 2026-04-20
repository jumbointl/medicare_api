<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContactFormInboxModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ContactFormInboxController extends Controller
{
    //add new data
    function addData(Request $request){

    
        $validator = Validator::make(request()->all(), [
            'name' => 'required',
             'email' => 'required',
              'subject' => 'required',
               'message' => 'required'
            

      ]);
      if ($validator->fails())
      return response (["response"=>400],400);
     
      try{ 
                DB::beginTransaction();

                $timeStamp= date("Y-m-d H:i:s");
                $dataModel=new ContactFormInboxModel;
                
                $dataModel->email = $request->name ;
                $dataModel->name = $request->email ;
                $dataModel->subject = $request->subject ;
                $dataModel->message = $request->message ;
               
                $dataModel->created_at=$timeStamp;
                $dataModel->updated_at=$timeStamp;
            
    
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

    function getData()
    {

      $data = DB::table("contact_form_inbox")
      ->select('contact_form_inbox.*'
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

      $data = DB::table("contact_form_inbox")
      ->select('contact_form_inbox.*')
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
                    $dataModel= ContactFormInboxModel::where("id",$request->id)->first();
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
                  
                        return Helpers::errorResponse("error $e");
                      }         
        
        }

}
