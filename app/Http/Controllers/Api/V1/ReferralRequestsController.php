<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ReferralRequestsModel;
use App\Models\PatientClinicModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;

class ReferralRequestsController extends Controller
{
    function getDataById($id)
    {

  
        $data = DB::table("referral_requests")
        ->select(
            'referral_requests.*',
            'patients.f_name as patient_f_name',
            'patients.l_name as patient_l_name',
            'from_clinic.title as from_clinic_title',
            'to_clinic.title as to_clinic_title',
            'requesting_user.f_name as requested_by_f_name',
            'requesting_user.l_name as requested_by_l_name',
            'approving_user.f_name as approved_by_f_name',
            'approving_user.l_name as approved_by_l_name'
        )
        ->join('patients', 'patients.id', '=', 'referral_requests.patient_id')
        ->join('clinics as from_clinic', 'from_clinic.id', '=', 'referral_requests.from_clinic_id')
        ->join('clinics as to_clinic', 'to_clinic.id', '=', 'referral_requests.to_clinic_id')
        ->leftJoin('users as requesting_user', 'requesting_user.id', '=', 'referral_requests.requested_by')
        ->leftJoin('users as approving_user', 'approving_user.id', '=', 'referral_requests.approved_by')
        ->where('referral_requests.id','=',$id)
        ->first();
      
            $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
        }

    public function getData(Request $request)
{
    $query = DB::table("referral_requests")
        ->select(
            'referral_requests.*',
            'patients.f_name as patient_f_name',
            'patients.l_name as patient_l_name',
            'from_clinic.title as from_clinic_title',
            'to_clinic.title as to_clinic_title',
            'requesting_user.f_name as requested_by_f_name',
            'requesting_user.l_name as requested_by_l_name',
            'approving_user.f_name as approved_by_f_name',
            'approving_user.l_name as approved_by_l_name'
        )
        ->join('patients', 'patients.id', '=', 'referral_requests.patient_id')
        ->join('clinics as from_clinic', 'from_clinic.id', '=', 'referral_requests.from_clinic_id')
        ->join('clinics as to_clinic', 'to_clinic.id', '=', 'referral_requests.to_clinic_id')
        ->leftJoin('users as requesting_user', 'requesting_user.id', '=', 'referral_requests.requested_by')
        ->leftJoin('users as approving_user', 'approving_user.id', '=', 'referral_requests.approved_by')
        ->orderBy('referral_requests.created_at', 'DESC');

    // Apply date filters
    if ($request->filled('start_date')) {
        $query->whereDate('referral_requests.created_at', '>=', $request->start_date);
    }

    if ($request->filled('end_date')) {
        $query->whereDate('referral_requests.created_at', '<=', $request->end_date);
    }

    // Apply specific filters
    if ($request->filled('patient_id')) {
        $query->where('referral_requests.patient_id', $request->patient_id);
    }

    if ($request->filled('from_clinic_id')) {
        $query->where('referral_requests.from_clinic_id', $request->from_clinic_id);
    }

    if ($request->filled('to_clinic_id')) {
        $query->where('referral_requests.to_clinic_id', $request->to_clinic_id);
    }

    if ($request->filled('status')) {
        $query->where('referral_requests.status', $request->status);
    }

    // Search functionality
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where(DB::raw("CONCAT(patients.f_name, ' ', patients.l_name)"), 'like', "%$search%")
              ->orWhere(DB::raw("CONCAT(requesting_user.f_name, ' ', requesting_user.l_name)"), 'like', "%$search%")
              ->orWhere(DB::raw("CONCAT(approving_user.f_name, ' ', approving_user.l_name)"), 'like', "%$search%")
              ->orWhere('referral_requests.id', 'like', "%$search%")
              ->orWhere('from_clinic.title', 'like', "%$search%")
              ->orWhere('to_clinic.title', 'like', "%$search%");
        });
    }

    // Get total record count before pagination
    $total_record = $query->count();

    // Apply pagination
    if ($request->filled(['start', 'end'])) {
        $start = (int) $request->start;
        $end = (int) $request->end;
        $query->skip($start)->take($end - $start);
    }

    // Fetch the final data
    $data = $query->get();

    return response()->json([
        "response" => 200,
        "total_record" => $total_record,
        "data" => $data,
    ], 200);
}



    public function deleteRequest(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'referral_id' => 'required|exists:referral_requests,id',
            'from_clinic_id' => 'required|exists:clinics,id'
        ]);
    
        if ($validator->fails()) {
            return response(["response" => 400, "message" => "Validation error", "errors" => $validator->errors()], 400);
        }
    
        try {
            // Find the referral request
            $referral = ReferralRequestsModel::where('id', $request->referral_id)
                ->where('from_clinic_id', $request->from_clinic_id)
                ->where('status', 'Pending') // Only allow deleting pending requests
                ->first();
    
            if (!$referral) {
                return Helpers::errorResponse("Referral request not found or cannot be deleted.");
            }
    
            // Delete the referral request
            $referral->delete();
    
            return Helpers::successResponse("Referral request deleted successfully.");
        } catch (\Exception $e) {
            return Helpers::errorResponse("error");
        }
    }
