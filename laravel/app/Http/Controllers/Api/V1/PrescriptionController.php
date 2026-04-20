<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PrescriptionItemModel;
use App\Models\PrescriptionModel;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Http\Request;
use PDF;
class PrescriptionController extends Controller
{

  public function generatePDF($id)
    {
        $prescription  = DB::table("prescription")
        ->select('prescription.*')
         ->where("prescription.id","=",$id)
          ->first();

          $prescriptionItems  = DB::table("prescription_item")
          ->select('prescription_item.*')
           ->where("prescription_item.prescription_id","=",$id)
            ->get();
          $prescription->items = $prescriptionItems;

        if (!$prescription) {
            return response()->json(['error' => 'Prescription not found'], 404);
        }
        
    //     $response = [
    //       "response"=>200,
    //       'data'=>$prescription,
    //   ];
  
    //  return response($response, 200);
    

        $pdf = PDF::loadView('prescriptions.pdf', ['prescription' => $prescription]);
        return $pdf->download('prescription.pdf');
    }
    function getDataById($id)
    {
        // Fetch data from the database
        $prescription = DB::table('prescription')
            ->select('prescription.*', 'patients.f_name as patient_f_name', 'patients.l_name as patient_l_name')
            ->where('prescription.id', '=', $id)
            ->leftJoin('patients', 'patients.id', '=', 'prescription.patient_id')
            ->orderBy('prescription.created_at', 'DESC')
            ->first();
    
        // Add prescription items to the prescription
        if ($prescription) {
            $prescription->items = DB::table('prescription_item')
                ->where('prescription_id', '=', $prescription->id)
                ->get();
        }
    
        // Prepare the response
        $response = [
            'response' => 200,
            'data' => $prescription,
        ];
    
        // Return the response
        return response()->json($response, 200);
    }

    function getDataByAppointmentId($id)
    {
        // Fetch data from the database
        $prescriptions = DB::table('prescription')
        ->select('prescription.*', 'patients.f_name as patient_f_name', 'patients.l_name as patient_l_name')
            ->where('prescription.appointment_id', '=', $id)
            ->leftJoin('patients', 'patients.id', '=', 'prescription.patient_id')
            ->orderBy('prescription.created_at', 'DESC')
            ->get();
    
        // Add prescription items to each prescription
        foreach ($prescriptions as $prescription) {
            $prescription->items = DB::table('prescription_item')
                ->where('prescription_id', '=', $prescription->id)
                ->get();
        }
    
        // Prepare the response
        $response = [
            'response' => 200,
            'data' => $prescriptions,
        ];
    
        // Return the response
        return response()->json($response, 200);
    }

    function getData()
    {

      $data = DB::table("prescribe_medicines")
      ->select('prescribe_medicines.*')
       ->orderBy('prescribe_medicines.created_at','DESC')
        ->get();
      
            $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
    }
    function addData(Request $request)
    {
        
        $validator = Validator::make(request()->all(), [
            'appointment_id' => 'required',
            'patient_id' => 'required',   
            'medicines' => 'required|array',
            'medicines.*.medicine_name' => 'required|string',
            'medicines.*.days' => 'required|string',
            'medicines.*.time' => 'required|string',
    ]);
  
    if ($validator->fails())
      return response (["response"=>400],400);
        else{
        
                  try{
                    DB::beginTransaction();
            
                    $timeStamp= date("Y-m-d H:i:s");
                    $dataModel=new PrescriptionModel;
                    
                    $dataModel->appointment_id = $request->appointment_id ;
                        
                    $dataModel->patient_id = $request->patient_id ;
                    $dataModel->date = $timeStamp ;
                    
                    $dataModel->created_at=$timeStamp;
                    $dataModel->updated_at=$timeStamp;
                    $qResponce= $dataModel->save();
                    if(!$qResponce){
                        DB::rollBack();
                        return Helpers::errorResponse("error");
    
                            }
                            foreach ($request->medicines as $medicine) {
                                $dataModelItem = new PrescriptionItemModel;
                                $dataModelItem->prescription_id = $dataModel->id;
                                $dataModelItem->medicine_name = $medicine['medicine_name'];
                                $dataModelItem->days = $medicine['days'];
                                $dataModelItem->time = $medicine['time'];
                                $dataModelItem->created_at = $timeStamp;
                                $dataModelItem->updated_at = $timeStamp;
            
                                if (isset($medicine['notes'])) {
                                    $dataModelItem->notes = $medicine['notes'];
                                }
            
                                $qResponce = $dataModelItem->save();
            
                                if (!$qResponce) {
                                    DB::rollBack();
                                    return Helpers::errorResponse("error");
                                }
                            }
                            DB::commit();
                            return Helpers::successWithIdResponse("successfully", $dataModel->id);
                               
                  }catch(\Exception $e){
                    DB::rollBack();
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
                if(isset($request->notes)){
                    $dataModel->notes  = $request->notes;
                  }
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
