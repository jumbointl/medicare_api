<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AddressModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class SmtpController extends Controller
{
       //add new data
       function sendForgetMail(Request $request){
    
        $validator = Validator::make(request()->all(), [
         'email' => 'required|email'
      ]);
      if ($validator->fails())
      return response (["response"=>400],400);
    
      try{
        $userDetailsModel = User::where("email", $request->email)->first();
        if(!$userDetailsModel){
            return Helpers::errorResponse("No user found");
        }
        $password = Str::random(8); 
        $uName = ($userDetailsModel->f_name ?? "") . " " . ($userDetailsModel->l_name ?? "");
        $userDetailsModel->password = Hash::Make($password);
        $qResponce = $userDetailsModel->save();
        if( !$qResponce){ return Helpers::errorResponse("Error");}
         $data = [
            'userName' => $uName,
            'newPassword' => $password,
            'email' => $request->email
        ];

        Mail::send('emails.password_reset', $data, function ($message) use ($request) {
            $message->to($request->email)
                    ->subject('Your Password Has Been Reset');
        });

        return Helpers::successResponse("successfully");
        
           
        }

     catch(\Exception $e){
             DB::rollBack();
              
                    return Helpers::errorResponse("error");
                  }
}
}
