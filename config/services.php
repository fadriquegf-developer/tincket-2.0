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

    // ⚠️ CRÍTICO: Las credenciales por defecto NO deben estar hardcodeadas
    'redsys' => [
        'merchantCode' => env('REDSYS_MERCHANT_CODE'),
        'merchantKey' => env('REDSYS_MERCHANT_KEY'),
        'terminal' => env('REDSYS_TERMINAL', '002'),
        'testMode' => env('REDSYS_TEST', true),
        'urlOK' => env('REDSYS_URL_OK'),
        'urlKO' => env('REDSYS_URL_KO'),
    ],

    'sermepa' => [
        'merchantCode' => env('REDSYS_MERCHANT_CODE'),
        'merchantKey' => env('REDSYS_MERCHANT_KEY'),
        'terminal' => env('REDSYS_TERMINAL', '002'),
        'testMode' => env('REDSYS_TEST', true),
        'urlOK' => env('REDSYS_URL_OK'),
        'urlKO' => env('REDSYS_URL_KO'),
    ],

    'javajan' => [
        'sermepa' => [
            'sermepaMerchantCode' => env('JAVAJAN_MERCHANT_CODE'),
            'sermepaMerchantKey' => env('JAVAJAN_MERCHANT_KEY'),
            'sermepaUrlKO' => ':public_url:/booking-failure/{id}?locale=ca',
            'sermepaUrlOK' => ':public_url:/booking-confirmed/{token}?locale=ca',
            'sermepaTestMode' => env('JAVAJAN_TEST_MODE', '0'),
            'sermepaMerchantName' => ':brand_name:',
            'sermepaTerminal' => env('JAVAJAN_TERMINAL', '002')
        ],
        'admin_emails' => explode(',', env('JAVAJAN_ADMIN_EMAILS', '')),
    ],

    'mandrill' => [
        'secret' => env('MANDRILL_SECRET')
    ],

    // Agregar configuración de cPanel
    'cpanel' => [
        'base_uri' => env('CPANEL_BASE_URI', 'https://yesweticket.com:2083'),
        'username' => env('CPANEL_USERNAME'),
        'api_key' => env('CPANEL_API_KEY'),
        'root_domain' => env('CPANEL_ROOT_DOMAIN', 'yesweticket.com'),
        'subdomain_dir' => env('CPANEL_SUBDOMAIN_DIR', '/public_html/engine/master/public'),
        'timeout' => env('CPANEL_TIMEOUT', 30),
    ],

];
