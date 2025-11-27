<?php

return [
    'from' => [
        'address' => 'cirvianum@teatrecirvianum.cat',
        'name' => 'Teatre Cirviànum de Torelló',
    ],

    'contact' => [
        'to' => 'cirvianum@teatrecirvianum.cat',
        'subject' => 'Contacte des del web'
    ],

    /*
    |--------------------------------------------------------------------------
    | Cart Confirmation Class
    |--------------------------------------------------------------------------
    |
    | When sending a confirmation email, a class per each brand is used to be 
    | able to send through differents SMTP servers
    |
    */
    'confirmation_class' => '\\App\\Mail\\Impl\\DefaultCartConfirmation'
];
