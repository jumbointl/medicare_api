<?php

namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TimeSlotsModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
class TimeSlotsController extends Controller
{

      //delete data
      function deleteData(Request $request){

    
        $validator = Validator::make(request()->all(), [
            'id' => 'required',
      ]);
      if ($validator->fails())
      return response (["response"=>400],400);
     
      try{               
                $dataModel= TimeSlotsModel::where("id",$request->id)->first();
                                              
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

    //add new data
    function addData(Request $request){

    
        $validator = Validator::make(request()->all(), [
            'doct_id' => 'required',
            'time_duration' => 'required',
            'day' => ['required', Rule::in(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])],
            'time_start' => 'required|date_format:H:i',
            'time_end' => 'required|date_format:H:i|after:time_start'
      ]);
      if ($validator->fails())
      return response (["response"=>400],400);
     
      try{
        // check time dif
           $time1 = strtotime($request->time_start);
            $time2 = strtotime($request->time_end);

            // Calculate the difference in minutes
            $diffInMinutes = ($time2 - $time1) / 60;

            if( $diffInMinutes<$request->time_duration ){
                return Helpers::errorResponse("please choose the correct time duration");
            }
          
    
     
        //check time between
        $alreadyAddedModel = TimeSlotsModel::where('day', $request->day)
        ->where('doct_id', $request->doct_id)
        
        ->where(function($query) use ($request) {
            $query->where('time_start', '<=', $request->time_end)
                ->where('time_end', '>=', $request->time_start);
        })->first();
            if($alreadyAddedModel)
            {
                return Helpers::errorResponse("time already exists");
            
            }
            else{
           
                $timeStamp= date("Y-m-d H:i:s");
                $dataModel=new TimeSlotsModel;
                
                $dataModel->doct_id = $request->doct_id;
                $dataModel->time_start = $request->time_start;
                $dataModel->time_end = $request->time_end;
                $dataModel->time_duration = $request->time_duration;
                $dataModel->day = $request->day;
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
             DB::rollBack();
              
                    return Helpers::errorResponse("error");
                  }
}

function getDataByDoctId($id)
{

  $data = DB::table("time_slots")
  ->select('time_slots.*'
  )->where("time_slots.doct_id","=",$id)
  ->orderBy('time_start', 'ASC')
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

  $data = DB::table("time_slots")
  ->select('time_slots.*')
  ->where('id','=',$id)
    ->first();
  
        $response = [
            "response"=>200,
            'data'=>$data,
        ];
    
  return response($response, 200);
    }
    function getDataDoctotToimeInterval($doct_id,$day)
    {
    
      $data = DB::table("time_slots")
      ->select('time_slots.*')
      ->where('doct_id','=',$doct_id)
      ->where('day','=',$day)
      ->orderBy('time_start', 'ASC')
        ->get();
        $slots = [];
        if($data){
            if(count($data)>0){
                foreach ($data as $time_slots) {
                    
                    $start_time = strtotime($time_slots->time_start); // Replace with your start time
                    $end_time = strtotime($time_slots->time_end);   // Replace with your end time
                    $time_duration = $time_slots->time_duration;; // Duration in minutes
                    
               
                    $current_time = $start_time;
                    
                    while ($current_time <= $end_time) {
                        $slot_start = date('H:i', $current_time);
                        $current_time += $time_duration * 60; // Add duration in seconds
                        $slot_end = date('H:i', $current_time);
                        
                        if ($current_time <= $end_time) {
                            $slots[] = ["time_start" => $slot_start, "time_end" => $slot_end];
                        }
                    }
                }
                
        }
    }
      
            $response = [
                "response"=>200,
                'data'=>$slots,
            ];
        
      return response($response, 200);



// Display all slots
foreach ($slots as $slot) {
    echo "Slot: {$slot['start']} - {$slot['end']}\n";
}
        }

}