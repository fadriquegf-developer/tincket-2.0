<?php

namespace App\Services\Payment;

use Illuminate\Http\Request;
use App\Models\Payment;

/**
 * During the sending and receiving payment request and response we need
 * to prepare data according to each payment platform.
 * 
 * Omnipays helps as to send and receive this data with an agnostic Gateway.
 * 
 * Anyway, the data we need to set in the OmnipayGateway to be sent may differ 
 * in any case, like the data received from them.
 * 
 * This Interface defines the methods that any PaymentService should implement.
 * 
 * Then we will use this PaymentService (ie PaymentSermepaService) to interact with.
 * 
 * @see AbstractPaymentService
 * 
 * @author miquel
 */
interface PaymentServiceInterface
{

    public function initGateway();

    public function getGateway();

    /**
     * Returns the Payment object that the gateway response is related to
     * 
     * @return Payment
     */
    public function getPayment(): Payment;

    /**
     * Returns the JSON response returned for the payment platform
     */
    public function getJsonResponse();

    /**
     * 
     * @param \App\Models\Cart $cart
     */
    public function purchase(\App\Models\Cart $cart);

    public function isPaymentSuccessful(Request $request);
}
