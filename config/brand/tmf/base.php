<?php

return [
    'inscription' => [
        'view' => [
            'ticket' => 'brands.tmf.inscription.ticket.ticket'
        ]
    ],
    'cart' => [
        'views' => [
            'email' => [
                'html' => 'brands.tmf.emails.lang.%s.cart-confirmation',
                'plain' => 'brands.tmf.emails.lang.%s.cart-confirmation',
            ]
        ]
    ],

    'emails' => [
        'reset-password' => 'brands.tmf.emails.lang.%s.reset-password',
        'basic-mailing-layout' => 'brands.tmf.emails.mailing.basic-layout',
    ]

];
