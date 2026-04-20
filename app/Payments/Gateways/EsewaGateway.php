<?php

namespace App\Payments\Gateways;

use App\Models\PaymentGatewayModel;
use App\Models\PreOrderModel;
use App\Payments\Contracts\PaymentGatewayInterface;

class EsewaGateway implements PaymentGatewayInterface
{
    protected $gateway;

    public function __construct(PaymentGatewayModel $gateway)
    {
        $this->gateway = $gateway;
    }

    public function initiate(PreOrderModel $preOrder): array
    {
        /**
         * eSewa parameters
         * amt  = amount
         * pid  = product / transaction id
         * scd  = merchant code
         */

        $transactionId = 'pre_order_' . $preOrder->id . '_' . time();

        return [
            'transaction_id' => $transactionId,
            'payment_url' => route('payment.pay', [
                'gateway' => strtolower($this->gateway->title), // esewa
                'pre_order_id' => $preOrder->id,
                'ref' => $transactionId,
            ]),
        ];
    }
}


