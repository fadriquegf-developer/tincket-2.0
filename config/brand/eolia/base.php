<?php

return [
    'inscription' => [
        'view' => [
            'ticket' => 'brands.eolia.inscription.ticket'
        ]
    ],
    'cart' => [
        'views' => [
            'email' => [
                'html' => 'brands.eolia.emails.lang.%s.cart-confirmation'
            ]
        ]
    ],
    'emails' => [
        'reset-password' => 'brands.eolia.emails.lang.%s.reset-password',
        'basic-mailing-layout' => 'brands.eolia.emails.mailing.basic-layout',
    ],
    'statistics' => [
        'defaults' => [
            'filter_starts_on' => \Carbon\Carbon::create(2018, 9, 1)->timezone("Europe/Andorra"),
            'filter_ends_on' => \Carbon\Carbon::now(),
        ]
    ]
];
