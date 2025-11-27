<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Traits\LogsActivity;

class Payment extends BaseModel
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use LogsActivity;

    protected $fillable = [
        'cart_id',
        'tpv_id',
        'tpv_name',
        'order_code',
        'gateway',
        'gateway_response',
        'paid_at',
        'requires_refund',
        'refund_reason',
        'refund_details',
        'refunded_at',
        'refund_reference',
    ];

    protected $casts = [
        'requires_refund' => 'boolean',
        'refund_details' => 'array',
        'refunded_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function tpv()
    {
        return $this->belongsTo(Tpv::class);
    }

    /**
     * Create and stores a new payment for the given Cart in database
     * 
     * @param Cart $cart
     * @return Payment
     */
    public static function createFromCart(Cart $cart)
    {
        $payment = new static;
        $payment->cart_id = $cart->id;

        // Rellenar ID a 7 caracteres con ceros a la izquierda
        $paddedId = str_pad($cart->id, 7, '0', STR_PAD_LEFT);

        // Generar 2 números aleatorios
        $randomNumbers = rand(10, 99);

        // Formato: 7 chars ID + -TK + 2 números = 12 caracteres
        $payment->order_code = $paddedId . '-TK' . $randomNumbers;

        $payment->save();

        return $payment;
    }

    public function getGatewayResponseProperty($value = null)
    {
        $resutl = null;
        $gateway_response = json_decode($this->gateway_response);

        if ($value) {
            $resutl = $gateway_response->{$value} ?? null;
        }

        return $resutl;
    }

    public function getTicketOfficePaymentType()
    {
        if ($this->gateway === 'TicketOffice') {
            return $this->getGatewayResponseProperty('payment_type');
        }

        return null;
    }

    /**
     * Marcar este pago como pendiente de reembolso
     * 
     * @param string $reason Motivo del reembolso (duplicate_slots, expired_session, etc.)
     * @param array $details Detalles adicionales del conflicto
     * @return self
     */
    public function markForRefund(string $reason, array $details = []): self
    {
        $this->requires_refund = true;
        $this->refund_reason = $reason;
        $this->refund_details = array_merge($details, [
            'marked_at' => now()->toIso8601String(),
        ]);
        $this->save();

        return $this;
    }

    /**
     * Marcar el reembolso como procesado
     * 
     * @param string|null $reference Código de referencia del reembolso
     * @return self
     */
    public function markAsRefunded(?string $reference = null): self
    {
        $this->refunded_at = now();
        $this->refund_reference = $reference;

        // Añadir al historial de detalles
        $details = $this->refund_details ?? [];
        $details['refunded_at'] = now()->toIso8601String();
        $details['refund_reference'] = $reference;
        $this->refund_details = $details;

        $this->save();

        return $this;
    }

    /**
     * ¿Este pago está pendiente de reembolso?
     * 
     * @return bool
     */
    public function isPendingRefund(): bool
    {
        return $this->requires_refund && is_null($this->refunded_at);
    }

    /**
     * ¿Este pago ya fue reembolsado?
     * 
     * @return bool
     */
    public function isRefunded(): bool
    {
        return $this->requires_refund && !is_null($this->refunded_at);
    }

    /**
     * Scope: Pagos pendientes de reembolso
     * 
     * Uso: Payment::pendingRefund()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePendingRefund($query)
    {
        return $query->where('requires_refund', true)
            ->whereNull('refunded_at');
    }

    /**
     * Scope: Pagos ya reembolsados
     * 
     * Uso: Payment::refunded()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRefunded($query)
    {
        return $query->where('requires_refund', true)
            ->whereNotNull('refunded_at');
    }
}
