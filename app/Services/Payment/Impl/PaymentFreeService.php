<?php

namespace App\Services\Payment\Impl;

use App\Models\Cart;
use Illuminate\Http\Request;

class PaymentFreeService extends \App\Services\Payment\AbstractPaymentService
{

    protected $gateway_code = 'Free';
    protected function setPaymentFromRequest(Request $request = null)
    {
    }

    public function purchase(Cart $cart)
    {
        parent::purchase($cart);

        if ($cart->price_sold == 0) {
            $this->confirmPayment();
        }
    }

    public function getData()
    {
        $data['platform'] = $this->getName();

        return $data;
    }

    public function getJsonResponse()
    {
        // to make it easier by now we will use the same price codification
        // as Sermepa but this logic should be isolated to its proper gateway
        return ['Ds_Amount' => $this->payment->cart->price_sold * 100];
    }

    public function initGateway()
    {
        $this->gateway = $this;
    }

    public function isPaymentSuccessful(Request $request)
    {
        return $this->payment->cart->price_sold === 0;
    }

    public function getName()
    {
        return "Free";
    }
}
