<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PatientClinicModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;

class PatientClinicController extends Controller
{
        //add new data
        function addData(Request $request){

    
            $validator = Validator::make(request()->all(), [
                'clinic_id' => 'required',
                'patient_id' => 'required',

          ]);
          if ($validator->fails())
          return response (["response"=>400],400);
         
          try{
                $alreadyAddedModel= PatientClinicModel::where("patient_id",$request->patient_id)->where("clinic_id",$request->clinic_id)->first();
                if($alreadyAddedModel)
                {
                    return Helpers::errorResponse("Patient is Already Linked");
                
                }else{
                 
    
                    $timeStamp= date("Y-m-d H:i:s");
                    $dataModel=new PatientClinicModel;
                    
                    $dataModel->patient_id = $request->patient_id ;
                         
                    $dataModel->clinic_id = $request->clinic_id ;
                         
                    $dataModel->linking_code = $request->linking_code ;
                   
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
               
                  
                        return Helpers::errorResponse("error");
                      }
    }

            
            //Delete Data
        function deleteData(Request $request){

            $validator = Validator::make(request()->all(), [
                'id' => 'required'
          ]);
          if ($validator->fails())
          return response (["response"=>400],400);
            try{
        
                $dataModel= PatientClinicModel::where("id",$request->id)->first();

                        $qResponce= $dataModel->delete();
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

    // get data

    public function getData(Request $request)
    {
  
        // Calculate the limit
        $start = $request->start;
        $end = $request->end;
        $limit = ($end - $start);
  
        // Define the base query
  
  
        $query = DB::table("patient_clinic")
        ->select('patient_clinic.*',
        'patients.f_name',
        'patients.l_name',
        'clinics.title',
        )
        ->join('patients','patients.id','patient_clinic.patient_id')
        ->join('clinics','clinics.id','patient_clinic.clinic_id')
                ->orderBy('patient_clinic.created_at','DESC');
        
            if (!empty($request->start_date)) {
              $query->whereDate('patient_clinic.created_at', '>=', $request->start_date);
          }
          
          if (!empty($request->end_date)) {
              $query->whereDate('patient_clinic.created_at', '<=', $request->end_date);
          }

          if ($request->filled('patient_id')) {
            $query->where('patient_clinic.patient_id', '=', $request->patient_id);
        }

        if ($request->filled('clinic_id')) {
            $query->where('patient_clinic.clinic_id', '=', $request->clinic_id);
        }
    
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where(DB::raw('CONCAT(patients.f_name, " ", patients.l_name)'), 'like', '%' . $search . '%')
                ->where('patient_clinic.linking_code', 'like', '%' . $search . '%')
                ->orWhere('patient_clinic.id', 'like', '%' . $search . '%')  
                ->orWhere('clinics.title', 'like', '%' . $search . '%') ; 
            });
        }
  
        $total_record = $query->get()->count();
        if ($request->filled(['start', 'end'])) {
            $start = (int) $request->start;
            $end = (int) $request->end;
            $query->skip($start)->take($end - $start);
        }
    
        // Fetch paginated data
        $data = $query->get();
  
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

  
        $data = DB::table("patient_clinic")
        ->select('patient_clinic.*',
        'patients.f_name',
        'patients.l_name',
        'clinics.title' )
        ->join('patients','patients.id','patient_clinic.patient_id')
        ->join('clinics','clinics.id','patient_clinic.clinic_id')
        ->where('patient_clinic.id','=',$id)
        ->first();
      
            $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
        }
}
