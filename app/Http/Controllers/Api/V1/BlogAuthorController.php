<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BlogAuthorModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogAuthorController extends Controller
{
    //add new data
    function addData(Request $request){

    
        $validator = Validator::make(request()->all(), [
            'user_id' => 'required|string|max:255',
            'blog_id' => 'required',
            'role' => 'required'
      ]);
      if ($validator->fails())
      return response (["response"=>400],400);
     
      try{
            $alreadyAddedModel= BlogAuthorModel::where("user_id",$request->user_id)->where("blog_id",$request->blog_id)->first();
            if($alreadyAddedModel)
            {
                return Helpers::errorResponse("Already exists");
            
            }else{
                DB::beginTransaction();

                $timeStamp= date("Y-m-d H:i:s");
                $dataModel=new BlogAuthorModel;
                
                $dataModel->user_id = $request->user_id ;
                $dataModel->blog_id = $request->blog_id;
                $dataModel->notes = $request->notes ;
                    $dataModel->role = $request->role ;
                    
               
                $dataModel->created_at=$timeStamp;
                $dataModel->updated_at=$timeStamp;
                // if(isset($request->image)){
        
                //       $dataModel->image =  $request->hasFile('image') ? Helpers::uploadImage('department/', $request->file('image')) : null;
                // }
                
             
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
        DB::beginTransaction();
        $dataModel= BlogAuthorModel::where("id",$request->id)->first();
    

                 if(isset($request->notes)){ $dataModel->notes = $request->notes ;}
                       if(isset($request->role)){ $dataModel->role = $request->role ;}


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


//  // Remove Image
// function removeImage(Request $request){


//     $validator = Validator::make(request()->all(), [
//         'id' => 'required'
//   ]);
//   if ($validator->fails())
//   return response (["response"=>400],400);
//     try{
//         $dataModel= BlogPostModel::where("id",$request->id)->first();
  

//             $oldImage = $dataModel->image;
//             if(isset($oldImage)){
//                 if($oldImage!="def.png"){
//                     Helpers::deleteImage($oldImage);
//                 }

//                 $dataModel->image=null;
//             }
 
//             $timeStamp= date("Y-m-d H:i:s");
//             $dataModel->updated_at=$timeStamp;
            
//                 $qResponce= $dataModel->save();
//                 if($qResponce)
//                 return Helpers::successResponse("successfully");
    
//                 else 
//                 return Helpers::errorResponse("error");
//     }
    

//  catch(\Exception $e){
          
//                 return Helpers::errorResponse("error");
//               }
//             }

            
    
    public function getData(Request $request)
    {
        // Define the base query
        $query = DB::table("blog_author")
            ->select(
                'blog_author.*',
                "users.f_name",
                "users.l_name",
                 "users.image",
                 'doctors.specialization'
                )
                        ->join('users','users.id','=','blog_author.user_id')
                                    ->leftJoin('doctors','doctors.user_id','=','blog_author.user_id')
            ->orderBy("blog_author.created_at", "DESC");

        // Apply filters (like search)
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('blog_author.notes', 'like', '%' . $search . '%');
            });
        }
    
        if ($request->filled('blog_id')) {
            $query->where('blog_author.blog_id', $request->blog_id);
            
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

        $data = DB::table("blog_author")
            ->select(
                'blog_author.*',
            "users.f_name",
            "users.l_name",
            "users.image",
           'doctors.specialization'

                )
                ->join('users','users.id','=','blog_author.user_id')
                ->leftJoin('doctors','doctors.user_id','=','blog_author.user_id')
      ->where('blog_author.id','=',$id)
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
                    $dataModel=BlogAuthorModel ::where("id",$request->id)->first();
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
