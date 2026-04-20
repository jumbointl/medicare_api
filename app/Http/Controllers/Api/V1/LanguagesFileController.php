<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LanguagesFileModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;

class LanguagesFileController extends Controller
{

    // UPDATE LANGUAGE JSON
    function updateData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'json_data' => 'required|array'
        ]);

        if ($validator->fails())
            return response(["response" => 400], 400);

        try {

            $dataModel = LanguagesFileModel::where("id", $request->id)->first();
            if (!$dataModel)
                return Helpers::errorResponse("Language JSON not found");

            DB::beginTransaction();

            $dataModel->json_data = json_encode($request->json_data, JSON_UNESCAPED_UNICODE);
            $dataModel->updated_at = date("Y-m-d H:i:s");

            if ($dataModel->save()) {
                DB::commit();
                return Helpers::successResponse("successfully");
            } else {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("error $e");
        }
    }

    // GET LANGUAGE FILES
    public function getData(Request $request)
    {
        $query = DB::table("language_files")
            ->select(
                'language_files.*',
                'languages.title as language_title',
                'languages.code',
                'languages.direction'
            )
            ->leftJoin('languages', 'languages.id', '=', 'language_files.language_id')
            ->orderBy("language_files.created_at", "DESC");

        if ($request->has('language_id')) {
            $query->where('language_files.language_id', $request->language_id);
        }

        if ($request->has('scope')) {
            $query->where('language_files.scope', $request->scope);
        }

        $total_record = $query->count();

        if ($request->filled(['start', 'end'])) {
            $query->skip($request->start)->take($request->end - $request->start);
        }

        $data = $query->get();

        foreach ($data as $row) {
            $rawJson = $row->json_data;
            $row->json_data = json_decode($rawJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                \Log::error('LANG_JSON_DECODE_ERROR', [
                    'id' => $row->id ?? null,
                    'language_id' => $row->language_id ?? null,
                    'code' => $row->code ?? null,
                    'scope' => $row->scope ?? null,
                    'json_last_error' => json_last_error_msg(),
                    'raw_json' => $rawJson,
                ]);
            }
        }

        return response()->json([
            "response" => 200,
            "total_record" => $total_record,
            "data" => $data
        ], 200);
    }

    // GET BY ID
    function getDataById($id)
    {
        $data = DB::table("language_files")
            ->select(
                'language_files.*',
                'languages.title as language_title',
                'languages.code',
                'languages.direction'
            )
            ->leftJoin('languages', 'languages.id', '=', 'language_files.language_id')
            ->where('language_files.id', $id)
            ->first();

        if ($data) {
            $rawJson = $data->json_data;
            $data->json_data = json_decode($rawJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                \Log::error('LANG_JSON_DECODE_ERROR_BY_ID', [
                    'id' => $data->id ?? null,
                    'language_id' => $data->language_id ?? null,
                    'code' => $data->code ?? null,
                    'scope' => $data->scope ?? null,
                    'json_last_error' => json_last_error_msg(),
                    'raw_json' => $rawJson,
                ]);
            }
        }

        return response([
            "response" => 200,
            "data" => $data
        ], 200);
    }
     function getDataByScope(Request $request)
    {

        $data = DB::table("language_files")
            ->select(
                'language_files.*',
                'languages.title as language_title',
                'languages.code',
                'languages.direction'
            )
            ->leftJoin('languages', 'languages.id', '=', 'language_files.language_id');
   
    
            if(isset($request->scope)){
                $data = $data->where('language_files.scope', $request->scope);
            }
               if(isset($request->code)){
                $data = $data->where('languages.code', $request->code);
            }

            $data = $data->first();

        if ($data) {
            $rawJson = $data->json_data;
            $data->json_data = json_decode($rawJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                \Log::error('LANG_JSON_DECODE_ERROR_BY_SCOPE', [
                    'id' => $data->id ?? null,
                    'language_id' => $data->language_id ?? null,
                    'code' => $data->code ?? null,
                    'scope' => $data->scope ?? null,
                    'json_last_error' => json_last_error_msg(),
                    'raw_json' => $rawJson,
                ]);
            }
        }

        return $data!=null?$data->json_data:null;
    }


}
