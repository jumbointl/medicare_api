<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CouponModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;

class CouponController extends Controller
{

  function deleteData(Request $request)
  {

    $initialCheck = false;
    $validator = Validator::make(request()->all(), [
      'id' => 'required'
    ]);
    if ($validator->fails())
      return response(["response" => 400], 400);


    if ($initialCheck)
      return response(["response" => 400], 400);

    try {
      $timeStamp = date("Y-m-d H:i:s");
      $dataModel = CouponModel::where("id", $request->id)->first();


      $qResponce = $dataModel->delete();
      if ($qResponce) {
        return Helpers::successResponse("successfully");
      }
    } catch (\Exception $e) {

      return Helpers::errorResponse("error");
    }
  }

  function getDataById($id)
  {

    $data = DB::table("coupon")
      ->select('coupon.*')
      ->where('coupon.id', '=', $id)
      ->first();
    $response = [
      "response" => 200,
      'data' => $data,
    ];

    return response($response, 200);
  }

  public function getData(Request $request)
  {

    // Calculate the limit
    $start = $request->start;
    $end = $request->end;
    $limit = ($end - $start);

    // Define the base query


    $query = DB::table("coupon")
      ->select('coupon.*')
      ->orderBy('coupon.start_date', 'DESC');

    if (!empty($request->start_date)) {
      $query->whereDate('coupon.start_date', '>=', $request->start_date);
    }

    if (!empty($request->end_date)) {
      $query->whereDate('coupon.end_date', '<=', $request->end_date);
    }

    if ($request->filled('active')) {
      $query->where('coupon.active', '=', $request->active);
    }

    if ($request->filled('clinic_id')) {
      $query->where('coupon.clinic_id', '=', $request->clinic_id);
    }
    if ($request->filled('pathology_id')) {
      $query->where('coupon.pathology_id', '=', $request->pathology_id);
    }

    if ($request->has('search')) {
      $search = $request->input('search');
      $query->where(function ($q) use ($search) {
        $q->where('coupon.title', 'like', '%' . $search . '%');
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




  function getValidateLab(Request $request)
  {
    $validator = Validator::make(request()->all(), [
      'title' => 'required',
      'user_id' => 'required',
      'lab_id' => 'required'
    ]);
    if ($validator->fails())
      return response(["response" => 400], 400);
    $lab = DB::table("pathologist")
      ->select("coupon_enable")
      ->where("id", "=", $request->lab_id)
      ->first();

    // Check if the clinic exists and coupon is enabled
    if (!$lab || $lab->coupon_enable != 1) {
      return response([
        "response" => 200,
        "status" => false,
        "msg" => "Coupons are not enabled for this lab"
      ], 200);
    }

    $currentDate = date("Y-m-d");


    $data = DB::table("coupon")
      ->select('coupon.*')
      ->where('coupon.pathology_id', '=', $request->lab_id)
      ->where('coupon.title', '=', $request->title)
      ->where('coupon.active', '=', 1)
      ->where('coupon.start_date', '<=', $currentDate)  // Check if start_date is <= current date
      ->where('coupon.end_date', '>=', $currentDate)
      ->first();

    if ($data == null) {
      $msg = "Invalid Coupon";
      $status = false;
      $response = [
        "response" => 200,
        "status" => $status ?? true,
        "msg" => $msg ?? "Available",
        'data' => $data,
      ];

      return response($response, 200);
    }

    $dataCouponUse = DB::table("coupon_use")
      ->select('coupon_use.*')
      ->where('coupon_use.coupon_id', '=', $data->id)
      ->where('coupon_use.user_id', '=', $request->user_id)
      ->first();

    if ($dataCouponUse != null) {
      $msg = "coupon code is already used";
      $status = false;
      $data = null;
    }

    $response = [
      "response" => 200,
      "status" => $status ?? true,
      "msg" => $msg ?? "Available",
      'data' => $data,
    ];

    return response($response, 200);
  }
  function getValidate(Request $request)
  {
    $validator = Validator::make(request()->all(), [
      'title' => 'required',
      'user_id' => 'required',
      'clinic_id' => 'required'
    ]);
    if ($validator->fails())
      return response(["response" => 400], 400);
    $clinic = DB::table("clinics")
      ->select("coupon_enable")
      ->where("id", "=", $request->clinic_id)
      ->first();

    // Check if the clinic exists and coupon is enabled
    if (!$clinic || $clinic->coupon_enable != 1) {
      return response([
        "response" => 200,
        "status" => false,
        "msg" => "Coupons are not enabled for this clinic"
      ], 200);
    }

    $currentDate = date("Y-m-d");


    $data = DB::table("coupon")
      ->select('coupon.*')
      ->where('coupon.clinic_id', '=', $request->clinic_id)
      ->where('coupon.title', '=', $request->title)
      ->where('coupon.active', '=', 1)
      ->where('coupon.start_date', '<=', $currentDate)  // Check if start_date is <= current date
      ->where('coupon.end_date', '>=', $currentDate)
      ->first();

    if ($data == null) {
      $msg = "Invalid Coupon";
      $status = false;
      $response = [
        "response" => 200,
        "status" => $status ?? true,
        "msg" => $msg ?? "Available",
        'data' => $data,
      ];

      return response($response, 200);
    }

    $dataCouponUse = DB::table("coupon_use")
      ->select('coupon_use.*')
      ->where('coupon_use.coupon_id', '=', $data->id)
      ->where('coupon_use.user_id', '=', $request->user_id)
      ->first();

    if ($dataCouponUse != null) {
      $msg = "coupon code is already used";
      $status = false;
      $data = null;
    }

    $response = [
      "response" => 200,
      "status" => $status ?? true,
      "msg" => $msg ?? "Available",
      'data' => $data,
    ];

    return response($response, 200);
  }


  function updateDetails(Request $request)
  {

    $validator = Validator::make(request()->all(), [
      'id' => 'required'
    ]);
    if ($validator->fails())
      return response(["response" => 400], 400);

    try {
      $timeStamp = date("Y-m-d H:i:s");
      $dataModel = CouponModel::where("id", $request->id)->first();


      $alreadyExists = CouponModel::where('title', '=', $request->title)->where('clinic_id', '=', $request->clinic_id)->where('id', "!=", $request->id)->first();

      if ($alreadyExists) {
        return Helpers::errorResponse("title already exists");
      }
      if (isset($request->title))
        $dataModel->title = $request->title;

      if (isset($request->value))
        $dataModel->value = $request->value;

      if (isset($request->description))
        $dataModel->description = $request->description;

      if (isset($request->active))
        $dataModel->active = $request->active;

      if (isset($request->start_date))
        $dataModel->start_date = $request->start_date;
      if (isset($request->end_date))
        $dataModel->end_date = $request->end_date;

      $dataModel->updated_at = $timeStamp;


      $qResponce = $dataModel->save();


      if ($qResponce) {
        return Helpers::successWithIdResponse("successfully", $dataModel->id);
      }
    } catch (\Exception $e) {

      return Helpers::errorResponse("error");
    }
  }
  function addData(Request $request)
  {

    $validator = Validator::make(request()->all(), [
      'title' => 'required',
      'description' => 'required',
      'value' => 'required',
      'start_date' => 'required',
      'end_date' => 'required',
      'active' => 'required'
    ]);

    if ($validator->fails())
      return response(["response" => 400], 400);

    $alreadyExists = CouponModel::where('title', '=', $request->title)->where('clinic_id', '=', $request->clinic_id)->first();
    if ($alreadyExists) {
      return Helpers::errorResponse("title already exists");
    }
    try {
      $timeStamp = date("Y-m-d H:i:s");
      $dataModel = new CouponModel;
      $dataModel->title = $request->title;
      $dataModel->value = $request->value;
      $dataModel->description = $request->description;
      $dataModel->active = $request->active;
      $dataModel->start_date = $request->start_date;
      $dataModel->end_date = $request->end_date;
      $dataModel->created_at = $timeStamp;
      $dataModel->updated_at = $timeStamp;

      if (isset($request->clinic_id))
        $dataModel->clinic_id = $request->clinic_id;
      if (isset($request->pathology_id))
        $dataModel->pathology_id = $request->pathology_id;
      $qResponce = $dataModel->save();
      if ($qResponce) {
        return Helpers::successWithIdResponse("successfully", $dataModel->id);
      }
    } catch (\Exception $e) {

      return Helpers::errorResponse("error");
    }
  }
}
