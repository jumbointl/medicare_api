<?php

namespace App\Payments\Gateways;

use App\Models\PaymentGatewayModel;
use App\Models\PreOrderModel;
use App\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlutterwaveGateway implements PaymentGatewayInterface
{
    protected $gateway;

    public function __construct(PaymentGatewayModel $gateway)
    {
        $this->gateway = $gateway;
    }

    public function initiate(PreOrderModel $preOrder): array
    {
        $reference = 'pre_order_' . $preOrder->id . '_' . time();

        $payload = [
            'tx_ref' => $reference,
            'amount' => (float) 100,
            'currency' => "NGN",
            'redirect_url' => route('payment.flutterwave.callback'),
            'customer' => [
                'email' => "appwebdevash@gmail.com",
                'name' => "Ashish",
            ],
            'meta' => [
                'pre_order_id' => $preOrder->id,
            ],
            'customizations' => [
                'title' => 'Order Payment',
                'description' => 'Pre Order #' . $preOrder->id,
            ],
        ];

        // Only for temporary debugging: set FLW_DISABLE_SSL_VERIFY=true in your .env
        $disableVerify = env('FLW_DISABLE_SSL_VERIFY', false);

        try {
            $response = Http::withToken($this->gateway->secret)
                ->withHeaders(['Accept' => 'application/json'])
                ->timeout(30)
                ->withOptions(['verify' => !$disableVerify ? true : false])
                ->post('https://api.flutterwave.com/v3/payments', $payload);
        } catch (\Exception $e) {
            // This catches connection/TLS/cURL-level errors
            Log::error('Flutterwave HTTP EXCEPTION', [
                'message' => $e->getMessage(),
                'payload' => $payload,
                'gateway_id' => $this->gateway->id ?? null,
            ]);

            throw new \Exception('Flutterwave payment initialization failed: ' . $e->getMessage());
        }

        if (!$response->successful()) {
            Log::error('Flutterwave HTTP ERROR', [
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $payload,
                'gateway_id' => $this->gateway->id ?? null,
            ]);

            throw new \Exception('Flutterwave payment initialization failed (non-success response).');
        }

        $result = $response->json();

        if (
            !isset($result['status']) ||
            $result['status'] !== 'success' ||
            empty($result['data']['link'])
        ) {
            Log::error('Flutterwave INIT ERROR', [
                'response' => $result,
                'payload' => $payload,
                'gateway_id' => $this->gateway->id ?? null,
            ]);

            throw new \Exception(
                $result['message'] ?? 'Flutterwave payment initialization failed'
            );
        }

        return [
            'transaction_id' => $reference,
            'payment_url' => $result['data']['link'],
        ];
    }
}