<?php

return [
     /*
      |--------------------------------------------------------------------------
      | Mail Driver
      |--------------------------------------------------------------------------
      |
      | Laravel supports both SMTP and PHP's "mail" function as drivers for the
      | sending of e-mail. You may specify which one you're using throughout
      | your application here. By default, Laravel is setup for SMTP mail.
      |
      | Supported: "smtp", "mail", "sendmail", "mailgun", "mandrill",
      |            "ses", "sparkpost", "log"
      |
     */

    'driver' => 'mandrill',
    
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

