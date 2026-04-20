<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WebPageModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class WebPageController extends Controller
{
    function getData()
    {
        $data = DB::table("web_pages")
            ->select('web_pages.*')
            ->get();

        $response = [
            "response" => 200,
            'data' => $data,
        ];

        return response($response, 200);
    }
    function getDataByPageId($id)
    {
        $data = DB::table("web_pages")
            ->select('web_pages.*')
            ->where("web_pages.page_id", "=", $id)
            ->first();

        $response = [
            "response" => 200,
            'data' => $data,
        ];

        return response($response, 200);
    }

    function updateData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response(["response" => 400], 400);
        } else {
            try {
                $timeStamp = date("Y-m-d H:i:s");
                $dataModel = WebPageModel::where("page_id", $request->page_id)->first();

                if (isset($request->body)) {
                    $dataModel->body = $request->body;
                }

                // Update the title if it is provided in the request
                if (isset($request->title)) {
                    $dataModel->title = $request->title;
                }

                $dataModel->updated_at = $timeStamp;
                $qResponce = $dataModel->save();

                if ($qResponce) {
                    $response = [
                        "response" => 200,
                        'status' => true,
                        'message' => "successfully updated",
                    ];
                } else {
                    $response = [
                        "response" => 201,
                        'status' => false,
                        'message' => "error updating data",
                    ];
                }
                return response($response, 200);
            } catch (\Exception $e) {
                $response = [
                    "response" => 201,
                    'status' => false,
                    'message' => "error",
                ];
                return response($response, 200);
            }
        }
    }
}
