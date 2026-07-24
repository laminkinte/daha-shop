<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'termii' => [
        'api_key' => env('TERMII_API_KEY'),
        'sender_id' => env('TERMII_SENDER_ID'),
    ],

    'paystack' => [
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
    ],

    'opay' => [
        'merchant_id' => env('OPAY_MERCHANT_ID'),
        'public_key' => env('OPAY_PUBLIC_KEY'),
        'secret_key' => env('OPAY_SECRET_KEY'),
        'sandbox' => env('OPAY_SANDBOX', true),
    ],

    'monnify' => [
        'api_key' => env('MONNIFY_API_KEY'),
        'secret_key' => env('MONNIFY_SECRET_KEY'),
        'contract_code' => env('MONNIFY_CONTRACT_CODE'),
        'sandbox' => env('MONNIFY_SANDBOX', true),
    ],

    // Not yet implemented (PalmPayClient throws) - placeholders for when a
    // real merchant account and verified API docs are available.
    'palmpay' => [
        'merchant_id' => env('PALMPAY_MERCHANT_ID'),
        'app_id' => env('PALMPAY_APP_ID'),
        'private_key' => env('PALMPAY_PRIVATE_KEY'),
        'sandbox' => env('PALMPAY_SANDBOX', true),
    ],

    // Not yet implemented (KudaClient throws) - placeholders for when a
    // real business account and verified API docs are available.
    'kuda' => [
        'client_id' => env('KUDA_CLIENT_ID'),
        'client_secret' => env('KUDA_CLIENT_SECRET'),
        'sandbox' => env('KUDA_SANDBOX', true),
    ],

];
