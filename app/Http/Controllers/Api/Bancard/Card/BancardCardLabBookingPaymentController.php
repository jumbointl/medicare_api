<?php

namespace App\Http\Controllers\Api\Bancard\Card;

use App\Http\Controllers\Controller;
use App\Models\LabBookingModel;
use App\Models\PaymentModel;
use App\Services\Bancard\Card\BancardCardClient;
use App\Services\Bancard\Card\BancardCardLabBookingPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BancardCardLabBookingPaymentController extends Controller
{
    public function __construct(
        protected BancardCardLabBookingPaymentService $paymentService,
        protected BancardCardClient $client,
    ) {
    }

    public function startLabBookingPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lab_booking_id' => ['required', 'integer', 'exists:lab_booking,id'],
            'id_user' => ['required', 'integer', 'exists:users,id'],
            'id_payment_type' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'max:10'],
            'description' => ['nullable', 'string', 'max:255'],
            'identification' => ['nullable', 'string', 'max:255'],
            'additional_data' => ['nullable', 'string', 'max:255'],
            'promotion_code' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:30'],
        ]);

        try {
            $result = $this->paymentService->startLabBookingPayment($validated);

            return response()->json([
                'success' => true,
                'message' => 'Pago iniciado correctamente.',
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'No se pudo iniciar el pago.',
            ], 500);
        }
    }

    public function findCurrentByLabBooking(LabBookingModel $labBooking): JsonResponse
    {
        $payment = PaymentModel::query()
            ->where('id_lab_booking', $labBooking->id)
            ->latest('id')
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró pago para esta reserva de laboratorio.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pago obtenido correctamente.',
            'data' => $payment,
        ]);
    }

    public function confirmLabBookingPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_id' => ['required', 'integer', 'exists:payments,id'],
        ]);

        /** @var PaymentModel $payment */
        $payment = PaymentModel::query()->findOrFail($validated['payment_id']);

        if ($payment->provider !== 'bancard') {
            return response()->json([
                'success' => false,
                'message' => 'El pago no pertenece a Bancard.',
            ], 400);
        }

        if (!$payment->provider_reference) {
            return response()->json([
                'success' => false,
                'message' => 'El pago no tiene provider_reference.',
            ], 400);
        }

        try {
            $confirmationResponse = $this->client->confirmation($payment->provider_reference);
            $updatedPayment = $this->paymentService->confirmLabBookingPayment($payment, $confirmationResponse);

            return response()->json([
                'success' => true,
                'message' => 'Pago confirmado correctamente.',
                'data' => [
                    'payment' => $updatedPayment,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'No se pudo confirmar el pago.',
            ], 500);
        }
    }

    public function cancelAndRollbackLabBookingPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_id' => ['required', 'integer', 'exists:payments,id'],
        ]);

        /** @var PaymentModel $payment */
        $payment = PaymentModel::query()->findOrFail($validated['payment_id']);

        if ($payment->provider !== 'bancard') {
            return response()->json([
                'success' => false,
                'message' => 'El pago no pertenece a Bancard.',
            ], 400);
        }

        if (!$payment->provider_reference) {
            return response()->json([
                'success' => false,
                'message' => 'El pago no tiene provider_reference.',
            ], 400);
        }

        try {
            $rollbackResponse = $this->client->rollback($payment->provider_reference, '0.00');
            $updatedPayment = $this->paymentService->cancelAndRollbackLabBookingPayment($payment, $rollbackResponse);

            return response()->json([
                'success' => true,
                'message' => 'Pago cancelado y revertido correctamente.',
                'data' => [
                    'payment' => $updatedPayment,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'No se pudo cancelar el pago.',
            ], 500);
        }
    }
}