<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'smtp'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        // Tu mailer principal: Mandrill vía SMTP, con valores del .env de producción
        'smtp' => [
            'transport'  => 'smtp',
            'host'       => env('MAIL_HOST', 'smtp.mandrillapp.com'), 
            'port'       => env('MAIL_PORT', 2525),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username'   => env('MAIL_USERNAME'),  
            'password'   => env('MAIL_PASSWORD'),
            'timeout'    => null,
            'auth_mode'  => null,
        ],

        // ALIAS opcional "mandrill": usa SMTP igualmente (así nada rompe si alguien lo solicita por nombre)
        'mandrill' => [
            'transport'  => 'smtp',
            'host'       => 'smtp.mandrillapp.com',
            'port'       => 587,
            'encryption' => 'tls',
            // Mandrill recomienda username "apikey" + password = API key:
            'username'   => env('MANDRILL_USERNAME', 'apikey'),
            'password'   => env('MANDRILL_SECRET'),
            'timeout'    => null,
            'auth_mode'  => null,
        ],

        'ses' => ['transport' => 'ses'],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => ['timeout' => 5],
        ],

        'resend' => ['transport' => 'resend'],

        'sendmail' => [
            'transport' => 'sendmail',
            'path'      => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log'   => ['transport' => 'log', 'channel' => env('MAIL_LOG_CHANNEL')],
        'array' => ['transport' => 'array'],

        // Opcional: failover y roundrobin si los usas
        'failover' => [
            'transport' => 'failover',
            'mailers'   => ['smtp', 'log'],
        ],
        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers'   => ['ses', 'postmark'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@yesweticket.com'),
        'name'    => env('MAIL_FROM_NAME', 'YesWeTicket'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cart Confirmation Class
    |--------------------------------------------------------------------------
    |
    | Default mail class used for cart confirmation emails when a brand
    | does not have a specific mail.confirmation_class configured in
    | brand_settings table.
    |
    */

    'confirmation_class' => \App\Mail\Impl\DefaultCartConfirmation::class,

    /*
    |--------------------------------------------------------------------------
    | Group Cart Tickets in a single document
    |--------------------------------------------------------------------------
    |
    | By default we send every single ticket in a separate attached document.
    | Set this to true to merge ALL cart tickets in a single document.
    |
    */

    'merge_attachments' => env('MAIL_MERGE_ATTACHMENTS', false),

];
