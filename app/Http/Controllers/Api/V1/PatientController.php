<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PatientModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PatientController extends Controller
{
    //add new data
    function addData(Request $request)
    {

        $validator = Validator::make(request()->all(), [
            'f_name' => 'required',
            'l_name' => 'required'

        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);

        try {

            $timeStamp = date("Y-m-d H:i:s");
            $dataModel = new PatientModel;

            $dataModel->f_name = $request->f_name;
            $dataModel->l_name = $request->l_name;
            $dataModel->clinic_id = $request->clinic_id ?? Null;
            $dataModel->pathology_id  = $request->pathology_id ?? Null;
            if (isset($request->isd_code)) {
                $dataModel->isd_code = $request->isd_code;
            }
            if (isset($request->phone)) {
                $dataModel->phone = $request->phone;
            }
            if (isset($request->city)) {
                $dataModel->city = $request->city;
            }
            if (isset($request->state)) {
                $dataModel->state = $request->state;
            }
            if (isset($request->address)) {
                $dataModel->address = $request->address;
            }
            if (isset($request->email)) {
                $dataModel->email = $request->email;
            }
            if (isset($request->gender)) {
                $dataModel->gender = $request->gender;
            }
            if (isset($request->dob)) {
                $dataModel->dob = $request->dob;
            }
            if (isset($request->postal_code)) {
                $dataModel->postal_code = $request->postal_code;
            }
            if (isset($request->notes)) {
                $dataModel->notes = $request->notes;
            }
            if (isset($request->user_id)) {
                $dataModel->user_id = $request->user_id;
            }


            if (isset($request->image)) {

                $dataModel->image =  $request->hasFile('image') ? Helpers::uploadImage('patients/', $request->file('image')) : null;
            }


            $dataModel->created_at = $timeStamp;
            $dataModel->updated_at = $timeStamp;

            $qResponce = $dataModel->save();
            if ($qResponce) {

                $dataModel->mrn = $dataModel->id;
                $dataModel->save();


                return Helpers::successWithIdResponse("successfully", $dataModel->id);
            } else {

                return Helpers::errorResponse("error");
            }
        } catch (\Exception $e) {


            return Helpers::errorResponse("error");
        }
    }

    // Update data
    function updateData(Request $request)
    {


        $validator = Validator::make(request()->all(), [
            'id' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);
        try {
            $dataModel = PatientModel::where("id", $request->id)->first();

            if (isset($request->f_name)) {
                $dataModel->f_name = $request->f_name;
            }
            if (isset($request->l_name)) {
                $dataModel->l_name = $request->l_name;
            }
            if (isset($request->gender)) {
                $dataModel->gender = $request->gender;
            }
            if (isset($request->isd_code)) {
                $dataModel->isd_code = $request->isd_code;
            }
            if (isset($request->phone)) {
                $dataModel->phone = $request->phone;
            }
            if (isset($request->city)) {
                $dataModel->city = $request->city;
            }
            if (isset($request->state)) {
                $dataModel->state = $request->state;
            }
            if (isset($request->address)) {
                $dataModel->address = $request->address;
            }
            if (isset($request->email)) {
                $dataModel->email = $request->email;
            }
            if (isset($request->gender)) {
                $dataModel->gender = $request->gender;
            }
            if (isset($request->dob)) {
                $dataModel->dob = $request->dob;
            }
            if (isset($request->postal_code)) {
                $dataModel->postal_code = $request->postal_code;
            }
            if (isset($request->notes)) {
                $dataModel->notes = $request->notes;
            }
            if (isset($request->user_id)) {
                $dataModel->user_id = $request->user_id;
            }

            if (isset($request->image)) {
                if ($request->hasFile('image')) {

                    $oldImage = $dataModel->image;
                    $dataModel->image =  Helpers::uploadImage('patients/', $request->file('image'));
                    if (isset($oldImage)) {
                        if ($oldImage != "def.png") {
                            Helpers::deleteImage($oldImage);
                        }
                    }
                }
            }

            $timeStamp = date("Y-m-d H:i:s");

            $dataModel->updated_at = $timeStamp;
            $qResponce = $dataModel->save();
            if ($qResponce) {

                return Helpers::successResponse("successfully");
            } else {

                return Helpers::errorResponse("error");
            }
        } catch (\Exception $e) {
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

        $query = DB::table("patients")
            ->select(
                'patients.*',
                'clinics.title',
                'pathologist.title as pathology_title'
            )
            ->Leftjoin("clinics", 'clinics.id', 'patients.clinic_id')
            ->Leftjoin("pathologist", 'pathologist.id', 'patients.pathology_id')
            ->orderBy('patients.created_at', 'DESC');

        if (!empty($request->start_date)) {
            $query->whereDate('patients.created_at', '>=', $request->start_date);
        }

        if (!empty($request->end_date)) {
            $query->whereDate('patients.created_at', '<=', $request->end_date);
        }

        if ($request->filled('user_id')) {
            $query->where('patients.user_id', '=', $request->user_id);
        }

        if ($request->filled('clinic_id')) {


            $query->where('patients.clinic_id', '=', $request->clinic_id);
        }

        if ($request->filled('pathology_id')) {


            $query->where('patients.pathology_id', '=', $request->pathology_id);
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where(DB::raw('CONCAT(patients.f_name, " ", patients.l_name)'), 'like', '%' . $search . '%')
                    ->orWhere('patients.id', 'like', '%' . $search . '%')
                    ->orWhere('patients.phone', 'like', '%' . $search . '%')
                    ->orWhere('patients.city', 'like', '%' . $search . '%')
                    ->orWhere('patients.state', 'like', '%' . $search . '%')
                    ->orWhere('patients.address', 'like', '%' . $search . '%')
                    ->orWhere('patients.email', 'like', '%' . $search . '%')
                    ->orWhere('patients.gender', 'like', '%' . $search . '%')
                    ->orWhere('patients.dob', 'like', '%' . $search . '%');
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

        $data = DB::table("patients")
            ->select('patients.*')
            ->where('id', '=', $id)
            ->first();

        $response = [
            "response" => 200,
            'data' => $data,
        ];

        return response($response, 200);
    }
    function getDataByMrn($mrn)
    {
        $data = DB::table("patients")
            ->select(
                'id',
                'f_name',
                'l_name',
                'mrn',
                'phone'
            )
            ->where('mrn', $mrn)
            ->wherenotNull('clinic_id')
            ->first();

        return response([
            "response" => 200,
            "data" => $data,
        ], 200);
    }


    function removeImage(Request $request)
    {


        $validator = Validator::make(request()->all(), [
            'id' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);
        try {
            $dataModel = PatientModel::where("id", $request->id)->first();


            $oldImage = $dataModel->image;
            if (isset($oldImage)) {
                if ($oldImage != "def.png") {
                    Helpers::deleteImage($oldImage);
                }

                $dataModel->image = null;
            }

            $timeStamp = date("Y-m-d H:i:s");
            $dataModel->updated_at = $timeStamp;

            $qResponce = $dataModel->save();
            if ($qResponce)
                return Helpers::successResponse("successfully");

            else
                return Helpers::errorResponse("error");
        } catch (\Exception $e) {

            return Helpers::errorResponse("error");
        }
    }


    function deleteData(Request $request)
    {

        $validator = Validator::make(request()->all(), [
            'id' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);
        try {
            DB::beginTransaction();
            $dataModelUser = PatientModel::where("id", $request->id)->first();

            $oldImage = $dataModelUser->image;
            $qResponce = $dataModelUser->delete();

            if ($qResponce) {

                if (isset($oldImage)) {
                    if ($oldImage != "def.png") {
                        Helpers::deleteImage($oldImage);
                    }
                }
                DB::commit();
                return Helpers::successResponse("successfully Deleted");
            } else {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("This record cannot be deleted because it is linked to multiple data entries in the system.");
        }
    }

    // Update data
    function linkMRNLabAndClinicPatient(Request $request)
    {

        $validator = Validator::make(request()->all(), [
            'mrn' => 'required',
            'pathology_id' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);
        try {
            $dataModelExists = PatientModel::where("mrn", $request->mrn)->where("pathology_id", $request->pathology_id)->first();
            if (isset($dataModelExists)) {

                return Helpers::successWithIdResponse("successfully", $dataModelExists->id);
            }

            $dataModelExistsPatient =  PatientModel::where("mrn", $request->mrn)->first();

            if (!isset($dataModelExistsPatient)) {
                return Helpers::errorResponse("Patient Not Exists!");
            }

            $dataModel = new PatientModel;
            $dataModel = $dataModelExistsPatient->replicate();
            $dataModel->clinic_id = null;
            $dataModel->image = null;
            $dataModel->pathology_id = $request->pathology_id;
            $dataModel->created_at = now();
            $dataModel->updated_at = now();



            $qResponce = $dataModel->save();
            if ($qResponce) {

                return Helpers::successWithIdResponse("successfully", $dataModel->id);
            } else {

                return Helpers::errorResponse("error");
            }
        } catch (\Exception $e) {
            return Helpers::errorResponse("error");
        }
    }

    //               function deleteData(Request $request){

    //   $validator = Validator::make(request()->all(), [
    //                     'id' => 'required'  ]);
    //               if ($validator->fails())
    //               return response (["response"=>400],400);
    //                 try{ 
    //                     DB::beginTransaction();
    //                     $dataModelUser= PatientModel::where("id",$request->id)->first();

    //                      $oldImage = $dataModelUser->image;
    //                         $qResponce= $dataModelUser->delete();

    //                         if($qResponce){

    //                             if(isset($oldImage)){
    //                                 if($oldImage!="def.png"){
    //                                     Helpers::deleteImage($oldImage);
    //                                 }         
    //                             }
    //                            DB::commit();
    //                         return Helpers::successResponse("successfully Deleted");
    //                    }
    //                         else 
    //                       { 
    //                         DB::rollBack();
    //                         return Helpers::errorResponse("error");    }

    //                 }

    //              catch(\Exception $e){
    //                 DB::rollBack();
    //                 return Helpers::errorResponse("This record cannot be deleted because it is linked to multiple data entries in the system.");
    //                           }         

    //             }  


}
