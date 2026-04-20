<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\RoleAssignModel;
use App\Models\PermissionModel;
use App\Models\RolePermissionModel;
use Illuminate\Support\Facades\DB;

class RolePermissionController extends Controller
{

    function addData(Request $request){
        $validator = Validator::make($request->all(), [
            'role_id' => 'required',
            'permission_ids' => 'required|string'
        ]);
    
        if ($validator->fails()) {
            return response(["response" => 400], 400);
        }
    
        try {
            $timeStamp = date("Y-m-d H:i:s");
            DB::beginTransaction();
    
            $permissionIds = explode(',', $request->permission_ids);
        
            $rolPermissionData=RolePermissionModel::where("role_id", $request->role_id);
            
            if($rolPermissionData->exists()){
                $resDelete=$rolPermissionData->delete();
                if(!$resDelete){
                    DB::rollBack();
                    return Helpers::errorResponse("Error");
                }
            }
        
    
            foreach ($permissionIds as $permissionId) {
                $permissionId = trim($permissionId); // Trim any whitespace
           
                $alreadyAddedModel = RolePermissionModel::where("role_id", $request->role_id)
                                                       ->where("permission_id", $permissionId)
                                                       ->first();
    
                if ($alreadyAddedModel) {
                    // Continue to the next permission if it's already added
                    continue;
                }
    
                $dataModel = new RolePermissionModel;
                $dataModel->role_id = $request->role_id;
                $dataModel->permission_id = $permissionId;
                $dataModel->created_at = $timeStamp;
                $dataModel->updated_at = $timeStamp;
    
                if (!$dataModel->save()) {
                    DB::rollBack();
                    return Helpers::errorResponse("Error");
                }
            }
    
            DB::commit();
            return Helpers::successResponse("Successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("Error    ");
        }
    }
function getDataByRoleId($id)
{

  $data = DB::table("role_permission")
  ->select('role_permission.*',
  'roles.name as role_name',
  'permission.name as permission_name',
    'permission.group_id'
  )
  ->join('roles','roles.id','role_permission.role_id')
  ->join('permission','permission.id','role_permission.permission_id')
  ->where('role_permission.role_id',$id)
   ->orderBy('permission.group_id',"ASC")
    ->get();
  
        $response = [
            "response"=>200,
            'data'=>$data,
        ];

  return response($response, 200);

    }

    function getData()
{

  $data = DB::table("role_permission")
  ->select('role_permission.*',
  'roles.name as role_name',
  'permission.name as permission_name'

  )
  ->join('roles','roles.id','role_permission.role_id')
  ->join('permission','permission.id','role_permission.permission_id')
   ->orderBy('role_permission.created_at',"DESC")
    ->get();
  
        $response = [
            "response"=>200,
            'data'=>$data,
        ];
    
  return response($response, 200);
    }

    function getDataById($id)
    {

      $data = DB::table("role_permission")
      ->select('role_permission.*',
      'roles.name as role_name',
      'permission.name as permission_name'
    
      )
      ->join('roles','roles.id','role_permission.role_id')
      ->join('permission','permission.id','role_permission.permission_id')
      ->where('role_permission.id','=',$id)
        ->first();
      
            $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
        }
}

