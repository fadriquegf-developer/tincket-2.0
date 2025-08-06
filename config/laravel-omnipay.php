<?php

return [

    // El gateway por defecto (puedes cambiarlo a 'stripe' si quieres)
    'default' => 'stripe',

    // Todos los gateways configurados
    'gateways' => [
        'paypal' => [
            'driver'  => 'PayPal_Express',
            'options' => [
                'solutionType'   => '',
                'landingPage'    => '',
                'headerImageUrl' => ''
            ]
        ],
        'stripe' => [
            'driver'  => 'Stripe',
            'options' => [
                'apiKey' => env('STRIPE_API_KEY'),
            ],
        ],
        // Añadirás Redsys aquí en el futuro
        // 'redsys' => [
        //     'driver'  => 'Redsys',
        //     'options' => [
        //         ...
        //     ],
        // ],
    ]

];
