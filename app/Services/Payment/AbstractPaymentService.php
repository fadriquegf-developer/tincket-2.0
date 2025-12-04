<?php

namespace App\Services\Payment;

use App\Models\Cart;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Services\MailerService;
use App\Services\TpvConfigurationDecoder;
use App\Services\PaymentSlotLockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Abstract class for all PaymentServices used in Tincket
 *
 * @see PaymentServiceInterface
 * @author miquel
 */
abstract class AbstractPaymentService implements PaymentServiceInterface
{

    protected $gateway;
    protected $payment;
    protected $gateway_code;
    protected ?Cart $cart = null;
    /** 
     * @var TpvConfigurationDecoder|null
     */
    protected ?TpvConfigurationDecoder $cfg = null;
    /**
     * Servicio para manejar locks de slots durante el pago
     * @var PaymentSlotLockService
     */
    protected PaymentSlotLockService $paymentSlotLockService;

    abstract protected function setPaymentFromRequest(Request $request = null);

    /**
     * @param string $gateway name of Gateway registered in Omnipay lib
     */
    public function __construct($gateway_code)
    {
        $this->gateway_code = $gateway_code;
        $this->paymentSlotLockService = new PaymentSlotLockService();
    }

    /**
     * @return \Omnipay\Common\AbstractGateway
     */
    public function getGateway()
    {
        return $this->gateway;
    }

    public function getPayment(): Payment
    {
        if (!$this->payment)
            $this->setPaymentFromRequest();

        return $this->payment;
    }

    public function purchase(Cart $cart)
    {
        $this->cart = $cart;
        $this->payment = Payment::createFromCart($cart);

        $this->initGateway();
    }

