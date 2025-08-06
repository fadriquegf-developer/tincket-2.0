<?php

namespace App\Services\Payment;

use App\Services\Payment\Impl\{
    PaymentFreeService,
    PaymentRedsysService,
    PaymentRedsysSoapService
};
use InvalidArgumentException;

class PaymentServiceFactory
{
    public static function create(string $gateway): AbstractPaymentService
    {
        // 1) Normalizamos: todo en minúscula y espacios/guiones → guión bajo
        $key = strtolower($gateway);
        $key = str_replace([' ', '-'], '_', $key);

        return match ($key) {
            // ------------- PASARELA REDIRECT (compra) -------------
            'redsys', 'sermepa', 'redsys_redirect' => new PaymentRedsysService(),

            // ------------- CALLBACK SOAP (notificación) ------------
            'redsyssoap', 'sermepasoap'           => new PaymentRedsysSoapService(),

            // ------------- Gratuito --------------------------------
            'free'                                => new PaymentFreeService('Free'),

            default => throw new InvalidArgumentException("Gateway {$gateway} no soportado"),
        };
    }

}

