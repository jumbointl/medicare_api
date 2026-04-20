<?php

namespace App\Http\Controllers\Api\Bancard\Card;

use App\Http\Controllers\Controller;
use App\Models\AppointmentModel;
use App\Models\PaymentModel;
use App\Services\Bancard\Card\BancardCardClient;
use App\Services\Bancard\Card\BancardCardPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BancardCardPaymentController extends Controller
{
    public function __construct(
        protected BancardCardPaymentService $paymentService,
        protected BancardCardClient $client,
    ) {
    }
    public function openCheckout(PaymentModel $payment)
    {
        if ($payment->provider !== 'bancard') {
            abort(404);
        }

        if (empty($payment->provider_process_id)) {
            abort(400, 'Payment has no provider_process_id');
        }

        return view('bancard.card.checkout-page', [
            'payment' => $payment,
            'processId' => $payment->provider_process_id,
            'checkoutScriptUrl' => rtrim((string) config('bancard_card.base_url'), '/vpos/api/0.3')
                . '/checkout/javascript/dist/bancard-checkout-4.0.0.js',
        ]);
    }

    public function startAppointmentPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'appointment_id' => ['required', 'integer', 'exists:appointments,id'],
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
            $result = $this->paymentService->startAppointmentPayment($validated);

            return response()->json([
                'success' => true,
                'message' => 'Pago iniciado correctamente.',
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo iniciar el pago.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function findCurrentByAppointment(AppointmentModel $appointment): JsonResponse
    {
        $payment = PaymentModel::query()
            ->where('id_appointment', $appointment->id)
            ->latest('id')
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró pago para esta cita.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pago obtenido correctamente.',
            'data' => $payment,
        ]);
    }

    public function confirmAppointmentPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_id' => ['required', 'integer', 'exists:payments,id'],
        ]);

        /** @var PaymentModel $payment */
        $payment = PaymentModel::query()->findOrFail($validated['payment_id']);

        try {
            $confirmationResponse = $this->client->confirmation(
                $payment->provider_reference
            );

            $updatedPayment = $this->paymentService->confirmAppointmentPayment(
                $payment,
                $confirmationResponse
            );

            return response()->json([
                'success' => true,
                'message' => 'Confirmación procesada correctamente.',
                'data' => [
                    'payment' => $updatedPayment,
                    'confirmation' => $confirmationResponse,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo confirmar el pago.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function cancelAndRollbackAppointmentPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_id' => ['required', 'integer', 'exists:payments,id'],
        ]);

        /** @var PaymentModel $payment */
        $payment = PaymentModel::query()->findOrFail($validated['payment_id']);

        try {
            $rollbackResponse = $this->client->rollback(
                $payment->provider_reference
            );

            $updatedPayment = $this->paymentService->cancelAndRollbackAppointmentPayment(
                $payment,
                $rollbackResponse
            );

            return response()->json([
                'success' => true,
                'message' => 'Cancelación/rollback procesado correctamente.',
                'data' => [
                    'payment' => $updatedPayment,
                    'rollback' => $rollbackResponse,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo cancelar/revertir el pago.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}