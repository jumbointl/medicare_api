<?php

namespace App\Http\Controllers\Api\V1\payments\stripe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PreOrderModel;
use App\Models\PaymentGatewayModel;

class StripePayController extends Controller
{
    public function pay(Request $request)
    {
        $preOrder = PreOrderModel::where('id', $request->pre_order_id)
            ->where('payment_status', 'Pending')
            ->firstOrFail();

        $gateway = PaymentGatewayModel::where('title', 'Stripe')
            ->firstOrFail();

        $sessionId = $request->ref;

        /**
         * Fetch Checkout Session from Stripe
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

        if (!isset($session['url'])) {
            abort(404, 'Stripe checkout session not found');
        }

        return redirect()->away($session['url']);
    }
}
