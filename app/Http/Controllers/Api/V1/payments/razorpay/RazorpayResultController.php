<?php

namespace App\Http\Controllers\Api\V1\payments\razorpay;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PreOrderModel;
use App\Models\PaymentGatewayModel;
use App\Models\OrderModel;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\V1\WebhookController;

class RazorpayResultController extends Controller
{
public function success(Request $request)
{
    $gateway = PaymentGatewayModel::where('title', 'Razorpay')
        ->firstOrFail();

    // 1. Verify Razorpay signature
    $expectedSignature = hash_hmac(
        'sha256',
        $request->razorpay_order_id . '|' . $request->razorpay_payment_id,
        $gateway->secret
    );

    if ($expectedSignature !== $request->razorpay_signature) {
        abort(403, 'Invalid payment');
    }

    //Log::info('Razorpay success controller HIT', $request->all());
    // 2. Call generic updatePayment
    app(WebhookController::class)->updatePayment(
        new Request([
            'pre_order_id' => $request->pre_order_id,
            'payment_transaction_id' => $request->razorpay_payment_id,
        ])
    );
 $preOrder = PreOrderModel::find($request->pre_order_id);

$rawPayload = $preOrder?->payload ?? [];

if (is_string($rawPayload)) {
    $decodedOnce = json_decode($rawPayload, true);

    if (is_string($decodedOnce)) {
        $payload = json_decode($decodedOnce, true) ?? [];
    } else {
        $payload = $decodedOnce ?? [];
    }
} else {
    $payload = $rawPayload ?? [];
}



    // 5️⃣ Redirect using payload callback URL
    if (!empty($payload['success_callback_url'])) { 
   return redirect()->away($payload['success_callback_url']);
    }

  return redirect()->route('payment.success.page');

}
   /**
     * PAYMENT FAILED / CANCELLED
     */
    public function failed(Request $request)
    {
        Log::warning('Razorpay FAILED hit', $request->all());

        app(WebhookController::class)->markPaymentFailed(
            new Request([
                'pre_order_id' => $request->pre_order_id,
                'reason' => 'User cancelled or payment failed',
            ])
        );

         $preOrder = PreOrderModel::find($request->pre_order_id);

$rawPayload = $preOrder?->payload ?? [];

if (is_string($rawPayload)) {
    $decodedOnce = json_decode($rawPayload, true);

    if (is_string($decodedOnce)) {
        $payload = json_decode($decodedOnce, true) ?? [];
    } else {
        $payload = $decodedOnce ?? [];
    }
} else {
    $payload = $rawPayload ?? [];
}



    // 5️⃣ Redirect using payload callback URL
    if (!empty($payload['failed_callback_url'])) { 
   return redirect()->away($payload['failed_callback_url']);
    }

        return redirect()->route('payment.failed.page');
    }

}
