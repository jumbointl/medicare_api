<?php

namespace App\Http\Controllers\Api\V1\payments\esewa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\PreOrderModel;
use App\Http\Controllers\Api\V1\WebhookController;

class EsewaResultController extends Controller
{
    /**
     * PAYMENT SUCCESS
     */
    public function success(Request $request)
    {
        /**
         * eSewa returns:
         * oid   => our transaction id (pid)
         * amt   => amount
         * refId => eSewa reference id
         */

        Log::info('eSewa SUCCESS hit', $request->all());

        if (!$request->oid || !$request->refId) {
            abort(400, 'Invalid eSewa response');
        }

        $preOrderId = $this->extractPreOrderId($request->oid);

        /**
         * 1️⃣ Call generic updatePayment
         */
        app(WebhookController::class)->updatePayment(
            new Request([
                'pre_order_id' => $preOrderId,
                'payment_transaction_id' => $request->refId, // eSewa refId
            ])
        );

        /**
         * 2️⃣ Load pre-order payload (same logic as Stripe)
         */
        $preOrder = PreOrderModel::find($preOrderId);

        $payload = $this->decodePayload($preOrder?->payload);

        /**
         * 3️⃣ Redirect using payload callback URL (if provided)
         */
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
        Log::warning('eSewa FAILED hit', $request->all());

        if (!$request->oid) {
            return redirect()->route('payment.failed.page');
        }

        $preOrderId = $this->extractPreOrderId($request->oid);

        /**
         * 1️⃣ Mark payment failed
         */
        app(WebhookController::class)->markPaymentFailed(
            new Request([
                'pre_order_id' => $preOrderId,
                'reason' => 'eSewa payment failed or cancelled',
            ])
        );

        /**
         * 2️⃣ Load payload for redirect
         */
        $preOrder = PreOrderModel::find($preOrderId);

        $payload = $this->decodePayload($preOrder?->payload);

        /**
         * 3️⃣ Redirect using payload callback URL (if provided)
         */
        if (!empty($payload['failed_callback_url'])) {
            return redirect()->away($payload['failed_callback_url']);
        }

        return redirect()->route('payment.failed.page');
    }

    /**
     * Extract pre_order_id from transaction id
     * Example oid: pre_order_203_1769522998
     */
    private function extractPreOrderId(string $oid): int
    {
        $parts = explode('_', $oid);

        return (int) ($parts[2] ?? 0);
    }

    /**
     * Decode payload safely (handles double-encoded JSON)
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
