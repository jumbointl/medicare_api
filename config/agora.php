<?php

return [
    'app_id' => env('AGORA_APP_ID'),
    'app_certificate' => env('AGORA_APP_CERTIFICATE'),
    'token_expire_seconds' => (int) env('AGORA_TOKEN_EXPIRE_SECONDS', 3600),
    'late_tolerance_minutes' => (int) env('AGORA_LATE_TOLERANCE_MINUTES', 20),
];