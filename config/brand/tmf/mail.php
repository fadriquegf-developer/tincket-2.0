<?php

return [
    /*
      |--------------------------------------------------------------------------
      | Global "From" Address
      |--------------------------------------------------------------------------
      |
      | You may wish for all e-mails sent by your application to be sent from
      | the same address. Here, you may specify a name and address that is
      | used globally for all e-mails that are sent by your application.
      |
     */
    'from' => [
        'address' => 'tickets@torellomountainfilm.cat',
        'name' => 'Torello Mountain Film',
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
    'confirmation_class' => '\\App\\Mail\\Impl\\CartConfirmationTmf'
];

