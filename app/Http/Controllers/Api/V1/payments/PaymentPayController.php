<?php

namespace App\Http\Controllers\Api\V1\payments;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PaymentPayController extends Controller
{
    public function pay(Request $request, string $gateway)
    {
        return match ($gateway) {
            'razorpay' => app(\App\Http\Controllers\Api\V1\payments\razorpay\RazorpayPayController::class)->pay($request),
             'stripe' => app(\App\Http\Controllers\Api\V1\payments\stripe\StripePayController::class)->pay($request),
            'esewa'    => app(\App\Http\Controllers\Api\V1\payments\esewa\EsewaPayController::class)->pay($request),
            default => abort(404),
        };
    }
}
