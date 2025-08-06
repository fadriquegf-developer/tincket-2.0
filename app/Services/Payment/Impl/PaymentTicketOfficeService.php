<?php

namespace App\Services\Payment\Impl;

use Illuminate\Http\Request;

/**
 * Description of PaymentSermepaService
 *
 * @author miquel
 */
class PaymentTicketOfficeService extends \App\Services\Payment\AbstractPaymentService
{
    public const CASH = 'cash';
    public const CARD = 'card';

    /**
     * 
     */
    private $paymentType = null;

    protected function setPaymentFromRequest(Request $request = null)
    {

    }

    public function getJsonResponse()
    {
        // to make it easier by now we will use the same price codification
        // as Sermepa but this logic should be isolated to its proper gateway
        return [
            'Ds_Amount' => $this->payment->cart->price_sold * 100,
            'payment_type' => $this->paymentType
        ];
    }

    public function initGateway()
    {
        $this->gateway = $this;
    }

    public function isPaymentSuccessful(Request $request)
    {
        return true;
    }

    public function confirmedPayment()
    {
        // unlike confirmPayment of Sermepa gateway, in TicketOffice gateway we
        // do not dispatch the job to the queue to avoid the customer waiting 
        // for queue processing        
        (new \App\Jobs\CartConfirm($this->payment->cart, ['send_mail' => false, 'pdf' => config('base.inscription.ticket-office-params')]))->handle();
    }

    public function getName()
    {
        return "TicketOffice";
    }

    public function setPaymentType($paymentType)
    {
        if (self::CASH === $paymentType || self::CARD === $paymentType) {
            $this->paymentType = $paymentType;
        }
    }
}
