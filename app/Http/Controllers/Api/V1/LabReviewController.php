<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LabBookingModel;
use App\Models\LabReviewModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Http\Request;

class LabReviewController extends Controller
{


  public function getData(Request $request)
  {
    // Define the base query
    $query = DB::table("lab_review")
      ->select(
        'lab_review.*',
        'patients.f_name as f_name',
        'patients.l_name as l_name',
        'pathologist.title as pathology_title'
      )
      ->join("pathologist", "pathologist.id", "=", "lab_review.path_id")
      ->join("patients", "patients.id", "=", "lab_review.user_id")
      ->orderBy('lab_review.created_at', 'DESC');

    if (!empty($request->path_id)) {
      $query->where('lab_review.path_id', '=', $request->path_id);
    }

    if (!empty($request->start_date)) {
      $query->whereDate('lab_review.created_at', '>=', $request->start_date);
    }

    if (!empty($request->end_date)) {
      $query->whereDate('lab_review.created_at', '<=', $request->end_date);
    }

    if (!empty($request->user_id)) {
      $query->where('lab_review.user_id ', $request->user_id);
    }

    if ($request->has('search')) {
      $search = $request->input('search');
      $query->where(function ($q) use ($search) {
        $q->where('lab_review.lab_booking_id ', 'like', '%' . $search . '%')
          ->orWhere('lab_review.user_id', 'like', '%' . $search . '%')
          ->orWhere('lab_review.id', 'like', '%' . $search . '%')
          ->orWhere('lab_review.description', 'like', '%' . $search . '%')
          ->orWhere('pathologist.title', 'like', '%' . $search . '%')
          ->orWhere(DB::raw('CONCAT(patients.f_name, " ", patients.l_name)'), 'like', '%' . $search . '%');
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
    if (!empty($request->path_id)) {
      $totalReviewPoints = $data->sum('points'); // Assuming 'review_points' is the column name for review points

      // Count the number of reviews
      $numberOfReviews = $data->count();
      // Calculate the average rating
      $averageRating = $numberOfReviews > 0 ? number_format($totalReviewPoints / $numberOfReviews, 2) : '0.00';
      $response = [
        'total_review_points' => $totalReviewPoints,
        'number_of_reviews' => $numberOfReviews,
        'average_rating' => $averageRating,
        "response" => 200,
        "total_record" => $total_record,
        'data' => $data,
      ];
    } else {
      $response = [
        "response" => 200,
        "total_record" => $total_record,
        'data' => $data,
      ];
    }


    return response()->json($response, 200);
  }


  function addData(Request $request)
  {

    $validator = Validator::make(request()->all(), [
      'points' => 'required',
      'lab_booking_id' => 'required'


    ]);

    if ($validator->fails())
      return response(["response" => 400], 400);
    else {

      try {
        if (isset($request->lab_booking_id)) {
          $alreadyExists = LabReviewModel::where('lab_booking_id', '=', $request->lab_booking_id)->first();
          if ($alreadyExists != null) {
            return Helpers::errorResponse("you have already submitted a review for this lab appointment");
          }
        }
        $timeStamp = date("Y-m-d H:i:s");
        $dataModel = new LabReviewModel;
        $appData = LabBookingModel::where('id', '=', $request->lab_booking_id)->first();

        $dataModel->path_id = $appData->pathology_id;
        $dataModel->points = $request->points;

        $dataModel->user_id = $appData->lab_patient_id;
        $dataModel->lab_booking_id   = $request->lab_booking_id;

        if (isset($request->description)) {
          $dataModel->description  = $request->description;
        }


        $dataModel->created_at = $timeStamp;
        $dataModel->updated_at = $timeStamp;
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
  }
}
