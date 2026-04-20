<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PathologyTestModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PathologyTestController extends Controller
{
    //add new data
function addData(Request $request)
{
    $validator = Validator::make($request->all(), [
        'title' => 'required',
        'pathology_id' => 'required',
        'report_day' => 'required',
        'amount' => 'required',
         'cat_id' => 'required'
       // 'sub_cat_id' => 'required'
    ]);

    if ($validator->fails()) {
        return response(["response" => 400], 400);
    }

       // Validate sub_cat_id exists
        if (!DB::table('pathology_test_category')->where('id', $request->cat_id)->exists()) {
            return Helpers::errorResponse("Invalid cat_id: not found in pathology test category.");
        }

    try {
        $alreadyAddedModel = PathologyTestModel::where("title", $request->title)
            ->where("pathology_id", $request->pathology_id)
            ->first();

        if ($alreadyAddedModel) {
            return Helpers::errorResponse("Title already exists");
        } else {
            DB::beginTransaction();

            $timeStamp = date("Y-m-d H:i:s");

            $dataModel = new PathologyTestModel;
            $dataModel->title = $request->title;
            $dataModel->pathology_id = $request->pathology_id;
            $dataModel->report_day = $request->report_day;
            $dataModel->amount = $request->amount;
            $dataModel->cat_id = $request->cat_id;
            $dataModel->created_at = $timeStamp;
            $dataModel->updated_at = $timeStamp;

            if ($request->hasFile('image')) {
                $dataModel->image = Helpers::uploadImage('pathology_test/', $request->file('image'));
            }

            if (isset($request->description)) {
                $dataModel->description = $request->description;
            }

            $qResponce = $dataModel->save();

            if ($qResponce) {
                DB::commit();
                return Helpers::successWithIdResponse("Successfully added", $dataModel->id);
            } else {
                DB::rollBack();
                return Helpers::errorResponse("Insert failed");
            }
        }
    } catch (\Exception $e) {
        DB::rollBack();
        return Helpers::errorResponse("Exception occurred: " . $e->getMessage());
    }
}

// Update Deapartment
function updateData(Request $request){
    
    $validator = Validator::make(request()->all(), [
        'id' => 'required'
  ]);
  if ($validator->fails())
  return response (["response"=>400],400);
    try{
        DB::beginTransaction();
        $dataModel= PathologyTestModel::where("id",$request->id)->first();
        if(isset($request->title)){
            $alreadyExists = PathologyTestModel::where('title', '=', $request->title)->where('id',"!=",$request->id)->where("pathology_id",$request->pathology_id)->first();
            if ($alreadyExists != null)
            {
                return Helpers::errorResponse("title already exists");
            }
            else{
                $dataModel->title= $request->title;
            }
        }
        if(isset($request->sub_title)){
            $dataModel->sub_title= $request->sub_title;
        }

        if(isset($request->report_day)){
            $dataModel->report_day= $request->report_day;
        }
         if(isset($request->cat_id )){
            $dataModel->cat_id = $request->cat_id ;
        }
         if(isset($request->amount)){
            $dataModel->amount= $request->amount;
        }
         if(isset($request->active)){
            $dataModel->active= $request->active;
        }
         if(isset($request->description)){
            $dataModel->description= $request->description;
        }

        
      
        if(isset($request->image)){
            if($request->hasFile('image') ){

            $oldImage = $dataModel->image;
            $dataModel->image =  Helpers::uploadImage('pathology_test/', $request->file('image'));
            if(isset($oldImage)){
                if($oldImage!="def.png"){
                    Helpers::deleteImage($oldImage);
                }
            }
        }
        }

        $timeStamp= date("Y-m-d H:i:s");
        $dataModel->updated_at=$timeStamp;
                $qResponce= $dataModel->save();
                if($qResponce)
                {
                    DB::commit();
                    return Helpers::successResponse("successfully");}
    
                else 
                {
                    DB::rollBack();
                    return Helpers::errorResponse("error");}
    }
    

 catch(\Exception $e){
    DB::rollBack();
                return Helpers::errorResponse("error $e");
              }
            }


 // Remove Image
function removeImage(Request $request){


    $validator = Validator::make(request()->all(), [
        'id' => 'required'
  ]);
  if ($validator->fails())
  return response (["response"=>400],400);
    try{
        $dataModel= PathologyTestModel::where("id",$request->id)->first();
  

            $oldImage = $dataModel->image;
            if(isset($oldImage)){
                if($oldImage!="def.png"){
                    Helpers::deleteImage($oldImage);
                }

                $dataModel->image=null;
            }
 
            $timeStamp= date("Y-m-d H:i:s");
            $dataModel->updated_at=$timeStamp;
            
                $qResponce= $dataModel->save();
                if($qResponce)
                return Helpers::successResponse("successfully");
    
                else 
                return Helpers::errorResponse("error");
    }
    

 catch(\Exception $e){
          
                return Helpers::errorResponse("error $e");
              }
            }

               // get data

    public function getData(Request $request)
  {

    // Calculate the limit
    $start = $request->start;
    $end = $request->end;
    $limit = ($end - $start);

    // Define the base query



        $query = DB::table("pathology_test")
            ->select(
                'pathology_test.*',
                'pathology_test_category.title as cat_title',
                'pathology_test_category.title as cat_title',
                'pathology_test_category.id as cat_id',

            )
            ->Leftjoin('pathology_test_category', 'pathology_test.cat_id', '=', 'pathology_test_category.id')
            ->orderBy('pathology_test.created_at', 'DESC');

    if ($request->filled('active')) {
      $query->where('pathology_test.active', '=', 1);
    }
 if ($request->filled('pathology_id')) {
      $query->where('pathology_test.pathology_id', '=', $request->pathology_id);
 }
  if ($request->filled('cat_id')) {
      $query->where('pathology_test.cat_id', '=', $request->cat_id);
 }
    

    if ($request->has('search')) {
      $search = $request->input('search');
      $query->where(function ($q) use ($search) {
        $q->where('pathology_test.title', 'like', '%' . $search . '%');
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

       if($request->filled('with_subtest') && $request->with_subtest==true){
        foreach($data as $dataItem){
              $dataItem->pathology_subtest= DB::table("pathology_subtest")
            ->select('pathology_subtest.*', 'pathology_test.title as test_title')
            ->join('pathology_test', 'pathology_subtest.test_id', '=', 'pathology_test.id')
            ->where('pathology_subtest.test_id', $dataItem->id)
            ->orderBy('pathology_subtest.created_at', 'DESC')
            ->get();
            }
        }

    $response = [
      "response" => 200,
      "total_record" => $total_record,
      'data' => $data,
    ];

    return response()->json($response, 200);
  }

           // get data by id


    function getDataById($id)
    {
        $data = DB::table("pathology_test")
            ->select(
                'pathology_test.*',
                'pathology_test_category.title as cat_title',
                'pathology_test_category.title as cat_title',
                'pathology_test_category.id as cat_id'
            )

           
            ->join('pathology_test_category', 'pathology_test.cat_id', '=', 'pathology_test_category.id')
            ->where('pathology_test.id', '=', $id)
            ->first();

            if($data){
              $data->pathology_subtest= DB::table("pathology_subtest")
            ->select('pathology_subtest.*', 'pathology_test.title as test_title')
            ->join('pathology_test', 'pathology_subtest.test_id', '=', 'pathology_test.id')
            ->where('pathology_subtest.test_id', $id)
            ->orderBy('pathology_subtest.created_at', 'DESC')
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
                    $dataModel= PathologyTestModel::where("id",$request->id)->first();
                    $oldImage = $dataModel->image;
                    $qResponce= $dataModel->delete();
                    if($qResponce){
                     
                        if(isset($oldImage)){
                            if($oldImage!="def.png"){
                                Helpers::deleteImage($oldImage);
                            }         
                        }
                    return Helpers::successResponse("successfully Deleted");}
                    else 
                    return Helpers::errorResponse("error");    
             
            }
        
         catch(\Exception $e){
                  
            return Helpers::errorResponse("This record cannot be deleted because it is linked to multiple data entries in the system. You can only deactivate it to prevent future use.");
                      }         
        
        }

}
