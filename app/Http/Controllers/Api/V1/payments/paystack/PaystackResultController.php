<?php

namespace App\Http\Controllers\Api\V1\payments\paystack;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\PaymentGatewayModel;
use App\Models\PreOrderModel;
use App\Http\Controllers\Api\V1\WebhookController;

class PaystackResultController extends Controller
{
    public function callback(Request $request)
    {
        Log::info('Paystack callback hit', $request->all());

        $reference = $request->reference;

        if (!$reference) {
            abort(400, 'Missing payment reference');
        }

        $gateway = PaymentGatewayModel::where('title', 'Paystack')
            ->where('is_active', 1)
            ->firstOrFail();

        // 1️⃣ Verify transaction with Paystack
        $verifyResponse = Http::withToken($gateway->secret)
            ->get("https://api.paystack.co/transaction/verify/{$reference}");

        $data = $verifyResponse->json();

        if (
            !$data['status'] ||
            $data['data']['status'] !== 'success'
        ) {
            // ❌ Payment failed
            app(WebhookController::class)->markPaymentFailed(
                new Request([
                    'pre_order_id' => $data['data']['metadata']['pre_order_id'] ?? null,
                    'reason' => $data['data']['gateway_response'] ?? 'Payment failed',
                ])
            );
               $preOrder = PreOrderModel::find($data['data']['metadata']['pre_order_id']);

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

   if (!empty($payload['failed_callback_url'])) { 
   return redirect()->away($payload['failed_callback_url']);
    }
        

            return redirect()->route('payment.failed.page');
        }

        // ✅ Payment success
        app(WebhookController::class)->updatePayment(
            new Request([
                'pre_order_id' => $data['data']['metadata']['pre_order_id'],
                'payment_transaction_id' => $data['data']['reference'],
            ])
        );
        
     $preOrder = PreOrderModel::find($data['data']['metadata']['pre_order_id']);

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
}
