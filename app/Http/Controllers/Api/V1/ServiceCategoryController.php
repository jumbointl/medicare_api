<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServiceCategoryModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ServiceCategoryController extends Controller
{
    //add new data
    function addData(Request $request){

    
        $validator = Validator::make(request()->all(), [
              'title' => 'required',
              'clinic_id' => 'required'
    
      ]);
      if ($validator->fails())
      return response (["response"=>400],400);
     
      try{
         $alreadyAddedModel= ServiceCategoryModel::where("clinic_id",$request->clinic_id)->where("title",$request->title)->first();
                   if($alreadyAddedModel)
                   {
                       return Helpers::errorResponse("Title already exists");
                   }
          
       
                DB::beginTransaction();

                $timeStamp= date("Y-m-d H:i:s");
                $dataModel=new ServiceCategoryModel;

                $dataModel->title = $request->title ;

                  $dataModel->clinic_id = $request->clinic_id ;

                $dataModel->created_at=$timeStamp;
                $dataModel->updated_at=$timeStamp;
                   $qResponce = $dataModel->save();
       
               
             
                if($qResponce)
               {
                DB::commit();
                
                return Helpers::successWithIdResponse("successfully",$dataModel->id);
            }
            
                else 
                {   DB::rollBack();
                    
                    return Helpers::errorResponse("error");}
           
        }

     catch(\Exception $e){
             DB::rollBack();
              
                    return Helpers::errorResponse("error $e");
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

            $dataModel= ServiceCategoryModel::where("id",$request->id)->first();
           

        DB::beginTransaction();
    
        if(isset($request->title)){
        
             
                   $alreadyAddedModel= ServiceCategoryModel::where("clinic_id",$dataModel->clinic_id)->where("title",$dataModel->title)->where('id',"!=",$request->id)->first();
                   if($alreadyAddedModel)
                   {
                       return Helpers::errorResponse("Title already exists");
                   }
               
               
               $dataModel->title= $request->title;
        }
  
          if(isset($request->active)){
            $dataModel->active= $request->active;
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

               // get data


      public function getData(Request $request)
    {
        // Define the base query
        $query = DB::table("service_category")
            ->select(
                'service_category.*',
                'clinics.title as clinic_title',
     
            )
            ->Leftjoin('clinics', 'clinics.id', '=', 'service_category.clinic_id')
            ->orderBy("service_category.created_at", "DESC");

        // Apply filters (like search)
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('service_category.title', 'like', '%' . $search . '%')
                    ->orWhere('clinics.title', 'like', '%' . $search . '%');
                    
                 
            });
        }
                if($request->has('clinic_id')){
                    $query->where('service_category.clinic_id','=',$request->clinic_id);
                }

                   if($request->has('active')){
                    $query->where('service_category.active','=',$request->active);
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

        // Return the response with total records and data
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

      $data = DB::table("service_category")
      ->select(   
          'service_category.*',
                'clinics.title as clinic_title'
         
      )
      ->Leftjoin('clinics', 'clinics.id', '=', 'service_category.clinic_id')
          
      ->where('service_category.id','=',$id)
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
                    $dataModel= ServiceCategoryModel::where("id",$request->id)->first();
                    $qResponce= $dataModel->delete();
                    if($qResponce){
                     
                   
                    return Helpers::successResponse("successfully Deleted");}
                    else 
                    return Helpers::errorResponse("error");    
             
            }
        
         catch(\Exception $e){
                  
            return Helpers::errorResponse("This record cannot be deleted because it is linked to multiple data entries in the system. You can only deactivate it to prevent future use.");
                      }         
        
        }

}
