<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StatesModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StatesController extends Controller
{
    //add new data
    function addData(Request $request){

    
        $validator = Validator::make(request()->all(), [
            'title' => 'required',
             'country_id' => 'required'
      ]);
      if ($validator->fails())
      return response (["response"=>400],400);
     
      try{
            $alreadyAddedModel= StatesModel::where("title",$request->title)->first();
            if($alreadyAddedModel)
            {
                return Helpers::errorResponse("title already exists");
            
            }else{
                DB::beginTransaction();

                $timeStamp= date("Y-m-d H:i:s");
                $dataModel=new StatesModel;
                
                $dataModel->title = $request->title ;
                $dataModel->country_id  = $request->country_id;
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
        $dataModel= StatesModel::where("id",$request->id)->first();
        if(isset($request->title)){
            $alreadyExists = StatesModel::where('title', '=', $request->title)->where('id',"!=",$request->id)->first();
            if ($alreadyExists != null)
            {
                return Helpers::errorResponse("title already exists");
            }
            else{
                $dataModel->title= $request->title;
            }
        }
        if(isset($request->country_id)){
            $dataModel->country_id= $request->country_id;
        }
        if(isset($request->active)){
            $dataModel->active= $request->active;
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

      $data = DB::table("states")
      ->select('states.*',
      'country.title as country_title'
      )
      ->join('country', 'country.id', '=', 'states.country_id')
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
    
          $data = DB::table("states")
          ->select('states.*')
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

        $data = DB::table("states")
        ->select('states.*',
        'country.title as country_title'
        )
        ->join('country', 'country.id', '=', 'states.country_id')
      ->where('states.id','=',$id)
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
                    $dataModel= StatesModel::where("id",$request->id)->first();
               
                    $qResponce= $dataModel->delete();
                    if($qResponce){
                     

                    return Helpers::successResponse("successfully Deleted");}
                    else 
                    return Helpers::errorResponse("error");    
             
            }
        
         catch(\Exception $e){
                  
            return Helpers::errorResponse("Cannot be deleted. You can only deactivate.");
                      }         
        
        }

}
