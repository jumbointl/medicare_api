<?php

namespace App\Payments\Gateways;


use App\Models\PaymentGatewayModel;
use App\Models\PreOrderModel;
use App\Payments\Contracts\PaymentGatewayInterface;

class StripeGateway implements PaymentGatewayInterface
{
    protected $gateway;

    public function __construct(PaymentGatewayModel $gateway)
    {
        $this->gateway = $gateway;
    }

    public function initiate(PreOrderModel $preOrder): array
    {
        $payload = http_build_query([
            'mode' => 'payment',

            'success_url' => route('payment.stripe.success') . '?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'  => route('payment.stripe.failed') . '?session_id={CHECKOUT_SESSION_ID}',

            'billing_address_collection' => 'required',
            'customer_creation' => 'always',
        

            'line_items[0][price_data][currency]' => 'INR',
            'line_items[0][price_data][product_data][name]' => 'Pre Order #' . $preOrder->id,
            'line_items[0][price_data][unit_amount]' => (int) ($preOrder->pay_amount * 100),
            'line_items[0][quantity]' => 1,

            'metadata[pre_order_id]' => $preOrder->id,
        ]);

        $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');

        curl_setopt_array($ch, [
            CURLOPT_USERPWD => "{$this->gateway->secret}:",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $session = json_decode($response, true);

        if (!isset($session['id'], $session['url'])) {
            throw new \Exception('Stripe checkout session creation failed');
        }
         
    


        return [
            'transaction_id' => $session['payment_intent'],
            'payment_url' => route('payment.pay', [
                'gateway' => strtolower($this->gateway->title), // stripe
                'pre_order_id' => $preOrder->id,
                'ref' => $session['id'], // Stripe session ID
            ]),
        ];
    }
}
