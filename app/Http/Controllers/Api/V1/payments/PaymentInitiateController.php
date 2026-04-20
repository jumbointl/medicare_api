<?php

namespace App\Http\Controllers\Api\V1\payments;
use App\Payments\PaymentManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentGatewayModel;
use App\Models\PreOrderModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
class PaymentInitiateController extends Controller
{

public function initiate(Request $request)
{
    $validator = Validator::make($request->all(), [
        'pre_order_id' => 'required|integer'
    ]);

    if ($validator->fails()) {
        return response (["response"=>400],400);
    }

    try {
        $preOrder = PreOrderModel::where('id', $request->pre_order_id)
            ->where('payment_status', 'Pending')
            ->first();

            if($preOrder == null){
               return Helpers::errorResponse("No pre order found");
            }


        $gateway = PaymentGatewayModel::where('is_active', 1)->firstOrFail();

        $result = PaymentManager::initiate($gateway, $preOrder);

        return response()->json([
            'response' => 200,
            'payment_url' => $result['payment_url'],
            'transaction_id' => $result['transaction_id'],
        ]);

    } catch (\Throwable $e) {
        // \Log::error('Payment initiate failed', ['error' => $e->getMessage()]);

      return Helpers::errorResponse("Something went wrong while initiating payment $e");
    }
}

}
