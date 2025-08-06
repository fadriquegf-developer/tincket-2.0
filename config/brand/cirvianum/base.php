<?php

return [
    'inscription' => [
        'view' => [
            'ticket' => 'brands.cirvianum.inscription.ticket'
        ],
        'ticket-office-params' => [
            'ph' => 80,
            'pw' => 170,
            'mb' => 0,
            'mt' => 5,
            'ml' => 5,
            'mr' => 5
        ]
    ],
    'cart' => [
        'views' => [
            'email' => [
                'html' => 'brands.cirvianum.emails.lang.%s.cart-confirmation'
            ]
        ]
    ],
    'emails' => [
        'reset-password' => 'brands.cirvianum.emails.lang.%s.reset-password',
        'basic-mailing-layout' => 'brands.cirvianum.emails.mailing.basic-layout',        
    ]
];
