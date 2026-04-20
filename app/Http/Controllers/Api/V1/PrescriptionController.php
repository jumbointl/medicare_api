<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PrescriptionItemModel;
use App\Models\PrescriptionModel;
use App\Models\prescribedTestModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Http\Request;
use PDF;
use App\Http\Controllers\Api\V1\NotificationCentralController;

class PrescriptionController extends Controller
{

    public function uploadPdfAndJson(Request $request)
    {
      
        // Validate request
        $validator = Validator::make($request->all(), [
            'pdf_file' => 'required', // Max 20MB
            // 'json_data' => 'required',  // ✅ Ensure JSON is required
            'appointment_id' => 'required',
            'patient_id' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
    
        try {
            if ($request->hasFile('pdf_file')) { 
                $appData = DB::table("appointments")
                ->select('appointments.*')
                ->where('appointments.id',$request->appointment_id)
                ->first();  // ✅ Use `hasFile()` instead of `isset()`
                $timeStamp = now(); // Laravel helper for timestamp
                $dataModel = new PrescriptionModel;
                $dataModel->pdf_file = Helpers::uploadImage('prescription_file/', $request->file('pdf_file'));
             //   $dataModel->json_data = $request->json_data;  // ✅ Ensure JSON is stored
    
                $dataModel->appointment_id = $request->appointment_id;
                $dataModel->patient_id = $request->patient_id;
                $dataModel->date = $timeStamp;
                $dataModel->clinic_id = $appData->clinic_id;
                $dataModel->created_at = $timeStamp;
                $dataModel->updated_at = $timeStamp;
                
                if ($dataModel->save()) {
                    $notificationCentralController = new NotificationCentralController();
    
                    $notificationCentralController->sendPrescrptionNotificationToUsers($request->appointment_id, $dataModel->id, "Add");
    
                    return response()->json(['response' => 200,
                    "status"=> true,
                    "id"=>  $dataModel->id,
                    "file"=>   $dataModel->pdf_file,
                    'message' => "Successfully uploaded"], 200);
                  //  return Helpers::successWithIdResponse("Successfully uploaded", $dataModel->id);
                }
            }
    
            return Helpers::errorResponse("File upload failed");
    
        } catch (\Exception $e) {
            return Helpers::errorResponse("Error: " . $e->getMessage());
        }
    }


    public function generatePDF($id)
    {
        $prescription = DB::table("prescription")
            ->select(
                'prescription.*',
                'patients.mrn',
                'patients.f_name as patient_f_name',
                'patients.l_name as patient_l_name',
                'patients.phone as patient_phone',
                'patients.dob as patient_dob',
                'patients.gender as patient_gender',
            )
            ->LeftJoin('patients', 'patients.id', '=', 'prescription.patient_id')
            ->where("prescription.id", "=", $id)
            ->first();

        if (!$prescription) {
            return response()->json(['error' => 'Prescription not found'], 404);
        }

        $prescriptionItems = DB::table("prescription_item")
            ->select('prescription_item.*')
            ->where("prescription_item.prescription_id", "=", $id)
            ->get();
        $prescriptionTest = DB::table("prescribe_test")
            ->select('prescribe_test.*')
            ->where("prescribe_test.prescription_id", "=", $id)
            ->get();
        $prescription->items = $prescriptionItems;
         $prescription->prescribe_test = $prescriptionTest;

        $clinicsDetails=DB::table('clinics')
        ->where('id', '=', $prescription->clinic_id)
        ->first();

        $prescription->clinic_name = $clinicsDetails->title??"--";

        $prescription->logo = $clinicsDetails->image??"--";

        $prescription->phone = $clinicsDetails->phone??"--";

        $prescription->phone_second =$clinicsDetails->phone_second??"--";

        $prescription->email =$clinicsDetails->email??"--";

        $prescription->address = $clinicsDetails->address??"--";

        $pdf = PDF::loadView('prescriptions.pdf', ['prescription' => $prescription]);
        return $pdf->stream('prescription.pdf', ['Attachment' => false]);
    }
    public function generate_blank_prescriptionsPDF($id)
    {
        $prescription = DB::table("appointments")
            ->select(
                'appointments.*',
                'patients.f_name as patient_f_name',
                'patients.l_name as patient_l_name',
                'patients.phone as patient_phone',
                'patients.dob as patient_dob',
                'patients.gender as patient_gender',
                'department.title as dept_title',
                'users.f_name as doct_f_name',
                'users.l_name as doct_l_name',
                "users.image as doct_image",
                "doctors.specialization as doct_specialization"
            )
            ->LeftJoin('patients', 'patients.id', '=', 'appointments.patient_id')
            ->Join('department', 'department.id', '=', 'appointments.dept_id')
            ->Join('users', 'users.id', '=', 'appointments.doct_id')
            ->LeftJoin('doctors', 'doctors.user_id', '=', 'appointments.doct_id')
            ->where("appointments.id", "=", $id)
            ->first();

        if (!$prescription) {
            return response()->json(['error' => 'Prescription not found'], 404);
        }
        $clinicsDetails=DB::table('clinics')
        ->where('id', '=', $prescription->clinic_id)
        ->first();

        $prescription->clinic_name = $clinicsDetails->title??"--";

        $prescription->logo = $clinicsDetails->image??"--";

        $prescription->phone = $clinicsDetails->phone??"--";

        $prescription->phone_second =$clinicsDetails->phone_second??"--";

        $prescription->email =$clinicsDetails->email??"--";

        $prescription->address = $clinicsDetails->address??"--";

        $pdf = PDF::loadView('blank_prescriptions.pdf', ['prescription' => $prescription]);
        return $pdf->stream('prescription.pdf', ['Attachment' => false]);
    }

    public function getData(Request $request)
    {
        // Define the base query
        $query = DB::table("prescription")
            ->select(
                'prescription.*',  
                'patients.f_name as patient_f_name',  
                'patients.l_name as patient_l_name'  ,
                   'users.f_name as doctor_f_name',
                'users.l_name as doctor_l_name'
            )
            ->leftJoin('patients', 'prescription.patient_id', '=', 'patients.id')
            ->join('appointments','appointments.id','=','prescription.appointment_id')
            ->join('users', 'users.id', '=', 'appointments.doct_id')
            ->orderBy('prescription.created_at', 'DESC');
    
        // Apply filters
        if ($request->filled('start_date')) {
            $query->whereDate('prescription.date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->whereDate('prescription.date', '<=', $request->end_date);
        }
    
        if ($request->filled('clinic_id')) {
            $query->where('prescription.clinic_id', $request->clinic_id);
        }

        if ($request->filled('appointment_id')) {
            $query->where('prescription.appointment_id', $request->appointment_id );
        }
        if ($request->filled('doctor_id')) {
            $query->where('appointments.doct_id', $request->doctor_id );
        }
        if ($request->filled('user_id')) {
            $query->where('patients.user_id', $request->user_id);
        }
        if ($request->filled('patient_id')) {
            $query->where('prescription.patient_id', '=', $request->patient_id);
        }

      
        
     
    
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('prescription.appointment_id', 'like', "%$search%")
                    ->orWhere(DB::raw('CONCAT(patients.f_name, " ", patients.l_name)'), 'like', "%$search%")
                    ->orWhere('prescription.patient_id', 'like', "%$search%");
            });
        }
    
        // Get the total number of matching records (before pagination)
        $total_record = $query->count();
    
        // Apply pagination if start and end are provided
        if ($request->filled(['start', 'end'])) {
            $start = (int) $request->start;
            $end = (int) $request->end;
            $query->skip($start)->take($end - $start);
        }
    
        // Fetch paginated data
        $data = $query->get();

        if($request->filled('show_prescription_item')){
            foreach ($data as $prescription) {
                $prescription->items = DB::table('prescription_item')
                    ->where('prescription_id', '=', $prescription->id)
                    ->get();
            }
        }
    
        // Prepare and return the response
        return response()->json([
            "response" => 200,
            "total_record" => $total_record,
            'data' => $data,
        ], 200);
    }
    
  
    function getDataById($id)
    {

        $data = DB::table("prescription")
            ->select('prescription.*')
            ->where('id', '=', $id)
            ->first();
        if ($data != null) {
            $data->items = DB::table('prescription_item')
                ->where('prescription_id', '=', $data->id)
                ->orderBy('prescription_item.created_at', 'DESC')
                ->get();

                     $data->prescribe_test = DB::table('prescribe_test')
                ->where('prescribe_test.prescription_id', '=', $data->id)
                ->orderBy('prescribe_test.created_at', 'DESC')
                ->get();
        }

        $response = [
            "response" => 200,
            'data' => $data,
        ];


        return response($response, 200);
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
 

    function addData(Request $request)
    {

        $validator = Validator::make(request()->all(), [
            'appointment_id' => 'required',
            'patient_id' => 'required',
            'medicines' => 'required|array',
            'medicines.*.medicine_name' => 'required|string',
            'medicines.*.duration' => 'required|string',
            'medicines.*.time' => 'required|string',
            'medicines.*.dose_interval' => 'required|string'


        ]);

        if ($validator->fails())
            return response(["response" => 400], 400);
        else {

            try {
                DB::beginTransaction();
                $appData = DB::table("appointments")
                ->select('appointments.*')
                ->where('appointments.id',$request->appointment_id)
                ->first();  

                $timeStamp = date("Y-m-d H:i:s");
                $dataModel = new PrescriptionModel;

                $dataModel->appointment_id = $request->appointment_id;

                $dataModel->patient_id = $request->patient_id;
                $dataModel->date = $timeStamp;
                $dataModel->test = $request->test;
                $dataModel->advice = $request->advice;
                $dataModel->problem_desc = $request->problem_desc;
                $dataModel->food_allergies = $request->food_allergies;
                $dataModel->tendency_bleed = $request->tendency_bleed;
                $dataModel->heart_disease = $request->heart_disease;
                $dataModel->blood_pressure = $request->blood_pressure;
                $dataModel->diabetic = $request->diabetic;
                $dataModel->surgery = $request->surgery;
                $dataModel->accident = $request->accident;
                $dataModel->others = $request->others;
                $dataModel->medical_history = $request->medical_history;
                $dataModel->current_medication = $request->current_medication;
                $dataModel->female_pregnancy = $request->female_pregnancy;
                $dataModel->breast_feeding = $request->breast_feeding;
                $dataModel->pulse_rate = $request->pulse_rate;
                $dataModel->temperature = $request->temperature;
                $dataModel->next_visit = $request->next_visit;
                $dataModel->created_at = $timeStamp;
                $dataModel->updated_at = $timeStamp;
                $dataModel->clinic_id = $appData->clinic_id;
                $qResponce = $dataModel->save();
                if (!$qResponce) {
                    DB::rollBack();
                    return Helpers::errorResponse("error");
                }
                foreach ($request->medicines as $medicine) {
                    $dataModelItem = new PrescriptionItemModel;
                    $dataModelItem->prescription_id = $dataModel->id;
                    $dataModelItem->medicine_name = $medicine['medicine_name'];
                    $dataModelItem->duration = $medicine['duration'];
                    $dataModelItem->time = $medicine['time'];
                    $dataModelItem->dose_interval = $medicine['dose_interval'];
                    $dataModelItem->created_at = $timeStamp;
                    $dataModelItem->updated_at = $timeStamp;

                    if (isset($medicine['notes'])) {
                        $dataModelItem->notes = $medicine['notes'];
                    }
                    if (isset($medicine['dosage'])) {
                        $dataModelItem->dosage = $medicine['dosage'];
                    }


                    $qResponce = $dataModelItem->save();

                    if (!$qResponce) {
                        DB::rollBack();
                        return Helpers::errorResponse("error");
                    }
                }
                if(isset($request->prescribed_test))
                {
                foreach ($request->prescribed_test as $test) {
                    $dataModePT = new prescribedTestModel;
                    $dataModePT->prescription_id  = $dataModel->id;
                    $dataModePT->notes = $test['notes']??Null;
                    $dataModePT->lab_test = $test['lab_test'];
                    $dataModePT->created_at = $timeStamp;
                    $dataModePT->updated_at = $timeStamp;

                    $qResponce = $dataModePT->save();

                    if (!$qResponce) {
                        DB::rollBack();
                        return Helpers::errorResponse("error");
                    }
                }

                }
               
                DB::commit();
                $notificationCentralController = new NotificationCentralController();

                $notificationCentralController->sendPrescrptionNotificationToUsers($request->appointment_id, $dataModel->id, "Add");

                return Helpers::successWithIdResponse("successfully", $dataModel->id);
            } catch (\Exception $e) {
                DB::rollBack();
                return Helpers::errorResponse("error $e");
            }
        }
    }

    // Update data
    function deleteData(Request $request)
    {


        $validator = Validator::make(request()->all(), [
            'id' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);
        try {
            DB::beginTransaction();
            $resDeleteItem = PrescriptionItemModel::where("prescription_id", $request->id)->delete();
            if (!$resDeleteItem) {

                DB::rollBack();
                return Helpers::errorResponse("error");
            }
            $prModel =   PrescriptionModel::where("id", $request->id)->first();
            $resDelete = PrescriptionModel::where("id", $request->id)->delete();

            if (!$resDelete) {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }

            DB::commit();

            $notificationCentralController = new NotificationCentralController();

            $notificationCentralController->sendPrescrptionNotificationToUsers($prModel->appointment_id, $request->id, "Delete");
            return Helpers::successResponse("successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("error $e");
        }
    }

    function updateData(Request $request)
    {


        $validator = Validator::make(request()->all(), [
            'id' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);
        try {
            DB::beginTransaction();
            $timeStamp = date("Y-m-d H:i:s");
            $dataModel = PrescriptionModel::where("id", $request->id)->first();

            if (isset($request->test)) {
                $dataModel->test = $request->test;
            }

            if (isset($request->advice)) {
                $dataModel->advice = $request->advice;
            }

            if (isset($request->problem_desc)) {
                $dataModel->problem_desc = $request->problem_desc;
            }

            if (isset($request->food_allergies)) {
                $dataModel->food_allergies = $request->food_allergies;
            }

            if (isset($request->tendency_bleed)) {
                $dataModel->tendency_bleed = $request->tendency_bleed;
            }

            if (isset($request->heart_disease)) {
                $dataModel->heart_disease = $request->heart_disease;
            }

            if (isset($request->blood_pressure)) {
                $dataModel->blood_pressure = $request->blood_pressure;
            }

            if (isset($request->diabetic)) {
                $dataModel->diabetic = $request->diabetic;
            }

            if (isset($request->surgery)) {
                $dataModel->surgery = $request->surgery;
            }

            if (isset($request->accident)) {
                $dataModel->accident = $request->accident;
            }

            if (isset($request->others)) {
                $dataModel->others = $request->others;
            }

            if (isset($request->medical_history)) {
                $dataModel->medical_history = $request->medical_history;
            }

            if (isset($request->current_medication)) {
                $dataModel->current_medication = $request->current_medication;
            }

            if (isset($request->female_pregnancy)) {
                $dataModel->female_pregnancy = $request->female_pregnancy;
            }

            if (isset($request->breast_feeding)) {
                $dataModel->breast_feeding = $request->breast_feeding;
            }

            if (isset($request->pulse_rate)) {
                $dataModel->pulse_rate = $request->pulse_rate;
            }

            if (isset($request->temperature)) {
                $dataModel->temperature = $request->temperature;
            }

            if (isset($request->next_visit)) {
                $dataModel->next_visit = $request->next_visit;
            }

            $dataModel->updated_at = $timeStamp;
            $qResponce = $dataModel->save();
            if (!$qResponce) {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }


            if (isset($request->medicines)) {
                $checkExists = PrescriptionItemModel::where("prescription_id", $request->id)->first();
                if ($checkExists) {
                    $resDeleteItem = PrescriptionItemModel::where("prescription_id", $request->id)->delete();
                    if (!$resDeleteItem) {
                        DB::rollBack();
                        return Helpers::errorResponse("error");
                    }
                }


                foreach ($request->medicines as $medicine) {
                    $dataModelItem = new PrescriptionItemModel;
                    $dataModelItem->prescription_id = $dataModel->id;
                    $dataModelItem->medicine_name = $medicine['medicine_name'];
                    $dataModelItem->duration = $medicine['duration'];
                    $dataModelItem->time = $medicine['time'];
                    $dataModelItem->dose_interval = $medicine['dose_interval'];
                    $dataModelItem->created_at = $timeStamp;
                    $dataModelItem->updated_at = $timeStamp;

                    if (isset($medicine['notes'])) {
                        $dataModelItem->notes = $medicine['notes'];
                    }
                    if (isset($medicine['dosage'])) {
                        $dataModelItem->dosage = $medicine['dosage'];
                    }

                    $qResponce = $dataModelItem->save();

                    if (!$qResponce) {
                        DB::rollBack();
                        return Helpers::errorResponse("error");
                    }
                }
            }

            
            if (isset($request->medicines)) {
                $checkExists = prescribedTestModel::where("prescription_id", $request->id)->first();
                if ($checkExists) {
                    $resDeleteItem = prescribedTestModel::where("prescription_id", $request->id)->delete();
                    if (!$resDeleteItem) {
                        DB::rollBack();
                        return Helpers::errorResponse("error");
                    }
                }

                       foreach ($request->prescribed_test as $test) {
                    $dataModePT = new prescribedTestModel;
                    $dataModePT->prescription_id  = $dataModel->id;
                    $dataModePT->notes = $test['notes']??Null;
                    $dataModePT->lab_test = $test['lab_test'];
                    $dataModePT->created_at = $timeStamp;
                    $dataModePT->updated_at = $timeStamp;

                    $qResponce = $dataModePT->save();

                    if (!$qResponce) {
                        DB::rollBack();
                        return Helpers::errorResponse("error");
                    }
                }
            }
            DB::commit();

            $notificationCentralController = new NotificationCentralController();

            $notificationCentralController->sendPrescrptionNotificationToUsers($dataModel->appointment_id, $request->id, "Update");

            return Helpers::successResponse("successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("error");
        }
    }
}