    /**
     * This is called when the payment is valid. It will set the cart as
     * paid relating it with this valid payment
     */
    /*     public function confirmPayment()
    {
        $payment = $this->payment;
        $cart = $payment->cart;

        if ($cart->gift_cards()->exists() && !$cart->allInscriptions()->exists()) {
            $duplicated_cart = null;
        } else {
            $duplicated_cart = Cart::where('brand_id', '=', $cart->brand_id)
                ->whereNotNull('confirmation_code')
                ->where('confirmation_code', 'not like', 'XXXXXXXXX%') // Exclude any confirmation_code containing 'XXXXXXXXX' ya que son los que han enviado el email de pago, suele ser XXXXXXXXX-{id_cart}
                ->with('allInscriptions')
                ->where('id', '!=', $cart->id)
                ->whereHas('allInscriptions', function ($query) use ($cart) {
                    $query->where(function ($query) use ($cart) {
                        foreach ($cart->allInscriptions as $inscription) {
                            $query->orWhere(function ($query) use ($inscription) {
                                $query->whereNotNull('slot_id') // check only numered
                                    ->where('session_id', $inscription->session_id)
                                    ->where('slot_id', $inscription->slot_id);
                            });
                        }
                    });
                })->first();
        }

        if ($duplicated_cart) {
            \Log::error('Duplicate error Cart id: ' . $cart->id);
            try {
                // Send email user
                $mailer = app(MailerService::class)->getMailerForBrand($cart->brand);
                $mailer->to(trim($cart->client->email))->send(new \App\Mail\ErrorDuplicate($payment, $cart, $duplicated_cart));
            } catch (\Exception $e) {
                \Log::error('Duplicate error and the email could not be sent', [
                    'cart_id' => $cart->id,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        //XXXXXXXXX serian los carritos que han enviado el email de pago
        if ((!$payment->cart->is_confirmed || strpos($payment->cart->confirmation_code, 'XXXXXXXXX') !== false) && !$duplicated_cart) {
            $payment->cart->confirmation_code = $payment->order_code;
            $payment->cart->save();
            $payment->paid_at = new \DateTime();
            $payment->gateway = $this->getGateway()->getName();
            $payment->gateway_response = json_encode($this->getJsonResponse());
            $payment->save();
            $this->confirmedPayment();
        }
    } */
    /**
     * Confirmar pago después de recibir callback de la pasarela
     * 
     * ╔═══════════════════════════════════════════════════════════════════════╗
     * ║ CAMBIO CRÍTICO (Race Condition Fix - Última Línea de Defensa)         ║
     * ╠═══════════════════════════════════════════════════════════════════════╣
     * ║ Ahora usamos PaymentSlotLockService para:                             ║
     * ║   1. Adquirir lock distribuido (evita callbacks simultáneos)          ║
     * ║   2. Verificar que los slots no fueron vendidos a otro                ║
     * ║   3. Marcar pago para reembolso si hay conflicto                      ║
     * ║   4. Liberar locks de Redis al finalizar                              ║
     * ║                                                                       ║
     * ║ FLUJO ANTERIOR:                                                       ║
     * ║   1. Query sin lock para buscar duplicados                            ║
     * ║   2. Si duplicado → solo enviar email de error                        ║
     * ║   3. Si no duplicado → confirmar carrito                              ║
     * ║   ⚠️ Dos callbacks simultáneos podían pasar la verificación           ║
     * ║                                                                       ║
     * ║ FLUJO NUEVO:                                                          ║
     * ║   1. Adquirir lock distribuido para este carrito                      ║
     * ║   2. Verificar cada slot con query + lock                             ║
     * ║   3. Si conflicto → marcar para reembolso + enviar email              ║
     * ║   4. Si OK → confirmar carrito                                        ║
     * ║   5. Liberar lock de Redis                                            ║
     * ╚═══════════════════════════════════════════════════════════════════════╝
     */
    public function confirmPayment()
    {

        $payment = $this->payment;
        $cart = $payment->cart;


        // ─────────────────────────────────────────────────────────────────────
        // PASO 0: Si es solo gift cards (sin inscripciones), confirmar directamente
        // ─────────────────────────────────────────────────────────────────────
        $hasGiftCards = $cart->gift_cards()->exists();
        $hasInscriptions = $cart->allInscriptions()->exists();

        if ($hasGiftCards && !$hasInscriptions) {
            $this->confirmCartWithoutSlotVerification($payment, $cart);
            return;
        }

        // ─────────────────────────────────────────────────────────────────────
        // PASO 1: Verificar slots con lock distribuido
        // ─────────────────────────────────────────────────────────────────────
        try {
            $verificationResult = $this->paymentSlotLockService->verifyAndConfirmSlots($cart);
        } catch (\Throwable $e) {
            Log::error('❌ confirmPayment: ERROR en verifyAndConfirmSlots', [
                'cart_id' => $cart->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }

        // ─────────────────────────────────────────────────────────────────────
        // PASO 2: Manejar resultado de la verificación
        // ─────────────────────────────────────────────────────────────────────
        if (!$verificationResult['success']) {
            Log::warning('⚠️ confirmPayment: Verificación fallida, llamando handleDuplicatePayment', [
                'cart_id' => $cart->id,
                'reason' => $verificationResult['reason'] ?? 'unknown',
            ]);
            $this->handleDuplicatePayment($payment, $cart, $verificationResult);
            return;
        }

        // ─────────────────────────────────────────────────────────────────────
        // PASO 3: Todo OK - Confirmar el carrito
        // ─────────────────────────────────────────────────────────────────────
        $isAlreadyConfirmed = $cart->is_confirmed &&
            strpos($cart->confirmation_code, 'XXXXXXXXX') === false;

        if ($isAlreadyConfirmed) {
            Log::info('⚠️ confirmPayment: Cart already confirmed, skipping', [
                'cart_id' => $cart->id,
                'confirmation_code' => $cart->confirmation_code
            ]);
            $this->releasePaymentLocks($cart);
            return;
        }

        // ─────────────────────────────────────────────────────────────────────
        // PASO 3b: Guardar payment y cart en transacción
        // ─────────────────────────────────────────────────────────────────────

        try {
            DB::transaction(function () use ($cart, $payment) {

                $payment->paid_at = new \DateTime();
                $payment->gateway = $this->getGateway()->getName();

                $jsonResponse = $this->getJsonResponse();

                $payment->gateway_response = json_encode($jsonResponse);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('❌ confirmPayment: Error en json_encode', [
                        'json_error' => json_last_error_msg(),
                    ]);
                }

                $payment->save();

                $cart->confirmation_code = $payment->order_code;
                $cart->save();
            });
        } catch (\Throwable $e) {
            Log::error('❌ confirmPayment: ERROR en transacción DB', [
                'cart_id' => $cart->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }

        // ─────────────────────────────────────────────────────────────────────
        // PASO 4: Liberar locks de pago de Redis
        // ─────────────────────────────────────────────────────────────────────

        $this->releasePaymentLocks($cart);

        // ─────────────────────────────────────────────────────────────────────
        // PASO 5: Disparar eventos post-confirmación
        // ─────────────────────────────────────────────────────────────────────

        try {
            $this->confirmedPayment();
        } catch (\Throwable $e) {
            Log::error('❌ confirmPayment: ERROR en confirmedPayment()', [
                'cart_id' => $cart->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            // No relanzamos porque el pago ya está confirmado
        }
    }

    /**
     * Manejar pago duplicado (slots ya vendidos a otro)
     * 
     * CAMBIO: Ahora marca el pago para reembolso además de enviar email
     * 
     * @param Payment $payment
     * @param Cart $cart
     * @param array $verificationResult
     */
    protected function handleDuplicatePayment(Payment $payment, Cart $cart, array $verificationResult): void
    {
        $conflicts = $verificationResult['conflicts'] ?? [];
        $reason = $verificationResult['reason'] ?? 'duplicate_slots';

        Log::error('Duplicate payment detected', [
            'cart_id' => $cart->id,
            'payment_id' => $payment->id,
            'reason' => $reason,
            'conflicts' => $conflicts
        ]);

        // ─────────────────────────────────────────────────────────────────────
        // NUEVO: Marcar el pago para reembolso
        // ─────────────────────────────────────────────────────────────────────
        // 
        // Esto permite llevar un registro de pagos que necesitan devolución
        // y facilita el proceso de reembolso posterior

        if (method_exists($payment, 'markForRefund')) {
            $payment->markForRefund($reason, [
                'conflicts' => $conflicts,
                'detected_at' => now()->toIso8601String(),
                'cart_expires_on' => $cart->expires_on?->toIso8601String(),
                'payment_completed_at' => now()->toIso8601String(),
                'verification_result' => $verificationResult
            ]);
        } else {
            // Fallback si el método no existe (migración no aplicada)
            $payment->requires_refund = true;
            $payment->refund_reason = $reason;
            $payment->refund_details = [
                'conflicts' => $conflicts,
                'detected_at' => now()->toIso8601String()
            ];
            $payment->save();
        }

        // ─────────────────────────────────────────────────────────────────────
        // Enviar email de error al cliente (mantener comportamiento existente)
        // ─────────────────────────────────────────────────────────────────────

        try {
            // Buscar el carrito duplicado para el email
            $duplicatedCart = $this->findDuplicatedCart($cart, $conflicts);

            $mailer = app(\App\Services\MailerService::class)->getMailerForBrand($cart->brand);
            $mailer->to(trim($cart->client->email))
                ->send(new \App\Mail\ErrorDuplicate($payment, $cart, $duplicatedCart));

            Log::info('Duplicate error email sent', [
                'cart_id' => $cart->id,
                'client_email' => $cart->client->email
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send duplicate error email', [
                'cart_id' => $cart->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        // ─────────────────────────────────────────────────────────────────────
        // Liberar locks de pago (no necesarios ya que no confirmamos)
        // ─────────────────────────────────────────────────────────────────────

        $this->releasePaymentLocks($cart);
    }

    /**
     * Confirmar carrito sin verificación de slots (para gift cards)
     * 
     * @param Payment $payment
     * @param Cart $cart
     */
    protected function confirmCartWithoutSlotVerification(Payment $payment, Cart $cart): void
    {
        // Verificar que no esté ya confirmado
        $isAlreadyConfirmed = $cart->is_confirmed &&
            strpos($cart->confirmation_code, 'XXXXXXXXX') === false;

        if ($isAlreadyConfirmed) {
            Log::info('Gift card cart already confirmed, skipping', [
                'cart_id' => $cart->id
            ]);
            return;
        }

        DB::transaction(function () use ($cart, $payment) {
            // PRIMERO: Actualizar el pago (esto confirma que tenemos respuesta del TPV)
            $payment->paid_at = new \DateTime();
            $payment->gateway = $this->getGateway()->getName();
            $payment->gateway_response = json_encode($this->getJsonResponse());
            $payment->save();

            // DESPUÉS: Confirmar el carrito (solo si el payment se guardó OK)
            $cart->confirmation_code = $payment->order_code;
            $cart->save();
        });

        $this->confirmedPayment();
    }

    /**
     * Buscar el carrito que tiene los slots duplicados
     * 
     * @param Cart $cart
     * @param array $conflicts
     * @return Cart|null
     */
    protected function findDuplicatedCart(Cart $cart, array $conflicts): ?Cart
    {
        if (empty($conflicts)) {
            // Fallback: usar la query original si no hay detalles de conflictos
            return Cart::where('brand_id', '=', $cart->brand_id)
                ->whereNotNull('confirmation_code')
                ->where('confirmation_code', 'not like', 'XXXXXXXXX%')
                ->with('allInscriptions')
                ->where('id', '!=', $cart->id)
                ->whereHas('allInscriptions', function ($query) use ($cart) {
                    $query->where(function ($query) use ($cart) {
                        foreach ($cart->allInscriptions as $inscription) {
                            $query->orWhere(function ($query) use ($inscription) {
                                $query->whereNotNull('slot_id')
                                    ->where('session_id', $inscription->session_id)
                                    ->where('slot_id', $inscription->slot_id);
                            });
                        }
                    });
                })->first();
        }

        // Usar el cart_id del primer conflicto
        $firstConflict = $conflicts[0] ?? null;
        if ($firstConflict && isset($firstConflict['existing_cart_id'])) {
            return Cart::with('allInscriptions')->find($firstConflict['existing_cart_id']);
        }

        return null;
    }

    /**
     * Liberar locks de pago de Redis
     * 
     * @param Cart $cart
     */
    protected function releasePaymentLocks(Cart $cart): void
    {
        try {
            $released = $this->paymentSlotLockService->releasePaymentLocks($cart);

            if ($released > 0) {
                Log::debug('Payment locks released', [
                    'cart_id' => $cart->id,
                    'released_count' => $released
                ]);
            }
        } catch (\Exception $e) {
            // No es crítico si falla - los locks expirarán por TTL
            Log::warning('Failed to release payment locks', [
                'cart_id' => $cart->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Is called when a payment is confirmed. By default it will send email 
     * and generate PDFs for the client but it can be overriden
     */
    public function confirmedPayment()
    {
        // Email sending, pdf generation and so on will be done by Event Queue
        \App\Jobs\CartConfirm::dispatch($this->payment->cart, ['pdf' => brand_setting('base.inscription.ticket-web-params')]);
    }

    public function getConfigDecoder(): ?TpvConfigurationDecoder
    {
        return $this->cfg;
    }

    public function setPaymentType($paymentType)
    {
        $this->paymentType = $paymentType;
        return $this;
    }
}