public function updateStatus(Request $request)
{
    

    $validator = Validator::make($request->all(), [
        'referral_id' => 'required|exists:referral_requests,id',
        'status' => 'required|in:Approved,Rejected',
        'approved_by' => 'required|exists:users,id'
    ]);

    if ($validator->fails()) {
        return response(["response" => 400, "message" => "Validation error", "errors" => $validator->errors()], 400);
    }

    try {
        // Find referral request
        $referral = ReferralRequestsModel::find($request->referral_id);

        if (!$referral || $referral->status !== 'Pending') {
            return Helpers::errorResponse("Referral request not found or already processed.");
        }

        // Update referral request status
        $referral->status = $request->status;
        $referral->approved_by = $request->approved_by;
        $referral->updated_at = now();
        $referral->save();

        // If approved, link the patient to the clinic
        if ($request->status === 'Approved') {
            // Check if already linked
            // $existingLink = PatientClinicModel::where('patient_id', $referral->patient_id)
            //     ->where('clinic_id', $referral->to_clinic_id)
            //     ->first();

            // if (!$existingLink) {
            //     $newLink = new PatientClinicModel();
            //     $newLink->patient_id = $referral->patient_id;
            //     $newLink->clinic_id = $referral->to_clinic_id;
            //     $newLink->referral_requests_id = $request->referral_id;    
            //     $newLink->created_at = now();
            //     $newLink->updated_at = now();
            //     $newLink->save();
            // }

            return Helpers::successResponse("Referral approved");
        }

        return Helpers::successResponse("Referral request has been rejected.");
    } catch (\Exception $e) {
        return Helpers::errorResponse("error");
    }
}
        //add new data
       
public function addData(Request $request)
{
    // Validate request data
    $validator = Validator::make($request->all(), [
        'patient_id' => 'required|exists:patients,id',
        'from_clinic_id' => 'required|exists:clinics,id',
        'to_clinic_id' => 'required|exists:clinics,id',
        'requested_by' => 'required|exists:users,id'
    ]);

    if ($validator->fails()) {
        return response(["response" => 400, "message" => "Validation error", "errors" => $validator->errors()], 400);
    }

    try {
        // Check if a referral already exists
        $existingReferral = ReferralRequestsModel::where('patient_id', $request->patient_id)
            ->where('from_clinic_id', $request->from_clinic_id)
            ->where('to_clinic_id', $request->to_clinic_id)
            ->where('status', 'pending')
            ->first();

        if ($existingReferral) {
            return Helpers::errorResponse("Referral request is already pending.");
        }

        // Create a new referral request
        $referral = new ReferralRequestsModel();
        $referral->patient_id = $request->patient_id;
        $referral->from_clinic_id = $request->from_clinic_id;
        $referral->to_clinic_id = $request->to_clinic_id;
        $referral->requested_by = $request->requested_by;
        $referral->status = 'Pending'; // Default status
        $referral->created_at = now();
        $referral->updated_at = now();

        if ($referral->save()) {
            return Helpers::successWithIdResponse("Referral request submitted successfully.", $referral->id);
        } else {
            return Helpers::errorResponse("Failed to submit referral request.");
        }
    } catch (\Exception $e) {
           return Helpers::errorResponse("error");
    }
}

            
            //Delete Data
    //     function deleteData(Request $request){

    //         $validator = Validator::make(request()->all(), [
    //             'id' => 'required'
    //       ]);
    //       if ($validator->fails())
    //       return response (["response"=>400],400);
    //         try{
        
    //             $dataModel= PatientClinicModel::where("id",$request->id)->first();

    //                     $qResponce= $dataModel->delete();
    //                     if($qResponce)
    //                     {
                      
    //                         return Helpers::successResponse("successfully");}
            
    //                     else 
    //                     {
                       
    //                         return Helpers::errorResponse("error");}
    //         }
            
        
    //      catch(\Exception $e){
    //                     return Helpers::errorResponse("error");
    //                   }
    // }

    // get data

}
