<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PreOrderModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PreOrderController extends Controller
{
    //add new data
    function addData(Request $request){

    
        $validator = Validator::make(request()->all(), [
            'payload' => 'required',
            'type' => 'required',
            'pay_amount' => 'required',
      ]);
      if ($validator->fails())
      return response (["response"=>400],400);
     
      try{
           
                DB::beginTransaction();

                $timeStamp= date("Y-m-d H:i:s");
                $dataModel=new PreOrderModel;
                //cover to string 
                $dataModel->payload = json_encode($request->payload);

                $dataModel->type = $request->type ;
                $dataModel->user_id = $request->user_id ;
                $dataModel->pay_amount = $request->pay_amount ;

                $dataModel->created_at=$timeStamp;
                $dataModel->updated_at=$timeStamp;
            
          
               
                $qResponce = $dataModel->save();
                if($qResponce)
               {
                DB::commit();

                return Helpers::successWithIdResponse("successfully",$dataModel->id);
               }

                else
                {   DB::rollBack();
                    
                    return Helpers::errorResponse("error");}
            
           
        }

     catch(\Exception $e){
             DB::rollBack();
              
                    return Helpers::errorResponse("error $e");
                  }
}


}
