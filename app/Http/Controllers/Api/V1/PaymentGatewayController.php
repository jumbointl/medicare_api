<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentGatewayModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;

class PaymentGatewayController extends Controller
{

    // Update data
function updateData(Request $request){


    $validator = Validator::make(request()->all(), [
        'id' => 'required'
  ]);
  if ($validator->fails())
  return response (["response"=>400],400);
    try{

        $dataModel= PaymentGatewayModel::where("id",$request->id)->first();

         if(isset($request->key))  {  $dataModel->key= $request->key;} 
         if(isset($request->secret))  { $dataModel->secret= $request->secret;} 
         if(isset($request->webhook_secret_key)) { $dataModel->webhook_secret_key= $request->webhook_secret_key;}     
        $timeStamp= date("Y-m-d H:i:s");
        $dataModel->updated_at=$timeStamp;
        $qResponce= $dataModel->save();
                if($qResponce)
                {  
                    if(isset($request->is_active)){  
                        if($request->is_active==1){
                            PaymentGatewayModel::where('is_active', 1)->update(['is_active' => 0]);
                        }
                        $dataModel= PaymentGatewayModel::where("id",$request->id)->first();
                        $dataModel->is_active= $request->is_active; 
                        $dataModel->save();
                    }
            
                return Helpers::successResponse("successfully");
                }
                else 
                {
                    return Helpers::errorResponse("error");
                }
    }
    

 catch(\Exception $e){
                return Helpers::errorResponse("error");
              }
            }

            
            //Delete Data
   

    function getData()
    {

      $data = DB::table("payment_gateway")
      ->select('payment_gateway.*'
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

      $data = DB::table("payment_gateway")
      ->select('payment_gateway.*')
      ->where('payment_gateway.id','=',$id)
        ->first();
      
            $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
        }

        function getDataByActive()
        {
    
          $data = DB::table("payment_gateway")
          ->select('payment_gateway.*')
          ->where('payment_gateway.is_active','=',true)
            ->first();
          
                $response = [
                    "response"=>200,
                    'data'=>$data,
                ];
            
          return response($response, 200);
     }
        

}
