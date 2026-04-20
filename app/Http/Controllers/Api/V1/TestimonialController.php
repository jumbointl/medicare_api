<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TestimonialsModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\ImageModel;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;

class TestimonialController extends Controller
{

  function deleteData(Request $request)
  {

    $initialCheck = false;
    $validator = Validator::make(request()->all(), [
      'id' => 'required'
    ]);
    if ($validator->fails()) {
      return Helpers::errorResponse("error");
    }
    DB::beginTransaction();

    try {
      $timeStamp = date("Y-m-d H:i:s");
      $dataModel = TestimonialsModel::where("id", $request->id)->first();
      $oldImage = $dataModel->image;

      if ($oldImage != null) {

        Helpers::deleteImage($oldImage);
      }
      $qResponce = $dataModel->delete();
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

  function updateData(Request $request)
  {
    $initialCheck = false;
    $validator = Validator::make(request()->all(), [
      'id' => 'required'
    ]);
    if ($validator->fails())
      return response(["response" => 400], 400);

    try {
      $timeStamp = date("Y-m-d H:i:s");
      $dataModel = TestimonialsModel::where("id", $request->id)->first();

      if (isset($request->title))
        $dataModel->title = $request->title;
      if (isset($request->sub_title))
        $dataModel->sub_title = $request->sub_title;
      if (isset($request->rating))
        $dataModel->rating = $request->rating;
      if (isset($request->description))
        $dataModel->description = $request->description;
      $dataModel->updated_at = $timeStamp;
      if (isset($request->image)) {
        if ($request->hasFile('image')) {
          $oldImage = $dataModel->image;
          $dataModel->image =  Helpers::uploadImage('testimonial/', $request->file('image'));
          if (isset($oldImage)) {
            if ($oldImage != "def.png") {
              Helpers::deleteImage($oldImage);
            }
          }
        }
      }

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


  public function getData(Request $request)
  {
    // Define the base query
    $query = DB::table("testimonials")
      ->select('testimonials.*')
      ->whereNull('testimonials.path_id')
      ->orderBy('testimonials.created_at', 'DESC');
    if (!empty($request->start_date)) {
      $query->whereDate('testimonials.created_at', '>=', $request->start_date);
    }
    if (!empty($request->end_date)) {
      $query->whereDate('testimonials.created_at', '<=', $request->end_date);
    }
    if (!empty($request->clinic_id)) {
      $query->where('testimonials.clinic_id', $request->clinic_id);
    }

    if ($request->has('search')) {
      $search = $request->input('search');
      $query->where(function ($q) use ($search) {
        $q->where('testimonials.title', 'like', '%' . $search . '%')
          ->orWhere('testimonials.sub_title', 'like', '%' . $search . '%');
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

  public function getDataLab(Request $request)
  {
    $query = DB::table("testimonials")
      ->whereNotNull('path_id') // 🔥 ONLY pathology
      ->orderBy('created_at', 'DESC');

    // Date filters
    if ($request->filled('start_date')) {
      $query->whereDate('created_at', '>=', $request->start_date);
    }

    if ($request->filled('end_date')) {
      $query->whereDate('created_at', '<=', $request->end_date);
    }

    // Pathology filter
    if ($request->filled('path_id')) {
      $query->where('path_id', $request->path_id);
    }

    // Search
    if ($request->filled('search')) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('title', 'like', "%{$search}%")
          ->orWhere('sub_title', 'like', "%{$search}%");
      });
    }

    $total_record = $query->count();

    // Pagination
    if ($request->filled(['start', 'end'])) {
      $start = (int) $request->start;
      $end = (int) $request->end;
      $query->skip($start)->take($end - $start);
    }

    return response()->json([
      "response" => 200,
      "total_record" => $total_record,
      "data" => $query->get(),
    ]);
  }


  function getDataById($id)
  {

    $data = DB::table("testimonials")
      ->select(
        'testimonials.*'
      )
      ->where("testimonials.id", "=", $id)
      ->first();
    $response = [
      "response" => 200,
      'data' => $data,
    ];

    return response($response, 200);
  }

  function addData(Request $request)
  {
    DB::beginTransaction();

    $validator = Validator::make(request()->all(), [
      'title' => 'required',
      'sub_title' => 'required',
      'rating' => 'required',
      'description' => 'required'

    ]);

    if ($validator->fails())
      return response(["response" => 400], 400);
    else {

      try {
        $timeStamp = date("Y-m-d H:i:s");
        $dataModel = new TestimonialsModel;

        $dataModel->title = $request->title;
        $dataModel->sub_title = $request->sub_title;
        $dataModel->rating  = $request->rating;
        $dataModel->clinic_id  = $request->clinic_id ?? Null;
        $dataModel->path_id  = $request->pathology_id ?? Null;
        $dataModel->description = $request->description;

        if (isset($request->image)) {

          $dataModel->image =  $request->hasFile('image') ? Helpers::uploadImage('testimonial/', $request->file('image')) : null;
        }

        $dataModel->created_at = $timeStamp;
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
  }
  function removeImage(Request $request)
  {


    $validator = Validator::make(request()->all(), [
      'id' => 'required'
    ]);
    if ($validator->fails())
      return response(["response" => 400], 400);
    try {
      $dataModel = TestimonialsModel::where("id", $request->id)->first();


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
}
