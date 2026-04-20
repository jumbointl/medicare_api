<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use App\Models\PrescribeMedicinesModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Http\Request;

class PrescribeMedicinesController extends Controller
{
       //delete data
       function deleteData(Request $request){

    
        $validator = Validator::make(request()->all(), [
            'id' => 'required',
      ]);
      if ($validator->fails())
      return response (["response"=>400],400);
     
      try{               
                $dataModel= PrescribeMedicinesModel::where("id",$request->id)->first();
                                              
                $qResponce= $dataModel->delete();
                if($qResponce)
               {
             
                return Helpers::successWithIdResponse("successfully",$dataModel->id);}
            
                else 
                {  
                    
                    return Helpers::errorResponse("error");}
            
           
        }

     catch(\Exception $e){
             DB::rollBack();
              
                    return Helpers::errorResponse("error");
                  }
}
    function getDataById($id)
    {

      $data = DB::table("prescribe_medicines")
      ->select('prescribe_medicines.*')
       ->where("prescribe_medicines.id","=",$id)
        ->first();
      
            $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
    }

    public function getData(Request $request)
    {
  
  
        // Define the base query
  
        $query = DB::table("prescribe_medicines")
        ->select('prescribe_medicines.*')
         ->orderBy('prescribe_medicines.created_at','DESC');
  
        
            if (!empty($request->start_date)) {
              $query->whereDate('prescribe_medicines.created_at', '>=', $request->start_date);
          }
          
          if (!empty($request->end_date)) {
              $query->whereDate('prescribe_medicines.created_at', '<=', $request->end_date);
          }
    
          if (!empty($request->clinic_id)) {
            $query->where('prescribe_medicines.clinic_id', $request->clinic_id);
        }

        if ($request->has('search')) {
          $search = $request->input('search');
          $query->where(function ($q) use ($search) {
              $q->where('prescribe_medicines.clinic_id', 'like', '%' . $search . '%')
              ->orWhere('prescribe_medicines.title', 'like', '%' . $search . '%')
              ->orWhere('prescribe_medicines.notes', 'like', '%' . $search . '%');
          

    
          });
      }
        $total_record = $query->get()->count();
        if ($request->filled(['start', 'end'])) {
          $start = (int) $request->start;
          $end = (int) $request->end;
          $query->skip($start)->take($end - $start);
      }
        $data = $query->get();
   
        $response = [
          "response" => 200,
          "total_record" => $total_record,
          'data' => $data,
      ];
      
      

      return response()->json($response, 200);
  
      }

    function addData(Request $request)
    {
        
        $validator = Validator::make(request()->all(), [
            'title' => 'required',
            'clinic_id' => 'required',

            
    ]);
  
    if ($validator->fails())
      return response (["response"=>400],400);
        else{
        
                  try{
                    $prescribeMedicinesModel = PrescribeMedicinesModel::
                    where("title", $request->title)
                    ->where("clinic_id", $request->clinic_id)
                    ->first();
                    if($prescribeMedicinesModel!=null){
                        return Helpers::errorResponse("Title is already exists");
                    }
                    $timeStamp= date("Y-m-d H:i:s");
                    $dataModel=new PrescribeMedicinesModel;
                    
                    $dataModel->title = $request->title ;
                    $dataModel->clinic_id = $request->clinic_id ;  
                    if(isset($request->notes)){
                      $dataModel->notes  = $request->notes;
                    }
            
                  
                 
                    $dataModel->created_at=$timeStamp;
                    $dataModel->updated_at=$timeStamp;
                    $qResponce= $dataModel->save();
                    if($qResponce){
          
                        return Helpers::successWithIdResponse("successfully", $dataModel->id);
                            }else 
                    {
                      return Helpers::errorResponse("error");
                    }
                               
                  }catch(\Exception $e){
              
                    return Helpers::errorResponse("error");
                  }
                
            
      
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

        $dataModel= PrescribeMedicinesModel::where("id",$request->id)->first();
            $alreadyExists = PrescribeMedicinesModel::where('title', '=', $request->title)->where('id',"!=",$request->id)->first();
            if ($alreadyExists != null)
            {
                return Helpers::errorResponse("Title already exists");
            }
            else{
              $dataModel->notes  = $request->notes??null;
                  if(isset($request->title)){
                    $dataModel->title  = $request->title;
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
}
