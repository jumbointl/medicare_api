<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FamilyMembersModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;

class FamilyMembersController extends Controller
{
        //add new data
        function addData(Request $request){

            $validator = Validator::make(request()->all(), [
                'f_name' => 'required',
                'l_name' => 'required',
                'user_id' => 'required',
          ]);
          if ($validator->fails())
          return response (["response"=>400],400);
         
          try{

            $dataFamilyMemberExists = FamilyMembersModel::where('f_name', $request->f_name)
            ->where('l_name', $request->l_name)
            ->where('phone', $request->phone)
            ->first(); 
            if($dataFamilyMemberExists){
              return Helpers::errorResponse("Member already exists");
            }
              
                    $timeStamp= date("Y-m-d H:i:s");
                    $dataModel=new FamilyMembersModel;
                    
                    $dataModel->f_name = $request->f_name ;
                    $dataModel->l_name = $request->l_name ;
                    $dataModel->user_id = $request->user_id ;

                    if(isset($request->isd_code)){ $dataModel->isd_code = $request->isd_code ;}
                    if(isset($request->phone)){ $dataModel->phone = $request->phone;}
                    if(isset($request->gender)){ $dataModel->gender = $request->gender ;}
                    if(isset($request->dob)){ $dataModel->dob = $request->dob ;}
                  
                    
                //     if(isset($request->image)){
        
                //         $dataModel->image =  $request->hasFile('image') ? Helpers::uploadImage('patients/', $request->file('image')) : null;
                //   }     
                  
                
                    $dataModel->created_at=$timeStamp;
                    $dataModel->updated_at=$timeStamp;
                   
                    $qResponce = $dataModel->save();
                    if($qResponce)
                   {
              
                    
                    return Helpers::successWithIdResponse("successfully",$dataModel->id);}
                
                    else 
                    { 
                        
                        return Helpers::errorResponse("error");}
                
            }
    
         catch(\Exception $e){
               
                
                        return Helpers::errorResponse("error");
                      }
    }

    // Update data
function updateData(Request $request){


    $validator = Validator::make(request()->all(), [
        'id' => 'required'
  ]);
  if ($validator->fails())
  return response (["response"=>400],400);
    try{
        $dataModel= FamilyMembersModel::where("id",$request->id)->first();

        if(isset($request->f_name)){ $dataModel->f_name = $request->f_name;}
        if(isset($request->l_name)){ $dataModel->l_name = $request->l_name;}
        if(isset($request->dob)){ $dataModel->dob = $request->dob ;}
        if(isset($request->isd_code)){ $dataModel->isd_code = $request->isd_code ;}
        if(isset($request->phone)){ $dataModel->phone = $request->phone;}
        if(isset($request->gender)){ $dataModel->gender = $request->gender ;}
        if(isset($request->dob)){ $dataModel->dob = $request->dob ;}

        $timeStamp= date("Y-m-d H:i:s");

        $dataModel->updated_at=$timeStamp;
                $qResponce= $dataModel->save();
                if($qResponce)
                {
              
                    return Helpers::successResponse("successfully");
                }
    
                else 
                {
               
                    return Helpers::errorResponse("error");
                }
    }
    

 catch(\Exception $e){
                return Helpers::errorResponse("error");
              }
            }

            
            //Delete Data
        function deleteData(Request $request){

            $validator = Validator::make(request()->all(), [
                'id' => 'required'
          ]);
          if ($validator->fails())
          return response (["response"=>400],400);
            try{
        
                $dataModel= FamilyMembersModel::where("id",$request->id)->first();

                        $qResponce= $dataModel->delete();
                        if($qResponce)
                        {
                      
                            return Helpers::successResponse("successfully");}
            
                        else 
                        {
                       
                            return Helpers::errorResponse("error");}
            }
            
        
         catch(\Exception $e){
                        return Helpers::errorResponse("error");
                      }
    }

    // get data by user id

    function getDataByUserId($id)
    {

      $data = DB::table("family_members")
      ->select('family_members.*')
      ->where('family_members.user_id',$id)
      ->orderBy("family_members.created_at","DESC")
        ->get();
      
            $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
        }
            // get data

    function getData()
    {

      $data = DB::table("family_members")
      ->select('family_members.*',
      "users.f_name as parent_f_name",
      "users.l_name as parent_l_name",
         "users.phone as parent_phone"
      )
      ->join("users","users.id","=","family_members.user_id")
      ->orderBy("family_members.created_at","DESC")
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

      $data = DB::table("family_members")
      ->select('family_members.*')
      ->where('id','=',$id)
        ->first();
      
            $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
        }
          public function getDataPeg(Request $request)
  {
  
      // Calculate the limit
      $start =$request->start;
      $end =$request->end;
      $limit = ($end - $start);
  
      // Define the base query
  
      $query = DB::table("family_members")
      ->select('family_members.*',
      "users.f_name as user_f_name",
      "users.l_name as user_l_name",
         "users.phone as user_phone"
      )
      ->join("users","users.id","=","family_members.user_id")
      ->orderBy('family_members.created_at', 'DESC');
      if ($request->has('search')) {
          $search = $request->input('search');
          $query->where(function ($q) use ($search) {
              $q->where(DB::raw('CONCAT(family_members.f_name, " ", family_members.l_name)'), 'like', '%' . $search . '%')
              ->orWhere('family_members.id', 'like', '%' . $search . '%')  
              ->orWhere('family_members.phone', 'like', '%' . $search . '%')  
              ->orWhere('family_members.gender', 'like', '%' . $search . '%')  
              ->orWhere('family_members.dob', 'like', '%' . $search . '%');
             
          });
      }
   
      $total_record= $query->get()->count();
      $data = $query->skip($start)->take($limit)->get();
      
  
      $response = [
          "response" => 200,
          "total_record"=>$total_record,
          'data' => $data,
      ];
  
      return response()->json($response, 200);
  }

     
}
