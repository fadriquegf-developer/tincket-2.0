<?php

namespace App\Mail;

use App\Models\Cart;
use App\Scopes\BrandScope;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentCartMail extends Mailable
{
    use Queueable, SerializesModels;

    public Cart $cart;

    /**
     * Create a new message instance.
     */
    public function __construct(Cart $cart)
    {
        $this->cart = $cart;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        // ─────────────────────────────────────────────────────────────────
        // Obtener la brand del carrito (importante para multi-tenant)
        // La brand del carrito puede ser diferente a la del cliente
        // cuando es un carrito de un promotor hijo
        // ─────────────────────────────────────────────────────────────────
        $brand = $this->cart->brand;

        // Fallback: obtener brand de la primera inscripción si el carrito no tiene
        if (!$brand) {
            $firstInscription = $this->cart->allInscriptions()
                ->withoutGlobalScope(BrandScope::class)
                ->with(['session.event.brand'])
                ->first();

            $brand = $firstInscription?->session?->event?->brand;
        }

        $brandName = $brand->name ?? 'YesWeTicket';

        // ─────────────────────────────────────────────────────────────────
        // Subject dinámico
        // ─────────────────────────────────────────────────────────────────
        $confirmationCode = $this->cart->confirmation_code;

        // Si el código es XXXXXXXXX-{id}, mostrar solo el ID para el subject
        if (str_starts_with($confirmationCode ?? '', 'XXXXXXXXX')) {
            $subject = "Enllaç de pagament per les teves entrades a {$brandName}";
        } else {
            $subject = "Pagament carrito {$confirmationCode} per entrades a {$brandName}";
        }

        $this->subject($subject);

        // ─────────────────────────────────────────────────────────────────
        // From y Reply-To desde configuración de la brand
        // ─────────────────────────────────────────────────────────────────
        $from = brand_setting('mail.from', [
            'address' => 'noreply@yesweticket.com',
            'name' => $brandName
        ]);

        $this->from($from['address'] ?? 'noreply@yesweticket.com', $from['name'] ?? $brandName);

        $replyTo = brand_setting('mail.replyto', brand_setting('mail.from.address', 'noreply@yesweticket.com'));
        $this->replyTo($replyTo);

        // ─────────────────────────────────────────────────────────────────
        // CC configurable por brand
        // ─────────────────────────────────────────────────────────────────
        $ccEmail = brand_setting('mail.cc_payment', 'gemma.javajan@gmail.com');
        if ($ccEmail) {
            $this->cc(is_array($ccEmail) ? $ccEmail : [$ccEmail]);
        }

        // ─────────────────────────────────────────────────────────────────
        // Vista del email
        // ─────────────────────────────────────────────────────────────────
        $view = brand_setting('base.emails.email-payment', 'core.emails.cart-payment-html');

        return $this->view($view, [
            'cart' => $this->cart,
            'client' => $this->cart->client,
            'brand' => $brand,
            'paymentUrl' => $this->getPaymentUrl(),
        ]);
    }

    /**
     * Construir la URL de pago correctamente para la brand
     */
    protected function getPaymentUrl(): string
    {
        $frontendUrl = rtrim(brand_setting('clients.frontend.url', config('app.url')), '/');
        return "{$frontendUrl}/reserva/pagament/carrito/{$this->cart->token}";
    }
}
