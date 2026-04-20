<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PathologyTestSubCategoryModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PathologyTestSubController extends Controller
{
    //add new data
    function addData(Request $request){

    
        $validator = Validator::make(request()->all(), [
            'title' => 'required',
           'category_id' => 'required'
      ]);
      if ($validator->fails())
      return response (["response"=>400],400);
     
      try{
            $alreadyAddedModel= PathologyTestSubCategoryModel::where("title",$request->title)->first();
            if($alreadyAddedModel)
            {
                return Helpers::errorResponse("title already exists");
            
            }else{
                DB::beginTransaction();

                $timeStamp= date("Y-m-d H:i:s");
                $dataModel=new PathologyTestSubCategoryModel;
                
                $dataModel->title = $request->title ;
                $dataModel->category_id = $request->category_id ;
                $dataModel->created_at=$timeStamp;
                $dataModel->updated_at=$timeStamp;
                if(isset($request->image)){
        
                      $dataModel->image =  $request->hasFile('image') ? Helpers::uploadImage('pathology_test_sub_Category/', $request->file('image')) : null;
                }
                
                if(isset($request->description)){
                  $dataModel->description  = $request->description;
                }
               
                $qResponce = $dataModel->save();
                if($qResponce)
               {
                DB::commit();
                
                return Helpers::successWithIdResponse("successfully",$dataModel->id);}
            
                else 
                {   DB::rollBack();
                    
                    return Helpers::errorResponse("error");}
            }
           
        }

     catch(\Exception $e){
             DB::rollBack();
              
                    return Helpers::errorResponse("error");
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
        $dataModel= PathologyTestSubCategoryModel::where("id",$request->id)->first();
        if(isset($request->title)){
            $alreadyExists = PathologyTestSubCategoryModel::where('title', '=', $request->title)->where('id',"!=",$request->id)->first();
            if ($alreadyExists != null)
            {
                return Helpers::errorResponse("title already exists");
            }
            else{
                $dataModel->title= $request->title;
            }
        }
        if(isset($request->description)){
            $dataModel->description= $request->description;
        }
        if(isset($request->active)){
            $dataModel->active= $request->active;
        }
       if(isset($request->category_id)){
            $dataModel->category_id= $request->category_id;
        }
        
      
        if(isset($request->image)){
            if($request->hasFile('image') ){

            $oldImage = $dataModel->image;
            $dataModel->image =  Helpers::uploadImage('pathology_test_sub_category/', $request->file('image'));
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
                return Helpers::errorResponse("error");
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
        $dataModel= PathologyTestSubCategoryModel::where("id",$request->id)->first();
  

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
          
                return Helpers::errorResponse("error");
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


    $query = DB::table("pathology_test_sub_category")
      ->select('pathology_test_sub_category.*',
       'pathology_test_category.title as category_title',
       'pathology_test_category.description as category_description',
        'pathology_test_category.image as category_image',
      )
      ->join('pathology_test_category', 'pathology_test_sub_category.category_id', '=', 'pathology_test_category.id')
      ->orderBy('pathology_test_sub_category.created_at', 'DESC');

    if ($request->filled('active')) {
      $query->where('pathology_test_sub_category.active', '=', $request->active);
    }


    if ($request->has('search')) {
      $search = $request->input('search');
      $query->where(function ($q) use ($search) {
        $q->where('pathology_test_sub_category.title', 'like', '%' . $search . '%');
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


        function getDataActive()
        {
    
          $data = DB::table("pathology_test_sub_category")
          ->select('pathology_test_sub_category.*')
          ->where('active',1)
            ->get();
          
                $response = [
                    "response"=>200,
                    'data'=>$data,
                ];
            
          return response($response, 200);
            }
    

        

           // get data by id

    function getDataById($id)
    {
      $data = DB::table("pathology_test_sub_category")
      ->select('pathology_test_sub_category.*',
       'pathology_test_category.title as category_title',
       'pathology_test_category.description as category_description',
       'pathology_test_category.image as category_image',
      )
    ->join('pathology_test_category', 'pathology_test_sub_category.category_id', '=', 'pathology_test_category.id')
    
      ->where('pathology_test_sub_category.id','=',$id)
        ->first();
      
            $response = [
                "response"=>200,
                'data'=>$data,
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
                    $dataModel= PathologyTestSubCategoryModel::where("id",$request->id)->first();
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
