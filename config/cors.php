<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://admin.solexpresspy.com',
        'https://hospital.solexpresspy.com',
        'https://doctor.solexpresspy.com',
        'http://localhost:5173',

        
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'X-Requested-With',
        'Authorization',
        'Accept',
        'Origin',
        'x-api-key',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];