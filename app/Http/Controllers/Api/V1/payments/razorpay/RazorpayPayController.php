<?php

namespace App\Http\Controllers\Api\V1\payments\razorpay;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PreOrderModel;
use App\Models\PaymentGatewayModel;

class RazorpayPayController extends Controller
{
    public function pay(Request $request)
    {
        $preOrder = PreOrderModel::where('id', $request->pre_order_id)
            ->where('payment_status', 'Pending')
            ->firstOrFail();

        $gateway = PaymentGatewayModel::where('title', 'Razorpay')
            ->firstOrFail();

        return view('payments.razorpay', [
            'key'        => $gateway->key,
            'orderId'    => $request->ref,
            'amount'     => $preOrder->pay_amount,
            'preOrderId' => $preOrder->id,
        ]);
    }
}
