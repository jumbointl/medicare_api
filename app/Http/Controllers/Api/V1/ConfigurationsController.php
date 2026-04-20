<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ConfigurationsModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;

class ConfigurationsController extends Controller
{

    function getDataAll()
    {
        $data = DB::table('configurations')->pluck('value', 'id_name');

        $response = [
            "response" => 200,
            'data' => $data,
        ];

        return response($response, 200);
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

            $dataModel = ConfigurationsModel::where("id", $request->id)->first();
            if (isset($request->value)) {
                if ($request->hasFile('value')) {
                    $oldImage = $dataModel->value;
                    $dataModel->value =  Helpers::uploadImage('configurations/', $request->file('value'));
                    if (isset($oldImage)) {
                        if ($oldImage != "def.png") {
                            Helpers::deleteImage($oldImage);
                        }
                    }
                } else {
                    $dataModel->value = $request->value;
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

    function getData()
    {

        $data = DB::table("configurations")
            ->select(
                'configurations.*'
            )
            ->get();

        $response = [
            "response" => 200,
            'data' => $data,
        ];

        return response($response, 200);
    }

    // get data by id

    function getDataById($id)
    {

        $data = DB::table("configurations")
            ->select('configurations.*')
            ->where('configurations.id', '=', $id)
            ->first();

        $response = [
            "response" => 200,
            'data' => $data,
        ];

        return response($response, 200);
    }

    function getDataByIdName($idName)
    {

        $data = DB::table("configurations")
            ->select('configurations.*')
            ->where('configurations.id_name', '=', $idName)
            ->first();

        $response = [
            "response" => 200,
            'data' => $data,
        ];

        return response($response, 200);
    }

    function getDataByGroupName($group_name)
    {

        $data = DB::table("configurations")
            ->select('configurations.*')
            ->where('configurations.group_name', '=', $group_name)
            ->get();

        $response = [
            "response" => 200,
            'data' => $data,
        ];

        return response($response, 200);
    }

    function removeImage(Request $request)
    {


        $validator = Validator::make(request()->all(), [
            'id' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);
        try {
            $dataModel = ConfigurationsModel::where("id", $request->id)->first();


            $oldImage = $dataModel->value;
            if (isset($oldImage)) {
                if ($oldImage != "def.png") {
                    Helpers::deleteImage($oldImage);
                }

                $dataModel->value = null;
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
