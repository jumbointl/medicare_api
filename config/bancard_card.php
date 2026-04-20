<?php

return [
    'checkout_base_url' => env('BANCARD_CARD_CHECKOUT_BASE_URL', 'https://vpos.infonet.com.py:8888/payment/single_buy'),
    'checkout_script_url' => env('BANCARD_CARD_CHECKOUT_SCRIPT_URL', 'https://vpos.infonet.com.py:8888/checkout/javascript/dist/bancard-checkout-4.0.0.js'),
    'base_url' => env('BANCARD_CARD_BASE_URL', 'https://vpos.infonet.com.py:8888/vpos/api/0.3'),
    'public_key' => env('BANCARD_CARD_PUBLIC_KEY'),
    'private_key' => env('BANCARD_CARD_PRIVATE_KEY'),

    'return_url' => env('BANCARD_CARD_RETURN_URL'),
    'cancel_url' => env('BANCARD_CARD_CANCEL_URL'),
    'webhook_url' => env('BANCARD_CARD_WEBHOOK_URL'),
    'staging' => filter_var(env('BANCARD_CARD_STAGING', true), FILTER_VALIDATE_BOOL),
    
    'timeout' => env('BANCARD_TIMEOUT_SECONDS', 30),
    'connect_timeout' => env('BANCARD_CONNECT_TIMEOUT_SECONDS', 10),
    'retry_attempts' => env('BANCARD_RETRY_ATTEMPTS', 3),
];