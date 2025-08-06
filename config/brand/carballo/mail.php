<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cart Confirmation Class
    |--------------------------------------------------------------------------
    |
    | When sending a confirmation email, a class per each brand is used to be 
    | able to send through differents SMTP servers
    |
    */
    'confirmation_class' => '\\App\\Mail\\Impl\\CartConfirmationCarballo'
];

