<?php

namespace App\Http\Controllers\Api\Bancard\Card;

use App\Http\Controllers\Controller;
use App\Models\PaymentModel;
use App\Services\Bancard\Card\BancardCardClient;
use App\Services\Bancard\Card\BancardCardPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BancardCardRedirectController extends Controller
{
    public function __construct(
        protected BancardCardClient $client,
        protected BancardCardPaymentService $paymentService,
    ) {
    }

    protected function frontendPaymentProcessUrl(array $params = []): string
    {
        $frontendBase = rtrim(
            (string) config('app.frontend_hospital_url', env('FRONTEND_HOSPITAL_URL', 'https://hospital.solexpresspy.com')),
            '/'
        );

        $query = http_build_query(array_filter($params, function ($value) {
            return !is_null($value) && $value !== '';
        }));

        return $frontendBase . '/payment-process' . ($query ? ('?' . $query) : '');
    }

    protected function shouldRedirectToWeb(?PaymentModel $payment): bool
    {
        if (!$payment) {
            return false;
        }

        $requestJson = $payment->request_json;

        if (is_string($requestJson)) {
            $decoded = json_decode($requestJson, true);
            $requestJson = is_array($decoded) ? $decoded : [];
        }

        $source = strtolower((string) data_get($requestJson, 'source', ''));

        return $source === 'web';
    }

    public function return(Request $request, string $shopProcessId): RedirectResponse
    {
        $providerReference = $shopProcessId
            ?: $request->input('shop_process_id')
            ?: $request->input('provider_reference')
            ?: $request->input('process_id');

        if (empty($providerReference)) {
            return redirect()->to(
                url('/bancard/card/payment-result?status=failed&message=missing_process_id')
            );
        }

        /** @var PaymentModel|null $payment */
        $payment = PaymentModel::query()
            ->where('provider', 'bancard')
            ->where('provider_reference', (string) $providerReference)
            ->latest('id')
            ->first();

        if (!$payment) {
            return redirect()->to(
                url('/bancard/card/payment-result?status=failed&message=payment_not_found&process_id=' . urlencode((string) $providerReference))
            );
        }

        try {
            $confirmationResponse = $this->client->confirmation($payment->provider_reference);

            $updatedPayment = $this->paymentService->confirmAppointmentPayment(
                $payment,
                $confirmationResponse
            );

            $success = (int) $updatedPayment->id_payment_status === 99;

            if ($this->shouldRedirectToWeb($updatedPayment)) {
                return redirect()->away($this->frontendPaymentProcessUrl([
                    'status' => $success ? 'success' : 'failed',
                    'process_id' => (string) $providerReference,
                    'payment_id' => $updatedPayment->id,
                    'appointment_id' => $updatedPayment->id_appointment,
                    'lab_booking_id' => $updatedPayment->id_lab_booking,
                ]));
            }

            return redirect()->to(
                url('/bancard/card/payment-result?status=' . ($success ? 'success' : 'failed')
                    . '&process_id=' . urlencode((string) $providerReference)
                    . '&payment_id=' . $updatedPayment->id)
            );
        } catch (\Throwable $e) {
            report($e);

            if ($this->shouldRedirectToWeb($payment)) {
                return redirect()->away($this->frontendPaymentProcessUrl([
                    'status' => 'failed',
                    'message' => 'confirm_exception',
                    'process_id' => (string) $providerReference,
                    'payment_id' => $payment->id,
                    'appointment_id' => $payment->id_appointment,
                    'lab_booking_id' => $updatedPayment->id_lab_booking,
                ]));
            }

            return redirect()->to(
                url('/bancard/card/payment-result?status=failed&message=confirm_exception&process_id=' . urlencode((string) $providerReference))
            );
        }
    }

    public function cancel(Request $request, string $shopProcessId): RedirectResponse
    {
        $processId = $shopProcessId
            ?: $request->input('process_id')
            ?: $request->input('shop_process_id')
            ?: $request->input('provider_reference');

        if (empty($processId)) {
            return redirect()->to(
                url('/bancard/card/payment-result?status=canceled&message=missing_process_id')
            );
        }

        /** @var PaymentModel|null $payment */
        $payment = PaymentModel::query()
            ->where('provider', 'bancard')
            ->where('provider_reference', (string) $processId)
            ->latest('id')
            ->first();

        if ($this->shouldRedirectToWeb($payment)) {
            return redirect()->away($this->frontendPaymentProcessUrl([
                'status' => $success ? 'success' : 'failed',
                'process_id' => (string) $providerReference,
                'payment_id' => $updatedPayment->id,
                'appointment_id' => $updatedPayment->id_appointment,
                'lab_booking_id' => $updatedPayment->id_lab_booking,
            ]));
        }

        return redirect()->to(
            url('/bancard/card/payment-result?status=canceled&process_id=' . urlencode((string) $processId))
        );
    }

    public function paymentResult(Request $request): View
    {
        return view('bancard.card.payment-result', [
            'status' => $request->query('status', 'unknown'),
            'message' => $request->query('message'),
            'processId' => $request->query('process_id'),
            'paymentId' => $request->query('payment_id'),
        ]);
    }
}