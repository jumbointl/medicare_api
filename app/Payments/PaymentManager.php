<?php

namespace App\Payments;

use App\Models\PaymentGatewayModel;
use App\Payments\Gateways\RazorpayGateway;
use App\Payments\Gateways\StripeGateway;
use App\Payments\Gateways\PayStackGateway;
use App\Payments\Gateways\EsewaGateway;
use App\Payments\Gateways\FlutterwaveGateway;

class PaymentManager
{
    public static function initiate($gateway, $preOrder): array
    {
        return match (strtolower($gateway->title)) {
            'razorpay' => (new RazorpayGateway($gateway))->initiate($preOrder),
            'stripe' => (new StripeGateway($gateway))->initiate($preOrder),
            'paystack' => (new PayStackGateway($gateway))->initiate($preOrder),
            'esewa'    => (new EsewaGateway($gateway))->initiate($preOrder),
           'flutterwave'=> (new FlutterwaveGateway($gateway))->initiate($preOrder),
            default    => throw new \Exception('Payment gateway not supported'),
        };
    }
}
