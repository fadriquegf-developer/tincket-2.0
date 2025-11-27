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
}
