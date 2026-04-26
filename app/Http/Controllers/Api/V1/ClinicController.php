<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClinicsModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\RoleAssignModel;

class ClinicController extends Controller
{
    //add new data
    function addData(Request $request)
    {


        $validator = Validator::make(request()->all(), [
            'title' => 'required',
            'email' => 'required',
            'password' => 'required',
            'f_name'   => 'required',
            'l_name'   => 'required',
            'city_id'   => 'required'
        ]);

        if ($validator->fails())
            return response(["response" => 400], 400);

        try {

            $alreadyAddedModel = User::where("email", $request->email)->first();
            if ($alreadyAddedModel) {
                return Helpers::errorResponse("email id already exists");
            }

            $alreadyAddedModel = ClinicsModel::where("title", $request->title)->where("city_id", $request->city_id)->first();
            if ($alreadyAddedModel) {
                return Helpers::errorResponse("title already exists");
            } else {
                DB::beginTransaction();

                $userModel = new User;

                $userModel->email = $request->email;
                $userModel->password = Hash::Make($request->password);


                $userModel->f_name = $request->f_name;
                $userModel->l_name = $request->l_name;
                $qResponceC = $userModel->save();
                if (!$qResponceC) {
                    DB::rollBack();

                    return Helpers::errorResponse("error");
                }

                $timeStamp = date("Y-m-d H:i:s");
                $dataModel = new ClinicsModel;

                $dataModel->title = $request->title;
                $dataModel->city_id  = $request->city_id;
                $dataModel->user_id  = $userModel->id;
                $dataModel->created_at = $timeStamp;
                $dataModel->updated_at = $timeStamp;


                $qResponce = $dataModel->save();
                if ($qResponce) {

                    $dataModelR = new RoleAssignModel;
                    $dataModelR->role_id = 21;
                    $dataModelR->user_id = $userModel->id;
                    $dataModelR->created_at = $timeStamp;
                    $dataModelR->updated_at = $timeStamp;
                    $qResponceR = $dataModelR->save();

                    if (!$qResponceR) {
                        DB::rollBack();

                        return Helpers::errorResponse("error");
                    }


                    $userModel = User::where("id", $userModel->id)->first();
                    $userModel->clinic_id =  $dataModel->id;
                    $userModel->save();
                    DB::commit();

                    return Helpers::successWithIdResponse("successfully", $dataModel->id);
                } else {
                    DB::rollBack();

                    return Helpers::errorResponse("error");
                }
            }
        } catch (\Exception $e) {
            DB::rollBack();

            return Helpers::errorResponse("error $e");
        }
    }
    // Update Deapartment
    function updateData(Request $request)
    {

        $validator = Validator::make(request()->all(), [
            'id' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);
        try {
            DB::beginTransaction();

            $dataModel = ClinicsModel::where("id", $request->id)->first();
            if (isset($request->title)) {
                $alreadyExists = ClinicsModel::where("title", $request->title)->where("city_id", $request->city_id)->where('id', "!=", $request->id)->first();
                if ($alreadyExists != null) {
                    return Helpers::errorResponse("title already exists");
                } else {
                    $dataModel->title = $request->title;
                }
            }
            if (isset($request->email)) {
                $alreadyExists = ClinicsModel::where("email", $request->email)->where('id', "!=", $request->id)->first();
                if ($alreadyExists != null) {
                    return Helpers::errorResponse("Email id already exists");
                }
            }

            if (isset($request->phone)) {
                $alreadyExists = ClinicsModel::where("phone", $request->phone)->where('id', "!=", $request->id)->first();
                if ($alreadyExists != null) {
                    return Helpers::errorResponse("Phone number already exists");
                }
            }



            if (isset($request->address)) {
                $dataModel->address = $request->address;
            }
            if (isset($request->latitude)) {
                $dataModel->latitude = $request->latitude;
            }
            if (isset($request->longitude)) {
                $dataModel->longitude = $request->longitude;
            }
            if (isset($request->active)) {
                $dataModel->active = $request->active;
            }
            if (isset($request->description)) {
                $dataModel->description = $request->description;
            }

            if (isset($request->email)) {
                $dataModel->email = $request->email;
            }
            if (isset($request->phone)) {
                $dataModel->phone = $request->phone;
            }
            if (isset($request->phone_second)) {
                $dataModel->phone_second = $request->phone_second;
            }
            if (isset($request->ambulance_btn_enable)) {
                $dataModel->ambulance_btn_enable = $request->ambulance_btn_enable;
            }
            if (isset($request->ambulance_number)) {
                $dataModel->ambulance_number = $request->ambulance_number;
            }
            if (isset($request->stop_booking)) {
                $dataModel->stop_booking = $request->stop_booking;
            }
            if (isset($request->coupon_enable)) {
                $dataModel->coupon_enable = $request->coupon_enable;
            }
            if (isset($request->tax)) {
                $dataModel->tax = $request->tax;
            }
            if (isset($request->city_id)) {
                $dataModel->city_id = $request->city_id;
            }
            if (isset($request->opening_hours)) {
                $dataModel->opening_hours = $request->opening_hours;
            }
            if (isset($request->whatsapp)) {
                $dataModel->whatsapp = $request->whatsapp;
            }
            if (isset($request->image)) {
                if ($request->hasFile('image')) {

                    $oldImage = $dataModel->image;
                    $dataModel->image =  Helpers::uploadImage('clinics/', $request->file('image'));
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




    // get data

    public function getData(Request $request)
    {
        // Define the base query
        $query = DB::table("clinics")
            ->select(
                'clinics.*',
                'cities.title as city_title',
                'states.title as state_title'
            )
            ->join('cities', 'cities.id', '=', 'clinics.city_id')
            ->join('states', 'states.id', '=', 'cities.state_id')
            ->orderBy('clinics.created_at', 'desc');

        // Apply city filter
        if (!empty($request->city_id)) {
            $query->where('clinics.city_id', (int) $request->city_id);
        }

        // Apply active filter
        if ($request->has('active') && $request->active !== '') {
            $active = (int) $request->active;

            $query->where('clinics.active', $active);
            $query->where('cities.active', $active);
        }

        // Apply single clinic filter
        if (!empty($request->clinic_id)) {
            $query->where('clinics.id', (int) $request->clinic_id);
        }

        /*
        * Apply multiple clinic filter.
        * Example from frontend:
        * get_clinic?active=1&clinic_ids=5,6,7
        */
        if (!empty($request->clinic_ids)) {
            $clinicIds = $request->clinic_ids;

            if (is_string($clinicIds)) {
                $clinicIds = explode(',', $clinicIds);
            }

            $clinicIds = collect($clinicIds)
                ->map(fn ($id) => (int) trim($id))
                ->filter(fn ($id) => $id > 0)
                ->values()
                ->all();

            if (!empty($clinicIds)) {
                $query->whereIn('clinics.id', $clinicIds);
            }
        }

        // Apply search filter
        if ($request->has('search') && trim((string) $request->search) !== '') {
            $search = trim((string) $request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('clinics.title', 'like', '%' . $search . '%')
                    ->orWhere('clinics.address', 'like', '%' . $search . '%')
                    ->orWhere('cities.title', 'like', '%' . $search . '%')
                    ->orWhere('states.title', 'like', '%' . $search . '%');
            });
        }

        // Get total records before pagination
        $total_record = (clone $query)->count();

        // Handle pagination only if both 'start' and 'end' are provided and valid
        if ($request->filled(['start', 'end'])) {
            $start = max((int) $request->start, 0);
            $end = max((int) $request->end, $start);

            if ($end > $start) {
                $query->skip($start)->take($end - $start);
            }
        }

        // Fetch the data
        $data = $query->get();

        return response()->json([
            "response" => 200,
            "total_record" => $total_record,
            "data" => $data,
        ], 200);
    }







    // get data by id


    function getDataById($id)
    {

        $data = DB::table("clinics")
            ->select(
                'clinics.*',
                'cities.title as city_title',
                'states.title as state_title'
            )
            ->where('clinics.id', $id)
            ->join('cities', 'cities.id', '=', 'clinics.city_id')
            ->join('states', 'states.id', '=', 'cities.state_id')
            ->OrderBy('created_at', 'desc')
            ->first();

        if ($data) {
            $data->clinic_images = DB::table("clinic_images")
                ->select('clinic_images.*')
                ->where('clinic_images.clinic_id', $id)
                ->OrderBy('created_at', 'desc')
                ->get();
        }

        $response = [
            "response" => 200,
            'data' => $data,
        ];

        return response($response, 200);
    }

    public function getDataPeg(Request $request)
    {


        // Calculate the limit
        $start = $request->start;
        $end = $request->end;
        $limit = ($end - $start);

        // Define the base query

        $query = DB::table("clinics")
            ->select(
                'clinics.*',
                'cities.title as city_title',
                'states.title as state_title'
            )
            ->join('cities', 'cities.id', '=', 'clinics.city_id')
            ->join('states', 'states.id', '=', 'cities.state_id')
            ->OrderBy('created_at', 'desc');

        if (!empty($request->city_id)) {
            $query->where('clinics.city_id', $request->city_id);
        }

        if (!empty($request->active)) {
            $query->where('clinics.active', '=', $request->active);
        }
        if (!empty($request->clinic_id)) {
            $query->where('clinics.id', '=', $request->clinic_id);
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('clinics.title', 'like', '%' . $search . '%')
                    ->orWhere('clinics.address', 'like', '%' . $search . '%');
            });
        }

        $total_record = $query->get()->count();
        $data = $query->skip($start)->take($limit)->get();

        $response = [
            "response" => 200,
            "total_record" => $total_record,
            'data' => $data,
        ];

        return response()->json($response, 200);
    }


    function removeImage(Request $request)
    {


        $validator = Validator::make(request()->all(), [
            'id' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);
        try {
            $dataModel = ClinicsModel::where("id", $request->id)->first();


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
            $dataModel = ClinicsModel::where("id", $request->id)->first();
            $userId = $dataModel->user_id;
            $oldImage = $dataModel->image;


            DB::table('family_members')->where('user_id', $userId)->delete();
            DB::table('users')->where('id', $userId)->update(['clinic_id' => null]);
            DB::table('users_role_assign')->where('user_id', $userId)->delete();
            DB::table('clinics')->where('id', $request->id)->delete();
            DB::table('users')->where('id', $userId)->delete();

            // $qResponce= $dataModel->delete();

            // if($qResponce){

            if (isset($oldImage)) {
                if ($oldImage != "def.png") {
                    Helpers::deleteImage($oldImage);
                }
            }
            DB::commit();
            return Helpers::successResponse("successfully Deleted");
            //  }
            //     else 
            //   { 
            //     DB::rollBack();
            //     return Helpers::errorResponse("error");   
            //  }

        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("This record cannot be deleted because it is linked to multiple data entries in the system. You can only deactivate it to prevent future use.");
        }
    }
}
