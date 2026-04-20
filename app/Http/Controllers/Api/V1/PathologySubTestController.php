<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\CentralLogics\Helpers;
use App\Models\PathologySubTestModel;

class PathologySubTestController extends Controller
{
    // Add SubTest
    public function addData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'test_id'        => 'required|exists:pathology_test,id',
            'name'           => 'required|string|max:150',
            'ref_type'      => 'required',
            'unit_of_measure' => 'nullable|string|max:50',
            'general_range'  => 'nullable|string|max:100',
            'male_range'     => 'nullable|string|max:100',
            'female_range'   => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response(["response" => 400, "errors" => $validator->errors()], 400);
        }

        try {
            DB::beginTransaction();

            // Prevent duplicate name under same test
            $exists = PathologySubTestModel::where('test_id', $request->test_id)
                ->where('name', $request->name)
                ->first();

            if ($exists) {
                return Helpers::errorResponse("SubTest name already exists for this test.");
            }

            $subTest = new PathologySubTestModel();
            $subTest->test_id        = $request->test_id;
            $subTest->name           = $request->name;
            $subTest->unit_of_measure = $request->unit_of_measure;
            $subTest->general_range  = $request->general_range;
            $subTest->male_range     = $request->male_range;
            $subTest->female_range   = $request->female_range;
            $subTest->active         = $request->active ?? 1;
            $subTest->created_at     = now();
            $subTest->updated_at     = now();

            if ($subTest->save()) {
                DB::commit();
                return Helpers::successWithIdResponse("SubTest added successfully", $subTest->id);
            } else {
                DB::rollBack();
                return Helpers::errorResponse("Insert failed");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("Exception: " . $e->getMessage());
        }
    }

    // Update SubTest
    public function updateData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'   => 'required|exists:pathology_subtest,id',
            'name' => 'required|string|max:150',
        ]);

        if ($validator->fails()) {
            return response(["response" => 400, "errors" => $validator->errors()], 400);
        }

        try {
            DB::beginTransaction();

            $subTest = PathologySubTestModel::find($request->id);

            if ($request->has('name')) {
                $exists = PathologySubTestModel::where('test_id', $subTest->test_id)
                    ->where('name', $request->name)
                    ->where('id', '!=', $request->id)
                    ->first();
                if ($exists) {
                    return Helpers::errorResponse("SubTest name already exists for this test.");
                }
                $subTest->name = $request->name;
            }

            if ($request->has('ref_type')) $subTest->ref_type = $request->ref_type;
            if ($request->has('unit_of_measure')) $subTest->unit_of_measure = $request->unit_of_measure;
            if ($request->has('general_range'))   $subTest->general_range   = $request->general_range;
            if ($request->has('male_range'))      $subTest->male_range      = $request->male_range;
            if ($request->has('female_range'))    $subTest->female_range    = $request->female_range;

            $subTest->updated_at = now();

            if ($subTest->save()) {
                DB::commit();
                return Helpers::successResponse("SubTest updated successfully");
            } else {
                DB::rollBack();
                return Helpers::errorResponse("Update failed");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("Exception: " . $e->getMessage());
        }
    }

    // Get All SubTests
    public function getData(Request $request)
    {
        $query = DB::table("pathology_subtest")
            ->select('pathology_subtest.*', 'pathology_test.title as test_title')
            ->join('pathology_test', 'pathology_subtest.test_id', '=', 'pathology_test.id')
            ->orderBy('pathology_subtest.created_at', 'DESC');

        if ($request->filled('test_id')) {
            $query->where('pathology_subtest.test_id', $request->test_id);
        }
        if ($request->filled('active')) {
            $query->where('pathology_subtest.active', $request->active);
        }
        if ($request->has('search')) {
            $query->where('pathology_subtest.name', 'like', '%' . $request->search . '%');
        }

        $total_record = $query->count();

        if ($request->filled(['start', 'end'])) {
            $start = (int) $request->start;
            $end   = (int) $request->end;
            $query->skip($start)->take($end - $start);
        }

        $data = $query->get();

        return response()->json([
            "response" => 200,
            "total_record" => $total_record,
            "data" => $data
        ]);
    }

    // Get SubTest by ID
    public function getDataById($id)
    {
        $data = DB::table("pathology_subtest")
            ->select('pathology_subtest.*', 'pathology_test.title as test_title')
            ->join('pathology_test', 'pathology_subtest.test_id', '=', 'pathology_test.id')
            ->where('pathology_subtest.id', $id)
            ->first();

        return response()->json([
            "response" => 200,
            "data" => $data
        ]);
    }
    // public function getDataByTest($id)
    // {
    //     $data = DB::table("pathology_subtest")
    //         ->select('pathology_subtest.*', 'pathology_test.title as test_title')
    //         ->join('pathology_test', 'pathology_subtest.test_id', '=', 'pathology_test.id')
    //         ->where('pathology_test.id', $id)
    //         ->first();

    //     return response()->json([
    //         "response" => 200,
    //         "data" => $data
    //     ]);
    // }

    // Delete SubTest
    public function deleteData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:pathology_subtest,id'
        ]);

        if ($validator->fails()) {
            return response(["response" => 400, "errors" => $validator->errors()], 400);
        }

        try {
            $subTest = PathologySubTestModel::find($request->id);

            if ($subTest->delete()) {
                return Helpers::successResponse("SubTest deleted successfully");
            } else {
                return Helpers::errorResponse("Delete failed");
            }
        } catch (\Exception $e) {
            return Helpers::errorResponse("This SubTest cannot be deleted as it may be linked to reports. Try deactivating it instead.");
        }
    }
}
