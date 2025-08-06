<?php

return [
    'inscription' => [
        'view' => [
            'ticket' => 'core.inscription.ticket.ticket'
        ],
        'pdf_folder' => 'pdf/inscriptions', // inside storage/app
        'ticket-office-params' => [
            'ph' => 80,
            'pw' => 170,
            'mb' => 0,
            'mt' => 5,
            'ml' => 5,
            'mr' => 5,
            'zoom' => 1.28, //Extra para la nueva version de wkhtmltopdf, sino no se ve igual que la version anterior
            'dpi' => 70 //Extra para la nueva version de wkhtmltopdf, sino no se ve igual que la version anterior
        ],
        'ticket-web-params' => [
            'ph' => 80,
            'pw' => 170,
            'mb' => 0,
            'mt' => 5,
            'ml' => 5,
            'mr' => 5,
            'zoom' => 1.28, //Extra para la nueva version de wkhtmltopdf, sino no se ve igual que la version anterior
            'dpi' => 70 //Extra para la nueva version de wkhtmltopdf, sino no se ve igual que la version anterior
        ]
    ],
    'inscription_set' => [
        'pdf_folder' => 'pdf/orders', // inside storage/app
    ],
    'packs' => [
        'pdf_folder' => 'pdf/packs', // inside storage/app
    ],
    'gift_card' => [
        'pdf_folder' => 'pdf/gift_card', // inside storage/app
    ],
    'cart' => [
        'views' => [
            'email' => [
                'html' => 'core.emails.cart-confirmation-html',
                'plain' => 'core.emails.cart-confirmation-plain',
            ]
        ]
    ],
    'emails' => [
        'reset-password' => 'core.emails.reset-password',
        'basic-mailing-layout' => 'core.emails.mailing.basic-layout',
        'basic-mailing-text' => 'core.emails.mailing.basic-text',
        'contact' => 'core.emails.contact',
        'alta-promotor' => 'core.emails.partner',
        'email-payment' => 'core.emails.cart-payment-html'
    ],
    'statistics' => [
        'defaults' => [
            'filter_starts_on' => \Carbon\Carbon::now()->timezone("Europe/Andorra")->startOfMonth(),
            'filter_ends_on' => \Carbon\Carbon::now()->timezone("Europe/Andorra")->endOfMonth(),
        ]
    ]
];
