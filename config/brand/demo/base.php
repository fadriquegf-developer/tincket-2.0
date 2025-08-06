<?php

return [
    'inscription' => [
        'ticket-office-params' => [
            'ph' => 80,
            'pw' => 170,
            'mb' => 0,
            'mt' => 5,
            'ml' => 5,
            'mr' => 5
        ]
    ],
    /* Desactivo email para prueva QR oscuro */
    '_cart' => [
        'views' => [
            'email' => [
                'html' => 'brands.demo.emails.lang.%s.cart-confirmation'
            ]
        ]
    ],
    'emails' => [
        'reset-password' => 'brands.demo.emails.lang.%s.reset-password'
    ]
];
