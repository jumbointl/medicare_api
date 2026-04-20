<?php

namespace App\Http\Controllers\Api\V1\payments\esewa;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PreOrderModel;
use App\Models\PaymentGatewayModel;

class EsewaPayController extends Controller
{
    public function pay(Request $request)
    {
        $preOrder = PreOrderModel::where('id', $request->pre_order_id)
            ->where('payment_status', 'Pending')
            ->firstOrFail();

        $gateway = PaymentGatewayModel::where('title', 'eSewa')
            ->firstOrFail();

        return view('payments.esewa', [
            'amount' => $preOrder->pay_amount,
            'transaction_id' => $request->ref,
            'merchant_code' => $gateway->key, // eSewa SCD
            'success_url' => route('payment.esewa.success'),
            'failed_url' => route('payment.esewa.failed'),
        ]);
    }
}
