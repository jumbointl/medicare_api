<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DoctorModel;
use App\Models\RoleAssignModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;



class DoctorController extends Controller
{
    // add new users
    function addData(Request $request)
    {

        $validator = Validator::make(request()->all(), [
            'f_name' => 'required',
            'l_name' => 'required',
            'email' => 'required',
            'password' => 'required',
            'department' => 'required',
            'ex_year' => 'required',
            'active' => 'required',
            'specialization' => 'required',
            'dob' => 'required',
            'gender' => 'required',
            'clinic_id' => 'required',

        ]);

        if ($validator->fails())

            return response(["response" => 400], 400);
        else {

            if (isset($request->phone)) {
                $alreadyAddedModel = User::where("phone", $request->phone)->first();
                if ($alreadyAddedModel) {
                    return Helpers::errorResponse("phone number already exists");
                }
            }
            if (isset($request->email)) {
                $alreadyAddedModel = User::where("email", $request->email)->first();
                if ($alreadyAddedModel) {
                    return Helpers::errorResponse("email id already exists");
                }
            }

            if (isset($request->phone)) {
                if (!is_numeric($request->phone)) {
                    return Helpers::errorResponse("Please enter valid phone number");
                }
            }
            try {
                DB::beginTransaction();
                $timeStamp = date("Y-m-d H:i:s");
                $userModel = new User;
                if (isset($request->phone)) {
                    $userModel->phone = $request->phone;
                }
                if (isset($request->email)) {
                    $userModel->email = $request->email;
                }

                if (isset($request->password)) {
                    $userModel->password = Hash::Make($request->password);
                } else if (!isset($request->password)) {
                    $userModel->password = Hash::make(Str::random(8));
                }
                $userModel->f_name = $request->f_name;
                $userModel->l_name = $request->l_name;

                if (isset($request->dob)) {
                    $userModel->dob = $request->dob;
                }
                if (isset($request->gender)) {
                    $userModel->gender = $request->gender;
                }
                if (isset($request->isd_code)) {
                    $userModel->isd_code = $request->isd_code;
                }

            if (isset($request->clinic_id)) {
                                $userModel->clinic_id = $request->clinic_id;
                            }

                $userModel->created_at = $timeStamp;
                $userModel->updated_at = $timeStamp;
                if (isset($request->image)) {

                    $userModel->image =  $request->hasFile('image') ? Helpers::uploadImage('users/', $request->file('image')) : null;
                }
                $qResponce = $userModel->save();


                if ($qResponce) {
                    $doctorModel = new DoctorModel;
                    $doctorModel->user_id = $userModel->id;

                    $doctorModel->department = $request->department;

                    if (isset($request->description)) {
                        $doctorModel->description = $request->description;
                    }
                    $doctorModel->ex_year = $request->ex_year;
                    $doctorModel->specialization = $request->specialization;
                    $doctorModel->active = $request->active;

                    $doctorModel->clinic_id = $request->clinic_id;

                    $doctorModel->created_at = $timeStamp;
                    $doctorModel->updated_at = $timeStamp;
                    $qResponceDoct = $doctorModel->save();

                    if ($qResponceDoct) {

                        $dataModel = new RoleAssignModel;
                        $dataModel->role_id = 18;
                        $dataModel->user_id = $userModel->id;
                        $dataModel->created_at = $timeStamp;
                        $dataModel->updated_at = $timeStamp;
                        $dataModel->save();
                        DB::commit();
                        return Helpers::successWithIdResponse("successfully", $userModel->id);
                    }
                } else {
                    DB::rollBack();
                    return Helpers::errorResponse("error");
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return Helpers::errorResponse("error $e");
            }
        }
    }
    function removeImage(Request $request)
    {


        $validator = Validator::make(request()->all(), [
            'id' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);
        try {
            $dataModel = User::where("id", $request->id)->first();


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

    // Update Data

    function updateData(Request $request)
    {


        $validator = Validator::make(request()->all(), [
            'id' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);
        try {
            DB::beginTransaction();

            //user data

            if (isset($request->phone)) {
                $alreadyAddedModel = User::where("phone", $request->phone)->where('id', "!=", $request->id)->first();

                if ($alreadyAddedModel) {
                    return Helpers::errorResponse("phone number already exists");
                }
            }
            if (isset($request->email)) {
                $alreadyAddedModel = User::where("email", $request->email)->where('id', "!=", $request->id)->first();

                if ($alreadyAddedModel) {
                    return Helpers::errorResponse("email id already exists");
                }
            }
            $userModel = User::where("id", $request->id)->first();
            if (isset($request->f_name)) {
                $userModel->f_name = $request->f_name;
            }
            if (isset($request->l_name)) {
                $userModel->l_name = $request->l_name;
            }

            if (isset($request->phone)) {
                $userModel->phone = $request->phone;
            }
            if (isset($request->isd_code)) {
                $userModel->isd_code = $request->isd_code;
            }
            if (isset($request->gender)) {
                $userModel->gender = $request->gender;
            }
            if (isset($request->dob)) {
                $userModel->dob = $request->dob;
            }
            if (isset($request->email)) {
                $userModel->email = $request->email;
            }
            if (isset($request->password)) {
                $userModel->password = Hash::Make($request->password);
            }

            if (isset($request->isd_code_sec)) {
                $userModel->isd_code_sec = $request->isd_code_sec;
            }
            if (isset($request->phone_sec)) {
                $userModel->phone_sec = $request->phone_sec;
            }
            if (isset($request->address)) {
                $userModel->address = $request->address;
            }
            if (isset($request->state)) {
                $userModel->state = $request->state;
            }
            if (isset($request->city)) {
                $userModel->city = $request->city;
            }
            if (isset($request->postal_code)) {
                $userModel->postal_code = $request->postal_code;
            }

            if (isset($request->image)) {
                if ($request->hasFile('image')) {

                    $oldImage = $userModel->image;
                    $userModel->image =  Helpers::uploadImage('users/', $request->file('image'));
                    if (isset($oldImage)) {
                        if ($oldImage != "def.png") {
                            Helpers::deleteImage($oldImage);
                        }
                    }
                }
            }
            $res = $userModel->save();
            if ($res) {
                // doctors data 
                $dataModel = DoctorModel::where("user_id", $request->id)->first();

                if (isset($request->description)) {
                    $dataModel->description = $request->description;
                }

                if (isset($request->department)) {
                    $dataModel->department = $request->department;
                }

                if (isset($request->description)) {
                    $dataModel->description = $request->description;
                }
                if (isset($request->specialization)) {
                    $dataModel->specialization = $request->specialization;
                }
                if (isset($request->ex_year)) {
                    $dataModel->ex_year = $request->ex_year;
                }
                if (isset($request->zoom_client_id)) {
                    $dataModel->zoom_client_id = $request->zoom_client_id;
                }
                if (isset($request->zoom_secret_id)) {
                    $dataModel->zoom_secret_id = $request->zoom_secret_id;
                }


                if (isset($request->active)) {
                    $dataModel->active = $request->active;
                }
                if (isset($request->insta_link)) {
                    $dataModel->insta_link = $request->insta_link;
                }
                if (isset($request->fb_linik)) {
                    $dataModel->fb_linik = $request->fb_linik;
                }
                if (isset($request->twitter_link)) {
                    $dataModel->twitter_link = $request->twitter_link;
                }
                if (isset($request->you_tube_link)) {
                    $dataModel->you_tube_link = $request->you_tube_link;
                }
                if (isset($request->video_appointment)) {
                    $dataModel->video_appointment = $request->video_appointment;
                }
                if (isset($request->clinic_appointment)) {
                    $dataModel->clinic_appointment = $request->clinic_appointment;
                }
                if (isset($request->emergency_appointment)) {
                    $dataModel->emergency_appointment = $request->emergency_appointment;
                }

                if (isset($request->opd_fee)) {
                    $dataModel->opd_fee = $request->opd_fee;
                }
                if (isset($request->video_fee)) {
                    $dataModel->video_fee = $request->video_fee;
                }
                if (isset($request->emg_fee)) {
                    $dataModel->emg_fee = $request->emg_fee;
                }

                if (isset($request->stop_booking)) {
                    $dataModel->stop_booking = $request->stop_booking;
                }
            } else {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }

            $timeStamp = date("Y-m-d H:i:s");
            $dataModel->updated_at = $timeStamp;
            $qResponce = $dataModel->save();
            if ($qResponce) {
                DB::commit();
                return Helpers::successResponse("successfully");
            } else {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("error");
        }
    }

    public function getData(Request $request)
    {
        $query = DB::table('v_doctors')->select('*');

        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', (int) $request->doctor_id);
        }

        if ($request->filled('clinic_id')) {
            $query->where('clinic_id', (int) $request->clinic_id);
        }

        if ($request->filled('active')) {
            $query->where('active', (int) $request->active);
        }

        if ($request->filled('stop_booking')) {
            $query->where('stop_booking', (int) $request->stop_booking);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function ($q) use ($search) {
                $q->where('doctor_name', 'like', "%{$search}%")
                ->orWhere('f_name', 'like', "%{$search}%")
                ->orWhere('l_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('specialization', 'like', "%{$search}%")
                ->orWhere('department_title', 'like', "%{$search}%")
                ->orWhere('clinic_title', 'like', "%{$search}%");
            });
        }

        $data = $query
            ->orderBy('doctor_name')
            ->get();

        return response()->json([
            'response' => 200,
            'data' => $data,
        ], 200);
    }
    public function getDataById($id)
    {
        $rows = DB::table('v_doctors')
            ->where('doctor_id', (int) $id)
            ->orderBy('clinic_title')
            ->get();

        if ($rows->isEmpty()) {
            return response()->json([
                'response' => 404,
                'message' => 'Doctor not found',
                'data' => null,
            ], 404);
        }

        $first = $rows->first();

        $data = (object) [
            'doctor_id' => $first->doctor_id,
            'user_id' => $first->user_id,
            'f_name' => $first->f_name,
            'l_name' => $first->l_name,
            'doctor_name' => $first->doctor_name,
            'email' => $first->email,
            'phone' => $first->phone,
            'image' => $first->image,
            'gender' => $first->gender,
            'dob' => $first->dob,
            'address' => $first->address,
            'city' => $first->city,
            'state' => $first->state,
            'postal_code' => $first->postal_code,
            'department' => $first->department,
            'department_title' => $first->department_title,
            'specialization' => $first->specialization,
            'ex_year' => $first->ex_year,
            'video_appointment' => $first->video_appointment,
            'video_provider' => $first->video_provider,
            'clinic_appointment' => $first->clinic_appointment,
            'emergency_appointment' => $first->emergency_appointment,
            'opd_fee' => $first->opd_fee,
            'video_fee' => $first->video_fee,
            'emg_fee' => $first->emg_fee,
            'clinics' => $rows->map(function ($row) {
                return [
                    'clinic_id' => $row->clinic_id,
                    'clinic_title' => $row->clinic_title,
                    'is_active' => $row->is_active,
                    'is_default' => $row->is_default,
                    'active' => $row->active,
                    'stop_booking' => $row->stop_booking,
                ];
            })->values(),
        ];

        return response()->json([
            'response' => 200,
            'data' => $data,
        ], 200);
    }

    function deleteData(Request $request){
        $validator = Validator::make(request()->all(), [
            'id' => 'required'
      ]);
      if ($validator->fails())
      return response (["response"=>400],400);
        try{ 
            DB::beginTransaction();
            $dataModel= DoctorModel::where("user_id",$request->id)->first();
            $userId=$dataModel->user_id;
            $dataModelUser= User::where("id",$request->id)->first();
             $oldImage = $dataModelUser->image;
            DB::table('family_members')->where('user_id', $userId)->delete();
            DB::table('doctors')->where('user_id', $userId)->delete();
            DB::table('users_role_assign')->where('user_id', $userId)->delete();
            DB::table('users')->where('id', $userId)->delete();
              
                // $qResponce= $dataModel->delete();
                
                // if($qResponce){
                 
                    if(isset($oldImage)){
                        if($oldImage!="def.png"){
                            Helpers::deleteImage($oldImage);
                        }         
                    }
                   DB::commit();
                return Helpers::successResponse("successfully Deleted");
          //  }
            //     else 
            //   { 
            //     DB::rollBack();
            //     return Helpers::errorResponse("error");    }
         
        }
    
     catch(\Exception $e){
        DB::rollBack();
        return Helpers::errorResponse("This record cannot be deleted because it is linked to multiple data entries in the system. You can only deactivate it to prevent future use.");
                  }         
    
    }
    public function updateDoctorClinicStatus(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|integer',
            'clinic_id' => 'required|integer',
            'active' => 'nullable|integer',
            'stop_booking' => 'nullable|integer',
        ]);

        $doctor = DB::table('doctors')
            ->where('id', $request->doctor_id)
            ->first();

        if (!$doctor) {
            return response()->json([
                'status' => false,
                'message' => 'Doctor not found.',
            ], 404);
        }

        $query = DB::table('user_clinics')
            ->where('user_id', $doctor->user_id)
            ->where('clinic_id', $request->clinic_id);

        $exists = $query->exists();

        if (!$exists) {
            return response()->json([
                'status' => false,
                'message' => 'Doctor clinic relation not found.',
            ], 404);
        }

        $data = [
            'updated_at' => now(),
        ];

        if ($request->has('active')) {
            $data['active'] = (int) $request->active;
        }

        if ($request->has('stop_booking')) {
            $data['stop_booking'] = (int) $request->stop_booking;
        }

        $query->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Doctor clinic status updated successfully.',
        ], 200);
    }
    public function getDoctorsByClinic($clinicId)
    {
        $data = DB::table('v_doctors')
            ->where('clinic_id', (int) $clinicId)
            ->where('active', 1)
            ->where('stop_booking', 0)
            ->orderBy('doctor_name')
            ->get();

        return response()->json([
            'response' => 200,
            'data' => $data,
        ], 200);
    }
}
