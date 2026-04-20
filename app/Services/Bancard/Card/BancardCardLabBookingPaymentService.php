<?php

namespace App\Services\Bancard\Card;

use App\Models\AppointmentInvoiceModel;
use App\Models\BancardPromotionModel;
use App\Models\LabBookingModel;
use App\Models\PaymentModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BancardCardLabBookingPaymentService
{
    public const PROVIDER = 'bancard';
    public const PROVIDER_ID_BANCARD = 1001;

    public const TYPE_PAY_AT_CLINIC = 1100;
    public const TYPE_BANK_TRANSFER = 1200;
    public const TYPE_PAY_ONE_HOUR_BEFORE = 1500;
    public const TYPE_MEDICAL_AGREEMENT_PRESENTIAL = 2100;

    public const TYPE_DEBIT_CARD = 7000;
    public const TYPE_CREDIT_CARD = 7500;
    public const TYPE_QR = 7900;

    public const TYPE_PREAPPROVED_CREDIT = 8000;
    public const TYPE_CASH = 9001;
    public const TYPE_HOSPITAL_WALLET = 9100;

    public function __construct(
        protected BancardCardClient $client,
    ) {
    }

    public function startLabBookingPayment(array $data): array
    {
        return DB::transaction(function () use ($data) {
            /** @var LabBookingModel $labBooking */
            $labBooking = LabBookingModel::query()->findOrFail($data['lab_booking_id']);

            $invoice = AppointmentInvoiceModel::query()
                ->where('lab_booking_id', $labBooking->id)
                ->latest('id')
                ->first();

            if (!$invoice) {
                throw new \RuntimeException('No se encontró factura para esta reserva de laboratorio.');
            }

            $amount = $this->normalizeAmount($data['amount']);
            $paymentTypeId = (int) $data['id_payment_type'];
            $userId = (int) $data['id_user'];
            $currency = $data['currency'] ?? 'PYG';
            $description = $data['description'] ?? ('Lab booking #' . $labBooking->id);
            $identification = $data['identification'] ?? null;

            $payment = new PaymentModel();
            $payment->id_user = $userId;
            $payment->id_appointment = null;
            $payment->id_lab_booking = $labBooking->id;
            $payment->id_payment_type = $paymentTypeId;
            $payment->name_payment_type = $this->resolvePaymentTypeName($paymentTypeId);
            $payment->provider = self::PROVIDER;
            $payment->payment_provider_id = self::PROVIDER_ID_BANCARD;
            $payment->payment_method_code = $this->resolvePaymentMethodCode($paymentTypeId);
            $payment->provider_reference = null;
            $payment->provider_process_id = null;
            $payment->provider_operation_id = null;
            $payment->id_payment_status = $this->resolveInitialPaymentStatus($paymentTypeId);
            $payment->currency_code = $currency;
            $payment->gateway_description = $this->resolveInitialGatewayDescription($paymentTypeId);
            $payment->authorization_code = null;
            $payment->ticket_number = null;
            $payment->response_code = null;
            $payment->response_message = null;
            $payment->account_type = null;
            $payment->card_last_numbers = null;
            $payment->bin = null;
            $payment->merchant_code = null;
            $payment->confirmed_at = $this->isAutoPaidPaymentType($paymentTypeId) ? now() : null;
            $payment->canceled_at = null;
            $payment->expires_at = $this->requiresOnlineValidation($paymentTypeId)
                ? Carbon::now()->addMinutes(15)
                : null;
            $payment->request_json = null;
            $payment->response_json = null;
            $payment->webhook_json = null;
            $payment->webhook_received_at = null;
            $payment->is_webhook_confirmed = 0;
            $payment->amount = $amount;
            $payment->identification = $identification;
            $payment->save();

            $invoice->status = $this->resolveInvoicePaymentStatus($paymentTypeId);
            $invoice->updated_at = now();
            $invoice->save();

            if ($this->isManualValidation($paymentTypeId)) {
                return [
                    'lab_booking' => $labBooking->fresh(),
                    'invoice' => $invoice->fresh(),
                    'payment' => $payment->fresh(),
                    'payment_flow' => [
                        'required' => false,
                        'mode' => 'manual_validation',
                        'payment_id' => $payment->id,
                        'payment_type_id' => $payment->id_payment_type,
                        'provider' => self::PROVIDER,
                        'provider_id' => self::PROVIDER_ID_BANCARD,
                    ],
                ];
            }

            if ($this->isAutoPaidPaymentType($paymentTypeId)) {
                return [
                    'lab_booking' => $labBooking->fresh(),
                    'invoice' => $invoice->fresh(),
                    'payment' => $payment->fresh(),
                    'payment_flow' => [
                        'required' => false,
                        'mode' => 'auto_paid',
                        'payment_id' => $payment->id,
                        'payment_type_id' => $payment->id_payment_type,
                        'provider' => self::PROVIDER,
                        'provider_id' => self::PROVIDER_ID_BANCARD,
                    ],
                ];
            }

            $promotionCode = $data['promotion_code'] ?? null;
            $additionalData = $data['additional_data'] ?? null;

            if (!empty($promotionCode)) {
                $promotion = BancardPromotionModel::query()
                    ->where('code', $promotionCode)
                    ->where('active', 1)
                    ->first();

                if (!$promotion) {
                    throw new \RuntimeException('Promoción Bancard no encontrada o inactiva.');
                }

                $additionalData = $promotion->additional_data;
            }

            $source = $data['source'] ?? 'web';
            $maxAttempts = (int) config('bancard_card.retry_attempts', 3);
            $lastException = null;
            $response = null;
            $shopProcessId = null;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                $shopProcessId = $this->buildShopProcessId((int) $payment->id, $attempt);

                $requestPayload = [
                    'amount' => $amount,
                    'currency' => $currency,
                    'description' => $description,
                    'additional_data' => $additionalData,
                    'source' => $source,
                    'shop_process_id' => $shopProcessId,
                    'return_url' => rtrim((string) config('bancard_card.return_url'), '/') . '/' . urlencode($shopProcessId),
                    'cancel_url' => rtrim((string) config('bancard_card.cancel_url'), '/') . '/' . urlencode($shopProcessId),
                ];

                $payment->provider_reference = $shopProcessId;
                $payment->provider_process_id = null;
                $payment->provider_operation_id = null;
                $payment->gateway_description = "Intentando conexión con Bancard (#{$attempt})";
                $payment->request_json = $requestPayload;
                $payment->response_json = null;
                $payment->response_code = null;
                $payment->response_message = null;
                $payment->save();

                try {
                    $response = $this->client->singleBuy($requestPayload);
                    break;
                } catch (\Throwable $e) {
                    $lastException = $e;
                    report($e);

                    if ($this->isTimeoutException($e)) {
                        try {
                            $this->client->rollback($shopProcessId);
                        } catch (\Throwable $rollbackException) {
                            report($rollbackException);
                        }

                        if ($attempt < $maxAttempts) {
                            continue;
                        }

                        throw new \RuntimeException(
                            'No se puede establecer conexión con procesador de tarjeta, favor de intentar más tarde'
                        );
                    }

                    throw $e;
                }
            }

            if (!$response) {
                throw $lastException ?: new \RuntimeException(
                    'No se puede establecer conexión con procesador de tarjeta, favor de intentar más tarde'
                );
            }

            $raw = $response['raw'] ?? [];
            $status = $raw['status'] ?? null;
            $processId = $raw['process_id'] ?? null;

            $checkoutBaseUrl = rtrim((string) config('bancard_card.checkout_base_url'), '/');

            $paymentUrl = $raw['process_url']
                ?? $raw['payment_url']
                ?? (!empty($processId)
                    ? $checkoutBaseUrl . '?process_id=' . urlencode($processId)
                    : null);

            $responseMessage = $this->extractMessage($raw);

            $payment->response_json = $raw;
            $payment->response_code = $status;
            $payment->response_message = $responseMessage;
            $payment->provider_process_id = $processId;
            $payment->gateway_description = $responseMessage ?: 'Respuesta recibida de Bancard';
            $payment->id_payment_status = ($status === 'success' && !empty($processId)) ? 10 : 90;
            $payment->save();

            $invoice->status = 'Unpaid';
            $invoice->updated_at = now();
            $invoice->save();

            return [
                'lab_booking' => $labBooking->fresh(),
                'invoice' => $invoice->fresh(),
                'payment' => $payment->fresh(),
                'payment_flow' => [
                    'required' => true,
                    'mode' => 'online_validation',
                    'payment_url' => $paymentUrl,
                    'checkout_page_url' => url('/bancard/card/checkout/' . $payment->id),
                    'payment_id' => $payment->id,
                    'payment_type_id' => $payment->id_payment_type,
                    'provider' => self::PROVIDER,
                    'provider_id' => self::PROVIDER_ID_BANCARD,
                    'provider_reference' => $payment->provider_reference,
                    'provider_process_id' => $payment->provider_process_id,
                ],
            ];
        });
    }

    public function confirmLabBookingPayment(PaymentModel $payment, array $confirmationResponse): PaymentModel
    {
        return DB::transaction(function () use ($payment, $confirmationResponse) {
            $raw = $confirmationResponse['raw'] ?? $confirmationResponse;
            $confirmation = $raw['confirmation'] ?? $raw['operation'] ?? [];

            $response = strtoupper((string) ($confirmation['response'] ?? ''));
            $responseCode = (string) ($confirmation['response_code'] ?? '');
            $responseDetails = $confirmation['response_details'] ?? null;
            $responseDescription = $confirmation['response_description'] ?? null;
            $extendedResponseDescription = $confirmation['extended_response_description'] ?? null;

            $responseMessage = $responseDescription
                ?? $extendedResponseDescription
                ?? $responseDetails
                ?? $confirmation['response_message']
                ?? 'Confirmación recibida';

            $success = $response === 'S';

            $payment->response_json = $raw;
            $payment->response_code = $responseCode;
            $payment->response_message = $responseMessage;
            $payment->authorization_code = $confirmation['authorization_number']
                ?? $confirmation['authorization_code']
                ?? null;
            $payment->ticket_number = $confirmation['ticket_number'] ?? null;
            $payment->provider_operation_id = $confirmation['operation_id'] ?? null;
            $payment->confirmed_at = $success ? now() : null;
            $payment->id_payment_status = $success ? 99 : 92;
            $payment->gateway_description = $success ? 'Pago confirmado' : ($responseMessage ?: 'Pago rechazado');
            $payment->save();

            if ($payment->id_lab_booking) {
                $this->syncLabInvoiceStatus((int) $payment->id_lab_booking, $success);
            }

            return $payment->fresh();
        });
    }

    public function cancelAndRollbackLabBookingPayment(PaymentModel $payment, array $rollbackResponse): PaymentModel
    {
        return DB::transaction(function () use ($payment, $rollbackResponse) {
            $raw = $rollbackResponse['raw'] ?? $rollbackResponse;
            $message = $this->extractMessage($raw) ?: 'Rollback ejecutado';

            $payment->response_json = ['rollback' => $raw];
            $payment->response_message = $message;
            $payment->response_code = $raw['status'] ?? 'rollback';
            $payment->canceled_at = now();
            $payment->id_payment_status = 93;
            $payment->gateway_description = 'Pago revertido';
            $payment->save();

            if ($payment->id_lab_booking) {
                $this->syncLabInvoiceStatus((int) $payment->id_lab_booking, false);
            }

            return $payment->fresh();
        });
    }

    public function markWebhookForLabBooking(PaymentModel $payment, array $payload): PaymentModel
    {
        return DB::transaction(function () use ($payment, $payload) {
            $operation = $payload['operation'] ?? [];
            $response = strtoupper((string) ($operation['response'] ?? ''));
            $responseCode = (string) ($operation['response_code'] ?? '');
            $responseDetails = $operation['response_details'] ?? null;
            $responseDescription = $operation['response_description'] ?? null;
            $extendedResponseDescription = $operation['extended_response_description'] ?? null;

            $responseMessage = $responseDescription
                ?? $extendedResponseDescription
                ?? $responseDetails
                ?? $operation['response_message']
                ?? 'Webhook recibido';

            $success = $response === 'S';

            $payment->webhook_json = $payload;
            $payment->webhook_received_at = now();
            $payment->is_webhook_confirmed = 1;
            $payment->response_code = $responseCode ?: $payment->response_code;
            $payment->response_message = $responseMessage;
            $payment->id_payment_status = $success ? 99 : 91;
            $payment->confirmed_at = $success ? now() : $payment->confirmed_at;
            $payment->canceled_at = $success ? $payment->canceled_at : now();
            $payment->gateway_description = $success
                ? 'Webhook confirmó pago'
                : ($responseMessage ?: 'Webhook indicó cancelación/error');
            $payment->save();

            if ($payment->id_lab_booking) {
                $this->syncLabInvoiceStatus((int) $payment->id_lab_booking, $success);
            }

            return $payment->fresh();
        });
    }

    protected function syncLabInvoiceStatus(int $labBookingId, bool $success): void
    {
        $invoice = AppointmentInvoiceModel::query()
            ->where('lab_booking_id', $labBookingId)
            ->latest('id')
            ->first();

        if (!$invoice) {
            return;
        }

        $invoice->status = $success ? 'Paid' : 'Unpaid';
        $invoice->updated_at = now();
        $invoice->save();
    }

    protected function resolveInvoicePaymentStatus(int $idPaymentType): string
    {
        return $this->isAutoPaidPaymentType($idPaymentType) ? 'Paid' : 'Unpaid';
    }

    protected function buildShopProcessId(int $paymentId, int $attempt = 1): string
    {
        $secondsOfDay = ((int) now()->format('H') * 3600)
            + ((int) now()->format('i') * 60)
            + (int) now()->format('s');

        return (string) $paymentId . sprintf('%05d', $secondsOfDay) . (string) $attempt;
    }

    protected function normalizeAmount(float|int|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    protected function isManualValidation(int $idPaymentType): bool
    {
        return $idPaymentType < 5000;
    }

    protected function requiresOnlineValidation(int $idPaymentType): bool
    {
        return $idPaymentType >= 5001 && $idPaymentType <= 7999;
    }

    protected function isAutoPaidPaymentType(int $idPaymentType): bool
    {
        return $idPaymentType >= 8000;
    }

    protected function resolveInitialPaymentStatus(int $idPaymentType): int
    {
        if ($this->isAutoPaidPaymentType($idPaymentType)) {
            return 99;
        }

        return 10;
    }

    protected function resolveInitialGatewayDescription(int $idPaymentType): string
    {
        if ($this->isManualValidation($idPaymentType)) {
            return 'Pendiente de validación manual';
        }

        if ($this->isAutoPaidPaymentType($idPaymentType)) {
            return 'Pago tomado como confirmado';
        }

        return 'Pago pendiente de inicialización';
    }

    protected function resolvePaymentTypeName(int $idPaymentType): string
    {
        return match ($idPaymentType) {
            self::TYPE_PAY_AT_CLINIC => 'PAGO EN CLINICA',
            self::TYPE_BANK_TRANSFER => 'TRANSFERENCIA BANCARIA',
            self::TYPE_PAY_ONE_HOUR_BEFORE => 'PAGO 1 HORA ANTES DE CONSULTA',
            self::TYPE_MEDICAL_AGREEMENT_PRESENTIAL => 'CONVENIO MEDICO PRESENCIAL',
            self::TYPE_DEBIT_CARD => 'TARJETA DE DEBITO',
            self::TYPE_CREDIT_CARD => 'TARJETA DE CREDITO',
            self::TYPE_QR => 'QR',
            self::TYPE_PREAPPROVED_CREDIT => 'CREDITO PREAPROBADO',
            self::TYPE_CASH => 'EFECTIVO',
            self::TYPE_HOSPITAL_WALLET => 'BILLETERA HOSPITAL PROPIO',
            default => 'NO DEFINIDO',
        };
    }

    protected function resolvePaymentMethodCode(int $idPaymentType): string
    {
        return match ($idPaymentType) {
            self::TYPE_DEBIT_CARD => 'debit_card',
            self::TYPE_CREDIT_CARD => 'credit_card',
            self::TYPE_QR => 'qr',
            self::TYPE_BANK_TRANSFER => 'bank_transfer',
            self::TYPE_PAY_AT_CLINIC => 'pay_at_clinic',
            self::TYPE_PAY_ONE_HOUR_BEFORE => 'pay_before_consultation',
            self::TYPE_MEDICAL_AGREEMENT_PRESENTIAL => 'medical_agreement_presential',
            self::TYPE_PREAPPROVED_CREDIT => 'preapproved_credit',
            self::TYPE_CASH => 'cash',
            self::TYPE_HOSPITAL_WALLET => 'hospital_wallet',
            default => (string) $idPaymentType,
        };
    }

    protected function isTimeoutException(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'timeout')
            || str_contains($message, 'timed out')
            || str_contains($message, 'connection timed out')
            || str_contains($message, 'operation timed out')
            || str_contains($message, 'could not connect')
            || str_contains($message, 'connection refused')
            || str_contains($message, 'connection reset');
    }

    protected function extractMessage(array $raw): ?string
    {
        if (!empty($raw['messages'][0]['dsc'])) {
            return $raw['messages'][0]['dsc'];
        }

        if (!empty($raw['message'])) {
            return $raw['message'];
        }

        if (!empty($raw['status'])) {
            return (string) $raw['status'];
        }

        return null;
    }
}