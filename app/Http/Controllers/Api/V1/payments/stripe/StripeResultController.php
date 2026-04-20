<?php

namespace App\Http\Controllers\Api\V1\payments\stripe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PreOrderModel;
use Illuminate\Support\Facades\Log;
use App\Models\PaymentGatewayModel;
use App\Http\Controllers\Api\V1\WebhookController;

class StripeResultController extends Controller
{
    /**
     * PAYMENT SUCCESSx
     */
    public function success(Request $request)
    {
        $gateway = PaymentGatewayModel::where('title', 'Stripe')
            ->firstOrFail();

        $sessionId = $request->get('session_id');

        if (! $sessionId) {
            abort(400, 'Missing Stripe session ID');
        }

        /**
         * 1. Fetch Checkout Session from Stripe (server-side verification)
         */
        $ch = curl_init("https://api.stripe.com/v1/checkout/sessions/{$sessionId}");

        curl_setopt_array($ch, [
            CURLOPT_USERPWD => "{$gateway->secret}:",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $session = json_decode($response, true);

        /**
         * 2. Validate payment
         */
        if (
            !isset($session['payment_status']) ||
            $session['payment_status'] !== 'paid'
        ) {
            abort(403, 'Stripe payment not completed');
        }

        if (!isset($session['metadata']['pre_order_id'])) {
            abort(400, 'Missing pre_order_id in Stripe metadata');
        }

        /**
         * 3. Call generic updatePayment (same as Razorpay)
         */
        app(WebhookController::class)->updatePayment(
            new Request([
                'pre_order_id' => $session['metadata']['pre_order_id'],
                'payment_transaction_id' => $session['payment_intent'] ?? $sessionId,
            ])
        );

         $preOrder = PreOrderModel::find($session['metadata']['pre_order_id']);

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
    Log::warning('Stripe FAILED hit', $request->all());

    $sessionId = $request->get('session_id');

    if ($sessionId) {
        $gateway = PaymentGatewayModel::where('title', 'Stripe')
            ->firstOrFail();

        // Fetch session from Stripe
        $ch = curl_init("https://api.stripe.com/v1/checkout/sessions/{$sessionId}");

        curl_setopt_array($ch, [
            CURLOPT_USERPWD => "{$gateway->secret}:",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $session = json_decode($response, true);

        if (isset($session['metadata']['pre_order_id'])) {
            app(WebhookController::class)->markPaymentFailed(
                new Request([
                    'pre_order_id' => $session['metadata']['pre_order_id'],
                    'reason' => 'Stripe payment cancelled by user',
                ])
            );
        }
    }
             $preOrder = PreOrderModel::find($session['metadata']['pre_order_id']);

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
