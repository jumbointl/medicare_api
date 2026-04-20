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
        // Define the base query
        $query = DB::table("doctors")
            ->select(
                'doctors.*',
                "users.f_name",
                "users.l_name",
                "users.phone",
                "users.isd_code",
                "users.gender",
                "users.dob",
                "users.email",
                "users.image",
                "department.title as department_name",
                'clinics.title as clinic_title',
                'cities.title as city_title',
                'states.title as state_title',
                "clinics.address as clinics_address",
            )
            ->join('users', 'users.id', '=', 'doctors.user_id')
            ->join('department', 'department.id', '=', 'doctors.department')
            ->join('clinics', 'clinics.id', '=', 'doctors.clinic_id')
            ->join('cities', 'cities.id', '=', 'clinics.city_id')
            ->join('states', 'states.id', '=', 'cities.state_id')
            ->orderBy("doctors.created_at", "DESC");

        // Apply filters (like search)
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('doctors.specialization', 'like', '%' . $search . '%')
                    ->orWhere(DB::raw('CONCAT(users.f_name, " ", users.l_name)'), 'like', '%' . $search . '%')
                    ->orWhere('users.l_name', 'LIKE', "%$search%")
                    ->orWhere('clinics.title', 'LIKE', "%$search%")
                    ->orWhere('clinics.address', 'LIKE', "%$search%")
                    ->orWhereRaw("CONCAT(f_name, ' ', l_name) LIKE ?", ["%$search%"]);
            });
        }
        if ($request->filled('active')) {
            $query->where('doctors.active', $request->active);
            $query->where('clinics.active', $request->active);
            $query->where('cities.active', $request->active);
            
        }
        if ($request->filled('department')) {
            $query->where('doctors.department', $request->department);
        }
        if ($request->filled('clinic_id')) {
            $query->where('doctors.clinic_id', $request->clinic_id);
        }

        if ($request->filled('city_id')) {
            $query->where('cities.id', $request->city_id);
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

        // Add extra data like reviews and appointments to each doctor
        foreach ($data as $dataDoctor) {
            $dataDR = DB::table("doctors_review")
                ->select('doctors_review.*')
                ->where("doctors_review.doctor_id", "=", $dataDoctor->user_id)
                ->get();

            $totalReviewPoints = $dataDR->sum('points'); // Assuming 'review_points' is the column name for review points
            $numberOfReviews = $dataDR->count();
            $averageRating = $numberOfReviews > 0 ? number_format($totalReviewPoints / $numberOfReviews, 2) : '0.00';

            $dataDoctor->total_review_points = $totalReviewPoints;
            $dataDoctor->number_of_reviews = $numberOfReviews;
            $dataDoctor->average_rating = $averageRating;

            $dataDApp = DB::table("appointments")
                ->select('appointments.*')
                ->where("appointments.doct_id", "=", $dataDoctor->user_id)
                ->get();

            $dataDoctor->total_appointment_done = count($dataDApp);
        }

        // Return the response with total records and data
        $response = [
            "response" => 200,
            "total_record" => $total_record,
            'data' => $data,
        ];

        return response()->json($response, 200);
    }



    function getDataById($id)
    {

        $data = DB::table("doctors")
            ->select(
                'doctors.*',
                "users.f_name",
                "users.l_name",
                "users.phone",
                "users.isd_code",
                "users.gender",
                "users.dob",
                "users.email",
                "users.image",
                "users.address",
                "users.city",
                "users.state",
                "users.postal_code",
                "users.isd_code_sec",
                "users.phone_sec",
                "department.title as department_name",
                'clinics.title as clinic_title',
                "clinics.address as clinics_address",
                'clinics.image as clinic_thumb_image',
                'clinics.phone as clinic_phone',
                'clinics.phone_second as clinic_phone_second',
                'clinics.email as clinic_email',
                'clinics.stop_booking as clinic_stop_booking',
                'clinics.coupon_enable as clinic_coupon_enable',
                'clinics.latitude as clinic_latitude',
                'clinics.longitude as clinic_longitude',
                'clinics.tax as clinic_tax',
                'cities.title as city_title',
                'states.title as state_title'  ,
                
            )
            ->Join('users', 'users.id', '=', 'doctors.user_id')
            ->join('department', 'department.id', '=', 'doctors.department')
            ->join('clinics', 'clinics.id', '=', 'doctors.clinic_id')
            ->join('cities', 'cities.id', '=', 'clinics.city_id')
            ->join('states', 'states.id', '=', 'cities.state_id')
            ->where("doctors.user_id", "=", $id)
            ->first();

        if ($data != null) {

            $dataDR = DB::table("doctors_review")
                ->select('doctors_review.*')
                ->where("doctors_review.doctor_id", "=", $data->user_id)
                ->get();
            // Calculate the total review points
            $totalReviewPoints = $dataDR->sum('points'); // Assuming 'review_points' is the column name for review points

            // Count the number of reviews
            $numberOfReviews = $dataDR->count();
            // Calculate the average rating
            $averageRating = $numberOfReviews > 0 ? number_format($totalReviewPoints / $numberOfReviews, 2) : '0.00';

            $data->total_review_points = $totalReviewPoints;
            $data->number_of_reviews = $numberOfReviews;
            $data->average_rating = $averageRating;

            $dataDApp = DB::table("appointments")
                ->select('appointments.*')
                ->where("appointments.doct_id", "=", $data->user_id)
                ->get();
            // Calculate the total review points
            $data->total_appointment_done = count($dataDApp);
        }


        if ($data) {
            $data->clinic_images = DB::table("clinic_images")
                ->select('clinic_images.*')
                ->where('clinic_images.clinic_id', $data->clinic_id)
                ->OrderBy('created_at', 'desc')
                ->get();
        }

        $response = [
            "response" => 200,
            'data' => $data,
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
}
