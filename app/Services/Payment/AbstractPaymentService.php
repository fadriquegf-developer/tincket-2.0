<?php

namespace App\Services\Payment;

use App\Models\Cart;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Services\TpvConfigurationDecoder;

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

    abstract protected function setPaymentFromRequest(Request $request = null);

    /**
     * @param string $gateway name of Gateway registered in Omnipay lib
     */
    public function __construct($gateway_code)
    {
        $this->gateway_code = $gateway_code;
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
    public function confirmPayment()
    {
        $payment = $this->payment;
        $cart = $payment->cart;

        // Check if any other cart has the same session_id and slot_id pairs
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


        if ($duplicated_cart) {
            \Log::error('Duplicate error Cart id: ' . $cart->id);
            try {
                // Send email user
                $mailer = (new \App\Services\MailerBrandService($cart->brand->code_name))->getMailer();
                $mailer->to(trim($cart->client->email))->send(new \App\Mail\ErrorDuplicate($payment, $cart, $duplicated_cart));
            } catch (\Exception $e) {
                \Log::error('Duplicate error and the email could not be sent. Cart id: ' . $cart->id);
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
    }

    /**
     * Is called when a payment is confirmed. By default it will send email 
     * and generate PDFs for the client but it can be overriden
     */
    public function confirmedPayment()
    {
        // Email sending, pdf generation and so on will be done by Event Queue
        \App\Jobs\CartConfirm::dispatch($this->payment->cart, ['pdf' => config('base.inscription.ticket-web-params')]);
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
