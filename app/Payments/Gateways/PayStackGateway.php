<?php

namespace App\Payments\Gateways;

use App\Models\PaymentGatewayModel;
use App\Models\PreOrderModel;
use App\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Log;

class PaystackGateway implements PaymentGatewayInterface
{
    protected $gateway;

    public function __construct(PaymentGatewayModel $gateway)
    {
        $this->gateway = $gateway;
    }

    public function initiate(PreOrderModel $preOrder): array
    {
        /**
         * Paystack requires:
         * - amount (in kobo)
         * - email (required)
         * - callback_url
         * - reference (unique)
         */

         $preOrder = PreOrderModel::find($preOrder->id);

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


        if (empty($payload['email'])) {
            throw new \Exception('Customer email is required for Paystack');
        }

        $reference = 'pre_order_' . $preOrder->id . '_' . time();

        $payload = [
            'email'        => $payload['email'],
            'amount'       => (int) ($preOrder->pay_amount * 100), // kobo
            'currency'     => $this->gateway->currency ?? 'NGN',
            'reference'    => $reference,
            'callback_url' => route('payment.paystack.callback'),
            'metadata'     => [
                'pre_order_id' => $preOrder->id,
            ],
        ];

        $ch = curl_init('https://api.paystack.co/transaction/initialize');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->gateway->secret,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if (
            !isset($result['status']) ||
            $result['status'] !== true ||
            !isset($result['data']['authorization_url'])
        ) {
            Log::error('Paystack init failed', [
                'response' => $result,
                'pre_order_id' => $preOrder->id,
            ]);

            throw new \Exception('Paystack payment initialization failed');
        }

        // Log::info('Paystack checkout initialized', $result);

        return [
            'transaction_id' => $reference,
            'payment_url'    => $result['data']['authorization_url'],
        ];
    }
}
