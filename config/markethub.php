<?php

return [
    // Orders above this amount (in kobo) require manual admin approval instead of auto-confirming after OTP.
    'max_cod_auto_confirm_amount' => env('MARKETHUB_MAX_COD_AUTO_CONFIRM', 20000000),

    // How many failed delivery attempts before a vendor order is auto-cancelled and stock restored.
    'max_delivery_attempts' => env('MARKETHUB_MAX_DELIVERY_ATTEMPTS', 3),

    'otp' => [
        'length' => env('MARKETHUB_OTP_LENGTH', 6),
        'expires_in_minutes' => env('MARKETHUB_OTP_EXPIRES_IN_MINUTES', 10),
        'max_attempts' => env('MARKETHUB_OTP_MAX_ATTEMPTS', 5),
    ],

    'sms' => [
        'gateway' => env('MARKETHUB_SMS_GATEWAY', 'log'),
    ],
];
