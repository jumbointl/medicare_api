<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServiceChargesModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ServiceChargesController extends Controller
{
    //add new data
    function addData(Request $request){

    
        $validator = Validator::make(request()->all(), [
              'name' => 'required',
              'service_charge_type' => 'required',
              'service_charge_value' => 'required'
      ]);
      if ($validator->fails())
      return response (["response"=>400],400);
     
      try{
            //check of clinic id or lab id
            if(!isset($request->clinic_id)&&!isset($request->lab_id))
            {
                return Helpers::errorResponse("clinic id or lab id is required");
            }

            
               else if(isset($request->clinic_id)&&!isset($request->lab_id))
               {
                   $alreadyAddedModel= ServiceChargesModel::where("clinic_id",$request->clinic_id)->where("name",$request->name)->first();
                   if($alreadyAddedModel)
                   {
                       return Helpers::errorResponse("Name already exists");
                   }
               }
               else if(!isset($request->clinic_id)&&isset($request->lab_id))
               {
                   $alreadyAddedModel= ServiceChargesModel::where("lab_id",$request->lab_id)->where("name",$request->name)->first();
                   if($alreadyAddedModel)
                   {
                       return Helpers::errorResponse("Name already exists");
                   }
               }
            
       
                DB::beginTransaction();

                $timeStamp= date("Y-m-d H:i:s");
                $dataModel=new ServiceChargesModel;

                $dataModel->name = $request->name ;

                $dataModel->service_charge_type = $request->service_charge_type ;

                $dataModel->service_charge_value = $request->service_charge_value ;

                  $dataModel->clinic_id = $request->clinic_id ?? null ;
                $dataModel->lab_id = $request->lab_id ?? null ;

                $dataModel->created_at=$timeStamp;
                $dataModel->updated_at=$timeStamp;
                   $qResponce = $dataModel->save();

                if(isset($request->service_charge_tax)){
                     if($qResponce && is_array($request->service_charge_tax)) {
                        foreach($request->service_charge_tax as $serviceTax) {
                            if(isset($serviceTax['tax_id'])) {
                                // Insert service tax relationship
                                DB::table('service_charge_tax')->insert([
                                    'service_charge_id' => $dataModel->id,
                                    'tax_id' => $serviceTax['tax_id'],
                                    'created_at' => $timeStamp,
                                    'updated_at' => $timeStamp
                                ]);
                            }
                        }
                    }
                }
       
               
             
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
// Update Deapartment
function updateData(Request $request){
    
    $validator = Validator::make(request()->all(), [
        'id' => 'required'
  ]);
  if ($validator->fails())
  return response (["response"=>400],400);
    try{

            $dataModel= ServiceChargesModel::where("id",$request->id)->first();
           

        DB::beginTransaction();
    
        if(isset($request->name)){
        
                if(isset($dataModel->clinic_id)&&!isset($dataModel->lab_id))
               {
                   $alreadyAddedModel= ServiceChargesModel::where("clinic_id",$dataModel->clinic_id)->where("name",$dataModel->name)->where('id',"!=",$request->id)->first();
                   if($alreadyAddedModel)
                   {
                       return Helpers::errorResponse("Name already exists");
                   }
               }
               else if(!isset($dataModel->clinic_id)&&isset($dataModel->lab_id))
               {
                   $alreadyAddedModel= ServiceChargesModel::where("lab_id",$dataModel->lab_id)->where("name",$dataModel->name)->where('id',"!=",$request->id)->first();
                   if($alreadyAddedModel)
                   {
                       return Helpers::errorResponse("Name already exists");
                   }
               }
               $dataModel->name= $request->name;
        }
        if(isset($request->service_charge_type)){
            $dataModel->service_charge_type= $request->service_charge_type;
        }
        if(isset($request->service_charge_value)){
            $dataModel->service_charge_value= $request->service_charge_value;
        }

          if(isset($request->active)){
            $dataModel->active= $request->active;
        }



        $timeStamp= date("Y-m-d H:i:s");
        $dataModel->updated_at=$timeStamp;
                $qResponce= $dataModel->save();
                if($qResponce)
                {

                     if(isset($request->service_charge_tax)){
                        // First, delete existing relationships
                        DB::table('service_charge_tax')->where('service_charge_id', $dataModel->id)->delete();
                           if($qResponce && is_array($request->service_charge_tax)) {
                        foreach($request->service_charge_tax as $serviceTax) {
                            if(isset($serviceTax['tax_id'])) {
                                // Insert service tax relationship
                                DB::table('service_charge_tax')->insert([
                                    'service_charge_id' => $dataModel->id,
                                    'tax_id' => $serviceTax['tax_id'],
                                    'created_at' => $timeStamp,
                                    'updated_at' => $timeStamp
                                ]);
                            }
                        }
                    }
                }

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


      public function getData(Request $request)
    {
        // Define the base query
        $query = DB::table("service_charges")
            ->select(
                'service_charges.*',
                'clinics.title as clinic_title',
                'pathologist.title as lab_title'
     
            )
            ->Leftjoin('clinics', 'clinics.id', '=', 'service_charges.clinic_id')
             ->Leftjoin('pathologist', 'pathologist.id', '=', 'service_charges.lab_id')
            ->orderBy("service_charges.created_at", "DESC");

        // Apply filters (like search)
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('service_charges.name', 'like', '%' . $search . '%')
                    ->orWhere('service_charges.service_charge_type', 'like', '%' . $search . '%')
                    ->orWhere('service_charges.service_charge_value', 'LIKE', "%$search%")
                    ->orWhere('clinics.title', 'like', '%' . $search . '%')
                    ->orWhere('pathologist.title', 'like', '%' . $search . '%');
                 
            });
        }
                if($request->has('clinic_id')){
                    $query->where('service_charges.clinic_id','=',$request->clinic_id);
                }
                if($request->has('lab_id')){
                    $query->where('service_charges.lab_id','=',$request->lab_id);
                }

        // Calculate total records first (without pagination)
        $total_record = $query->count(); // Count the total records before pagination

        // Apply pagination if start and end are provided
        if ($request->filled(['start', 'end'])) {
            $start = $request->start;
            $end = $request->end;
            $query->skip($start)->take($end - $start);
        }
        // Get the paginated data
        $data = $query->get();

        if($data->isNotEmpty()){
            foreach($data as $item){
                $service_taxes = DB::table('service_charge_tax')
                    ->join('taxes', 'taxes.id', '=', 'service_charge_tax.tax_id')
                    ->where('service_charge_tax.service_charge_id', $item->id)
                    ->select('taxes.*',)
                    ->get();
                $item->service_taxes = $service_taxes;
            }
        }

        // Return the response with total records and data
        $response = [
            "response" => 200,
            "total_record" => $total_record,
            'data' => $data,
        ];

        return response()->json($response, 200);
    }


        // get data by id
    function getDataById($id)
    {

      $data = DB::table("service_charges")
      ->select(   
          'service_charges.*',
                'clinics.title as clinic_title',
                'pathologist.title as lab_title'
      
         
      )
      ->Leftjoin('clinics', 'clinics.id', '=', 'service_charges.clinic_id')
             ->Leftjoin('pathologist', 'pathologist.id', '=', 'service_charges.lab_id')
      ->where('service_charges.id','=',$id)
        ->first();

        if($data){
            $data->service_taxes = DB::table('service_charge_tax')
                ->join('taxes', 'taxes.id', '=', 'service_charge_tax.tax_id')
                ->where('service_charge_tax.service_charge_id', $data->id)
                ->select('taxes.*')
                ->get();
        }
      
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
                    $dataModel= ServiceChargesModel::where("id",$request->id)->first();
                    $qResponce= $dataModel->delete();
                    if($qResponce){
                     
                   
                    return Helpers::successResponse("successfully Deleted");}
                    else 
                    return Helpers::errorResponse("error");    
             
            }
        
         catch(\Exception $e){
                  
            return Helpers::errorResponse("This record cannot be deleted because it is linked to multiple data entries in the system. You can only deactivate it to prevent future use.");
                      }         
        
        }

}
