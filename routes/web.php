<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\PrescriptionController;
use App\Http\Controllers\Api\V1\AppointmentInvoiceController;
use App\Http\Controllers\Api\V1\payments\PaymentPayController;
use App\Http\Controllers\Api\V1\payments\razorpay\RazorpayResultController;
use App\Http\Controllers\Api\V1\payments\stripe\StripeResultController;
use App\Http\Controllers\Api\V1\payments\paystack\PaystackResultController;
use App\Http\Controllers\Api\V1\payments\flutterwave\FlutterwaveResultController;
use App\Http\Controllers\Api\V1\payments\esewa\EsewaResultController;
use App\Http\Controllers\Api\Bancard\Card\BancardCardWebhookController;
use App\Http\Controllers\Api\Bancard\Card\BancardCardRedirectController;
use App\Http\Controllers\Api\Bancard\Card\BancardCardPaymentController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/bancard/card/checkout/{payment}', [BancardCardPaymentController::class, 'openCheckout'])
    ->name('bancard.card.checkout');

Route::get('/bancard/card/return/{shopProcessId}', [BancardCardRedirectController::class, 'return'])
    ->name('bancard.card.return');

Route::get('/bancard/card/cancel/{shopProcessId}', [BancardCardRedirectController::class, 'cancel'])
    ->name('bancard.card.cancel');


Route::get('/bancard/card/payment-result', [BancardCardRedirectController::class, 'paymentResult'])
    ->name('bancard.card.payment-result');


Route::prefix('bancard/card')->group(function () {
    Route::match(['get', 'post'], 'return', [BancardCardRedirectController::class, 'return']);
    Route::match(['get', 'post'], 'cancel', [BancardCardRedirectController::class, 'cancel']);
    Route::post('webhook', [BancardCardWebhookController::class, 'handle']);
    Route::get('payment-result', [BancardCardRedirectController::class, 'paymentResult']);
});
Route::get('/', function () {
    return view('welcome');
    // return view('installation.step6');
    
});
//PaymentPayController
Route::get(
    '/payment/pay/{gateway}',
    [PaymentPayController::class, 'pay']
)->name('payment.pay');

//RazorpayResultController
Route::get(
    '/payment/razorpay/success',
    [RazorpayResultController::class, 'success']
)->name('payment.razorpay.success');
Route::get(
    '/payment/razorpay/failed',
    [RazorpayResultController::class, 'failed']
)->name('payment.razorpay.failed');

//StripeResultController
Route::get(
    '/payment/stripe/success',
    [StripeResultController::class, 'success']
)->name('payment.stripe.success');
Route::get(
    '/payment/stripe/failed',
    [StripeResultController::class, 'failed']
)->name('payment.stripe.failed');

//PaystackResultController
Route::get(
    '/payment/paystack/callback',
    [PaystackResultController ::class, 'callback']
)->name('payment.paystack.callback');

//EsewaResultController
Route::get('/payment/esewa/success', [EsewaResultController::class, 'success'])
    ->name('payment.esewa.success');

Route::get('/payment/esewa/failed', [EsewaResultController::class, 'failed'])
    ->name('payment.esewa.failed');

    //FlutterwaveResultController
    Route::get(
    '/payment/flutterwave/callback',
    [FlutterwaveResultController::class, 'callback']
)->name('payment.flutterwave.callback');


Route::get('/payment-success', function () {
    return 'Payment Successful';
})->name('payment.success.page');


Route::get('/payment-failed', function () {
    return 'Payment Failed. Please try again.';
})->name('payment.failed.page');

Route::get('prescriptions/pdf/{id}', [PrescriptionController::class, 'generatePDF']);
Route::get('invoice/pdf/{id}', [AppointmentInvoiceController::class, 'generatePDF']);

