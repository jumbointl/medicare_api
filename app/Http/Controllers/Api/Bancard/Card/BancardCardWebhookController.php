<?php

namespace App\Http\Controllers\Api\Bancard\Card;

use App\Http\Controllers\Controller;
use App\Models\PaymentModel;
use App\Models\PaymentWebhookModel;
use App\Services\Bancard\Card\BancardCardPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BancardCardWebhookController extends Controller
{
    public function __construct(
        protected BancardCardPaymentService $paymentService,
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        $providerReference = $this->extractProviderReference($payload);
        $eventType = $this->extractEventType($payload);

        DB::beginTransaction();

        try {
            $webhook = new PaymentWebhookModel();
            $webhook->provider = 'bancard';
            $webhook->provider_reference = $providerReference;
            $webhook->event_type = $eventType;
            $webhook->payload_json = $payload;
            $webhook->processed = 0;
            $webhook->processed_at = null;
            $webhook->save();

            if (empty($providerReference)) {
                $webhook->processed = 1;
                $webhook->processed_at = now();
                $webhook->save();

                DB::commit();

                return response()->json([
                    'success' => false,
                    'message' => 'No se recibió provider_reference / shop_process_id.',
                ], 400);
            }

            /** @var PaymentModel|null $payment */
            $payment = PaymentModel::query()
                ->where('provider', 'bancard')
                ->where('provider_reference', (string) $providerReference)
                ->latest('id')
                ->first();

            if (!$payment) {
                $webhook->processed = 1;
                $webhook->processed_at = now();
                $webhook->save();

                DB::commit();

                return response()->json([
                    'success' => false,
                    'message' => 'Pago no encontrado para el provider_reference recibido.',
                    'provider_reference' => (string) $providerReference,
                ], 404);
            }

            $updatedPayment = $this->paymentService->markWebhookForAppointment(
                $payment,
                $payload
            );

            $webhook->processed = 1;
            $webhook->processed_at = now();
            $webhook->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Webhook procesado correctamente.',
                'data' => [
                    'payment_id' => $updatedPayment->id,
                    'provider_reference' => $updatedPayment->provider_reference,
                    'id_payment_status' => $updatedPayment->id_payment_status,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo procesar el webhook.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    protected function extractProviderReference(array $payload): ?string
    {
        return $payload['operation']['shop_process_id']
            ?? $payload['operation']['process_id']
            ?? $payload['shop_process_id']
            ?? $payload['process_id']
            ?? $payload['provider_reference']
            ?? null;
    }

    protected function extractEventType(array $payload): string
    {
        if (!empty($payload['operation']['response'])) {
            return 'operation_response';
        }

        if (!empty($payload['status'])) {
            return 'status_' . strtolower((string) $payload['status']);
        }

        return 'webhook';
    }
}