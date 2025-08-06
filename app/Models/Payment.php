<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payment extends BaseModel
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'cart_id',
        'tpv_id',
        'tpv_name',
        'order_code',
        'gateway',
        'gateway_response',
        'amount',
        'paid_at'
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
        $orderCode = now()->format('ym') . str_pad($cart->id, 6, '0', STR_PAD_LEFT);
        $payment->order_code = $orderCode;
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
}
