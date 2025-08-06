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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'stripe' => [
        'secret' => env('STRIPE_API_KEY'),
    ],
    'redsys' => [
        'merchantCode' => env('REDSYS_MERCHANT_CODE', '334582459'),
        'merchantKey' => env('REDSYS_MERCHANT_KEY', 'sq7HjrUOBfKmC576ILgskD5srU870gJ7'),
        'terminal' => env('REDSYS_TERMINAL', '002'),
        'testMode' => env('REDSYS_TEST', true),
        'urlOK' => env('REDSYS_URL_OK'),
        'urlKO' => env('REDSYS_URL_KO'),
    ],
    'sermepa' => [
        'merchantCode' => env('REDSYS_MERCHANT_CODE', '334582459'),
        'merchantKey' => env('REDSYS_MERCHANT_KEY', 'sq7HjrUOBfKmC576ILgskD5srU870gJ7'),
        'terminal' => env('REDSYS_TERMINAL', '002'),
        'testMode' => env('REDSYS_TEST', true),
        'urlOK' => env('REDSYS_URL_OK'),        // ej: https://test.yourapp.com/booking-confirmed/{token}
        'urlKO' => env('REDSYS_URL_KO'),        // ej: https://test.yourapp.com/booking-failure/{id}
    ],
    'javajan' => [
        'sermepa' => [
            'sermepaMerchantCode' => '336790613',
            'sermepaMerchantKey' => '0t8Z5B0x0pzM91S3dR2r6RGAoWdHDijw',
            'sermepaUrlKO' => ':public_url:/booking-failure/{id}?locale=ca',
            'sermepaUrlOK' => ':public_url:/booking-confirmed/{token}?locale=ca',
            'sermepaTestMode' => '0',
            'sermepaMerchantName' => ':brand_name:',
            'sermepaTerminal' => '002'
        ]
    ],
    'mandrill' => [
        'secret' => 'vd8dPZy4pESKj7bFiiSt4g'
    ],

];
