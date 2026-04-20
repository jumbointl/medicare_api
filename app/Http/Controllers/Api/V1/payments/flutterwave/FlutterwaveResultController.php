<?php

namespace App\Http\Controllers\Api\V1\payments\flutterwave;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\PaymentGatewayModel;
use App\Models\PreOrderModel;
use App\Http\Controllers\Api\V1\WebhookController;

class FlutterwaveResultController extends Controller
{
    /**
     * PAYMENT CALLBACK (SUCCESS / FAILED)
     */
    public function callback(Request $request)
    {
        Log::info('Flutterwave callback hit', $request->all());

        // If user cancelled or failed before payment
        if ($request->status !== 'successful' || !$request->transaction_id) {
            return $this->handleFailure($request);
        }

        $gateway = PaymentGatewayModel::where('title', 'Flutterwave')
            ->where('is_active', 1)
            ->firstOrFail();

        /**
         * 1️⃣ Verify transaction with Flutterwave
         */
        $verify = Http::withToken($gateway->secret)
            ->get("https://api.flutterwave.com/v3/transactions/{$request->transaction_id}/verify");

        $verifyData = $verify->json();

        if (
            !$verifyData['status'] ||
            $verifyData['data']['status'] !== 'successful'
        ) {
            return $this->handleFailure($request);
        }

        /**
         * 2️⃣ Extract pre_order_id
         */
        $preOrderId = $verifyData['data']['meta']['pre_order_id'] ?? null;

        if (!$preOrderId) {
            abort(400, 'Missing pre_order_id');
        }

        /**
         * 3️⃣ Call generic updatePayment
         */
        app(WebhookController::class)->updatePayment(
            new Request([
                'pre_order_id' => $preOrderId,
                'payment_transaction_id' => $verifyData['data']['tx_ref'],
            ])
        );

        /**
         * 4️⃣ Redirect using payload success_callback_url (if any)
         */
        return $this->redirectUsingPayload($preOrderId, true);
    }

    /**
     * HANDLE FAILURE / CANCEL
     */
    private function handleFailure(Request $request)
    {
        Log::warning('Flutterwave FAILED', $request->all());

        $preOrderId = null;

        // Try extracting from tx_ref
        if ($request->tx_ref) {
            $parts = explode('_', $request->tx_ref);
            $preOrderId = $parts[2] ?? null;
        }

        if ($preOrderId) {
            app(WebhookController::class)->markPaymentFailed(
                new Request([
                    'pre_order_id' => $preOrderId,
                    'reason' => 'Flutterwave payment failed or cancelled',
                ])
            );
        }

        return $this->redirectUsingPayload($preOrderId, false);
    }

    /**
     * REDIRECT USING PRE_ORDER PAYLOAD
     */
    private function redirectUsingPayload(?int $preOrderId, bool $isSuccess)
    {
        if (!$preOrderId) {
            return redirect()->route(
                $isSuccess ? 'payment.success.page' : 'payment.failed.page'
            );
        }

        $preOrder = PreOrderModel::find($preOrderId);

        $payload = $this->decodePayload($preOrder?->payload);

        if ($isSuccess && !empty($payload['success_callback_url'])) {
            return redirect()->away($payload['success_callback_url']);
        }

        if (!$isSuccess && !empty($payload['failed_callback_url'])) {
            return redirect()->away($payload['failed_callback_url']);
        }

        return redirect()->route(
            $isSuccess ? 'payment.success.page' : 'payment.failed.page'
        );
    }

    /**
     * SAFE PAYLOAD DECODER (handles double JSON encoding)
     */
    private function decodePayload($rawPayload): array
    {
        if (empty($rawPayload)) {
            return [];
        }

        if (is_string($rawPayload)) {
            $decodedOnce = json_decode($rawPayload, true);

            if (is_string($decodedOnce)) {
                return json_decode($decodedOnce, true) ?? [];
            }

            return $decodedOnce ?? [];
        }

        return is_array($rawPayload) ? $rawPayload : [];
    }
}
