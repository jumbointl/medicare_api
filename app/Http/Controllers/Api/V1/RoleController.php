<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\RoleModel;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{

    function addData(Request $request){
        $validator = Validator::make(request()->all(), [
            'role_name' => 'required'
      ]);
      if ($validator->fails())
      return response (["response"=>400],400);
     
      try{
        $timeStamp= date("Y-m-d H:i:s");
        DB::beginTransaction(); 
        $alreadyAddedModel= RoleModel::where("name",$request->role_name)->first();
   
        if($alreadyAddedModel)
        {
         return Helpers::errorResponse("Role name already exists");
        }
        $dataModel= new RoleModel;
        $dataModel->name=$request->role_name;
        $dataModel->created_at=$timeStamp;
        $dataModel->updated_at=$timeStamp;
        $dataModel->is_super_admin_role=$request->is_super_admin_role??0; 
        $res=$dataModel->save();
        if(!$res){
            DB::rollBack();
            return Helpers::errorResponse("error");
        }
        DB::commit();
        return Helpers::successWithIdResponse("successfully",$dataModel->id);
}    catch(\Exception $e){
    DB::rollBack();
    return Helpers::errorResponse("error");
  }
}

function updateData(Request $request){
    $validator = Validator::make(request()->all(), [
        'id' => 'required'
  ]);
  if ($validator->fails())
  return response (["response"=>400],400);
  if($request->id==14||$request->id==16||$request->id==18||$request->id==21||$request->id==32){
    return Helpers::successResponse("You can't update the Subper Admin, Clinic,  Doctor,Front Desk, and Pathologist roles.");
  }
  try{
    $timeStamp= date("Y-m-d H:i:s");
    DB::beginTransaction(); 
    
    $alreadyAddedModel= RoleModel::where("name",$request->role_name)->where('id',"!=",$request->id)->first();

    if($alreadyAddedModel)
    {
     return Helpers::errorResponse("Role name already exists");
    }

    $dataModel= RoleModel::where("id",$request->id)->first();

    if($dataModel->name=="Admin"){   return Helpers::errorResponse("Cann't update admin role");}
    if(isset($request->role_name)){ 
        $dataModel->name=$request->role_name;
      
    }
    if(isset($request->is_super_admin_role)){ 
      $dataModel->is_super_admin_role=$request->is_super_admin_role??0; 
    
  }
    
     $dataModel->updated_at=$timeStamp;
    $res=$dataModel->save();
    if(!$res){
        DB::rollBack();
        return Helpers::errorResponse("error");
    }
    DB::commit();
    return Helpers::successWithIdResponse("successfully",$dataModel->id);
}    catch(\Exception $e){
DB::rollBack();
return Helpers::errorResponse("error");
}
}

function deleteData(Request $request){
    $validator = Validator::make(request()->all(), [
        'id' => 'required'
  ]);
  if ($validator->fails())
  return response (["response"=>400],400);
if($request->id==14||$request->id==16||$request->id==18||$request->id==21||$request->id==32){
  return Helpers::successResponse("You can't delete the Subper Admin, Clinic, Doctor, Front Desk, and Pathologist roles.");
}
    try{ 
            $dataModel= RoleModel::where("id",$request->id)->first();
            $qResponce= $dataModel->delete();
            if($qResponce)
            return Helpers::successResponse("successfully Deleted");
            else 
            return Helpers::errorResponse("error");    
     
    }

 catch(\Exception $e){
          
                return Helpers::errorResponse("error $e");
              }
    



}

function getData()
{

  $data = DB::table("roles")
  ->select('roles.*' )
    ->get();
  
        $response = [
            "response"=>200,
            'data'=>$data,
        ];
    
  return response($response, 200);
    }

    function getDataById($id)
    {

      $data = DB::table("roles")
      ->select('roles.*')
      ->where('id','=',$id)
        ->first();
      
            $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
        }
    }

