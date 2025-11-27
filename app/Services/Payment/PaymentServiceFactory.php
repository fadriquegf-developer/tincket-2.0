<?php

namespace App\Services\Payment;

use App\Services\Payment\Impl\{
    PaymentFreeService,
    PaymentRedsysService,
    PaymentRedsysSoapService,
    PaymentTicketOfficeService
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
            'redsyssoap', 'sermepasoap', 'redsys_soap' => new PaymentRedsysSoapService(),

            // ------------- Gratuito --------------------------------
            'free'                                => new PaymentFreeService('Free'),

            // Agregar soporte para TicketOffice
            'ticketoffice', 'ticket_office'       => new PaymentTicketOfficeService('TicketOffice'),

            default => throw new InvalidArgumentException("Gateway {$gateway} no soportado"),
        };
    }
}