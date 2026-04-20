<?php


namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\RoleAssignModel;
use App\Models\PermissionModel;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{

    function getData()
{

  $data = DB::table("permission")
  ->select('permission.*',

  )
  ->orderBy('permission.group_id',"ASC")
    ->get();
  
        $response = [
            "response"=>200,
            'data'=>$data,
        ];
    
  return response($response, 200);
    }

    function getDataById($id)
    {

      $data = DB::table("permission")
      ->select('permission.*')
      ->where('id','=',$id)
        ->first();
      
            $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
        }
}

