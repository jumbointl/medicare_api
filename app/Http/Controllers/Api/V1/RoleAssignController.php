<?php


namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\RoleAssignModel;
use Illuminate\Support\Facades\DB;

class RoleAssignController extends Controller
{

    function addData(Request $request){
        $validator = Validator::make(request()->all(), [
            'role_id' => 'required',
            'user_id' => 'required'
      ]);
      if ($validator->fails())
      return response (["response"=>400],400);
     
      try{
        $timeStamp= date("Y-m-d H:i:s");
        DB::beginTransaction(); 
        $alreadyAddedModel= RoleAssignModel::where("user_id",$request->user_id)->first();
   
        if($alreadyAddedModel)
        {
         return Helpers::errorResponse("This role is already assigned. Please remove the current role before assigning a new one.");
        }
        $dataModel= new RoleAssignModel;
        $dataModel->role_id=$request->role_id;
        $dataModel->user_id=$request->user_id;
        $dataModel->created_at=$timeStamp;
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
        try{ 
                $dataModel= RoleAssignModel::where("id",$request->id)->first();
                $qResponce= $dataModel->delete();
                if($qResponce)
                return Helpers::successResponse("successfully Deleted");
                else 
                return Helpers::errorResponse("error");    
         
        }
    
     catch(\Exception $e){
              
                    return Helpers::errorResponse("error");
                  }
        
    
    
    
    }

    function getData()
{

  $data = DB::table("users_role_assign")
  ->select(
    'users_role_assign.*',
    'users.f_name',
    'users.l_name',
    'users.phone',
    'roles.name as role_name'  // Select the role name
)
->join("users", "users.id", "=", "users_role_assign.user_id")
->leftJoin("roles", "roles.id", "=", "users_role_assign.role_id")  // Left join with roles table
->get();
  
        $response = [
            "response"=>200,
            'data'=>$data,
        ];
    
  return response($response, 200);
    }

    function getDataById($id)
    {

      $data = DB::table("users_role_assign")
      ->select('users_role_assign.*')
      ->where('id','=',$id)
        ->first();
      
            $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
        }
}

