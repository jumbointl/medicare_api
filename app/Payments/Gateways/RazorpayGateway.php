<?php

namespace App\Payments\Gateways;

use App\Models\PaymentGatewayModel;
use App\Models\PreOrderModel;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\Log;
class RazorpayGateway implements PaymentGatewayInterface
{
    protected $gateway;

    public function __construct(PaymentGatewayModel $gateway)
    {
        $this->gateway = $gateway;
    }

    public function initiate(PreOrderModel $preOrder): array
    {
        $payload = [
            'amount' => (int) ($preOrder->pay_amount * 100),
            'currency' => 'INR',
            'receipt' => 'pre_order_' . $preOrder->id,
            'notes' => [
                'pre_order_id' => $preOrder->id
            ]
        ];

        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt_array($ch, [
            CURLOPT_USERPWD => "{$this->gateway->key}:{$this->gateway->secret}",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $order = json_decode($response, true);

        if (!isset($order['id'])) {
            throw new \Exception('Razorpay order creation failed');
        }

   // Log::info('RazorPay checkout session created', $order);
       return [
            'transaction_id' => $order['id'],
            'payment_url' => route('payment.pay', [
                'gateway' => strtolower($this->gateway->title), // razorpay, esewa, paypal
                'pre_order_id' => $preOrder->id,
                'ref' => $order['id'], // gateway reference (order_id / token / session)
            ])
        ];
    }
}
