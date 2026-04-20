<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LabTestCartModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LabTestCartController extends Controller
{
    // Add or update lab test cart item
    function addData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lab_test_id' => 'required',
            'user_id' => 'required',
            'qty' => 'required',
        ]);

        if ($validator->fails()) {
            return response(["response" => 400, "errors" => $validator->errors()], 400);
        }

        try {
            $timeStamp = now();

            $existingItem = LabTestCartModel::where("user_id", $request->user_id)
                ->where("lab_test_id", $request->lab_test_id)
                ->first();

            // Handle deletion if qty is 0
            if (isset($request->qty) && $request->qty == 0) {
                if ($existingItem) {
                    $deleted = $existingItem->delete();
                    if (!$deleted) {
                        return Helpers::errorResponse("Failed to delete item.");
                    }
                    return Helpers::successResponse("Successfully deleted.");
                } else {
                    return Helpers::errorResponse("Item not found for deletion.");
                }
            }

            // Handle insert/update
            $dataModel = $existingItem ?? new LabTestCartModel;

            $dataModel->lab_test_id = $request->lab_test_id;
            $dataModel->user_id = $request->user_id;
            $dataModel->qty = $request->qty;
            //?? ($existingItem->qty + 1 ?? 1);
            $dataModel->updated_at = $timeStamp;

            if (!$existingItem) {
                $dataModel->created_at = $timeStamp;
            }

            $saved = $dataModel->save();

            if (!$saved) {
                return Helpers::errorResponse("Failed to save cart item.");
            }

            return Helpers::successWithIdResponse("Successfully saved.", $dataModel->id);
        } catch (\Exception $e) {
            return Helpers::errorResponse("Exception: " . $e->getMessage());
        }
    }



    public function getData(Request $request)
    {
        // Define the base query
        $query = DB::table("lab_user_cart")
            ->select(
                'lab_user_cart.*',
                'pathology_test.title',
                'pathology_test.sub_title',
                'pathology_test.amount',
                'pathology_test.image',
                'pathologist.title as pathologist_title',
                'pathologist.id as pathologist_id'

            )
            ->join('pathology_test', 'pathology_test.id', 'lab_user_cart.lab_test_id')
            ->join('pathologist', 'pathologist.id', 'pathology_test.pathology_id')
            ->orderBy("lab_user_cart.created_at", "DESC");

        if ($request->filled('user_id')) {
            $query->where('lab_user_cart.user_id', $request->user_id);
        }
        if ($request->filled('path_id')) {
            $query->where('pathologist.id', $request->path_id);
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

        if(isset($request->with_subtest) && $request->with_subtest==1){
            //loop through data and add pathology_subtest
              if($data){
              foreach($data as $key=>$value){
              $data[$key]->pathology_subtest= DB::table("pathology_subtest")
            ->select('pathology_subtest.*')
            ->where('pathology_subtest.test_id', $value->lab_test_id)
            ->orderBy('pathology_subtest.created_at', 'DESC')
            ->get();
            }
        }
    }


        // Return the response with total records and data
        $response = [
            "response" => 200,
            "total_record" => $total_record,
            'data' => $data,
        ];

        return response()->json($response, 200);
    }

    function deleteData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response(["response" => 400, "errors" => $validator->errors()], 400);
        }

        try {
            $timeStamp = now();

            $resD = LabTestCartModel::where("id", $request->id)
                ->delete();


            if (!$resD) {
                return Helpers::errorResponse("Failed to delete cart item.");
            }

            return Helpers::successResponse("Successfully deleted.");
        } catch (\Exception $e) {
            return Helpers::errorResponse("Exception: " . $e->getMessage());
        }
    }

    function deleteAndAddLabTest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'lab_test_ids' => 'required',
        ]);

        if ($validator->fails()) {
            return response(["response" => 400, "errors" => $validator->errors()], 400);
        }

        try {
            $timeStamp = now();
            $resD = false;
            $getData = LabTestCartModel::where("user_id", $request->user_id)->get();
            if (count($getData) > 0) {
                $resD = LabTestCartModel::where("user_id", $request->user_id)
                    ->delete();
            } else {
                $resD = true;
            }

            if (!$resD) {
                return Helpers::errorResponse("Failed to delete cart item.");
                //lab_test_id comma separated

            }
            // {user_id: 137, lab_test_ids: 49,43}
            $lab_test_ids = explode(",", $request->lab_test_ids);



            foreach ($lab_test_ids as $lab_test_id) {

                $dataModel = new LabTestCartModel;
                $dataModel->lab_test_id = $lab_test_id;
                $dataModel->user_id = $request->user_id;
                $dataModel->qty = 1;
                $dataModel->created_at = $timeStamp;
                $dataModel->updated_at = $timeStamp;

                $saved = $dataModel->save();

                if (!$saved) {
                    return Helpers::errorResponse("Failed to save cart item.");
                }
            }

            return Helpers::successResponse("Successfully");
        } catch (\Exception $e) {
            return Helpers::errorResponse("Exception: " . $e->getMessage());
        }
    }

    // rebokFor web
    public function deleteAndAddLabTestForWeb(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'lab_booking_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response([
                "response" => 400,
                "errors" => $validator->errors()
            ], 400);
        }

        DB::beginTransaction();

        try {
            $timeStamp = now();

            // 🔹 Get lab_test_ids from lab_booking_items table
            $labTestIds = DB::table('lab_booking_items')
                ->where('lab_booking_id', $request->lab_booking_id)
                ->pluck('lab_test_id');

            if ($labTestIds->isEmpty()) {
                DB::rollBack();
                return Helpers::errorResponse("No lab tests found for this booking.");
            }

            // 🔹 Delete existing cart items for the user
            LabTestCartModel::where('user_id', $request->user_id)->delete();

            // 🔹 Insert new cart items
            $insertData = [];

            foreach ($labTestIds as $lab_test_id) {
                $insertData[] = [
                    'user_id'     => $request->user_id,
                    'lab_test_id' => $lab_test_id,
                    'qty'         => 1,
                    'created_at'  => $timeStamp,
                    'updated_at'  => $timeStamp,
                ];
            }

            LabTestCartModel::insert($insertData);
            DB::commit();
            return Helpers::successResponse("Lab tests successfully added to cart.");
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("Exception: " . $e->getMessage());
        }
    }
}
