<?php
// app/Services/Payment/Impl/PaymentSermepaSoapService.php

namespace App\Services\Payment\Impl;

use App\Models\Payment;
use App\Models\Cart;
use App\Services\Payment\AbstractPaymentService;
use App\Services\Payment\Lib\RedsysAPI;
use App\Services\TpvConfigurationDecoder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use BadMethodCallException;
use Omnipay\Common\Message\AbstractResponse;

class PaymentRedsysSoapService extends AbstractPaymentService
{
    /** @var RedsysAPI */
    protected RedsysAPI $redsys_lib;

    /** XML crudo que llega del banco */
    protected string $xml = '';

    /** Configuración decodificada del TPV */
    protected TpvConfigurationDecoder $tpv_config;

    /** Resultado OK/KO que devolveremos al banco */
    protected bool $success = false;

    public function __construct(string $gatewayAlias = 'SermepaSoap')
    {
        parent::__construct($gatewayAlias);
        $this->redsys_lib = new RedsysAPI();
    }

    /**
     * Parte de la interfaz: no usamos gateway externo.
     */
    public function initGateway(): void
    {
        $this->gateway = $this;
    }

    /**
     * No aplicable para SOAP: stubb de la interfaz.
     */
    protected function buildRequestData($cart): array
    {
        return [];
    }

    /**
     * No aplicable para SOAP: error si se llama.
     */
    public function purchase($cart): AbstractResponse
    {
        throw new BadMethodCallException('SOAP service no soporta purchase().');
    }

    /**
     * Punto de entrada para el callback SOAP de Redsys.
     * El método debe llamarse exactamente procesaNotificacionSIS
     * para que el SoapServer lo invoque.
     */
    public function procesaNotificacionSIS(string $xml): string
    {
        // 1) Limpia saltos de línea
        $this->xml = trim(preg_replace('/\s\s+/', ' ', $xml));

        // 2) Carga el Payment
        $this->setPaymentFromRequest();

        // 3) Carga config de marca
        (new \App\Http\Middleware\CheckBrandHost())
            ->loadBrandConfig($this->payment->cart->brand->code_name);

        // 4) Carga TPV (objeto decoder, no array)
        $this->tpv_config = new TpvConfigurationDecoder($this->payment->tpv->config);

        // 5) Valida firma y confirma
        if ($this->isPaymentSuccessful(request())) {
            $this->confirmPayment();
            $this->success = true;
        } else {
            $this->success = false;
            Log::warning("Signature for payment {$this->payment->order_code} is not valid");
        }

        // 6) Devuelve el XML de respuesta
        return $this->createResponseXML();
    }

    /**
     * Comprueba la firma de la notificación SOAP.
     */
    public function isPaymentSuccessful(Request $request): bool
    {
        $calculated = $this->redsys_lib
            ->createMerchantSignatureNotifSOAPRequest(
                $this->tpv_config->sermepaMerchantKey,
                $this->xml
            );

        if (!preg_match('/<Signature.*?>(.*?)<\/Signature>/', $this->xml, $m)) {
            return false;
        }
        $received = $m[1];

        return $calculated === $received;
    }

    /**
     * Devuelve la notificación parseada (para debug si falla).
     */
    public function getJsonResponse(): mixed
    {
        try {
            $plain = $this->redsys_lib->getRequestNotifSOAP($this->xml);
            return simplexml_load_string(htmlspecialchars_decode($plain));
        } catch (\Throwable $e) {
            Log::error("Error parsing SOAP request XML: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Rellena $this->payment buscándolo por Ds_Order en el XML.
     */
    protected function setPaymentFromRequest(Request $request = null): Payment
    {
        if (!$this->payment) {
            $dsOrder = $this->redsys_lib->getOrderNotifSOAP($this->xml);
            $this->payment = Payment::where('order_code', $dsOrder)
                ->firstOrFail();
        }
        return $this->payment;
    }

    /**
     * Marca como confirmado, gestiona duplicados y dispara el job.
     */
    public function confirmPayment(): void
    {
        $payment = $this->payment;
        $cart = $payment->cart;

        // --- lógica de duplicados tal cual tenías ---
        $duplicated = Cart::where('brand_id', $cart->brand_id)
            ->whereNotNull('confirmation_code')
            ->where('confirmation_code', 'NOT LIKE', 'XXXXXXXXX%')
            ->where('id', '!=', $cart->id)
            ->whereHas('allInscriptions', function ($q) use ($cart) {
                $q->where(function ($q) use ($cart) {
                    foreach ($cart->allInscriptions as $insc) {
                        $q->orWhere(
                            fn($q2) =>
                            $q2->whereNotNull('slot_id')
                                ->where('session_id', $insc->session_id)
                                ->where('slot_id', $insc->slot_id)
                        );
                    }
                });
            })
            ->first();

        if ($duplicated) {
            Log::error("Carrito duplicado #{$cart->id}");
            try {
                $mailer = (new \App\Services\MailerBrandService($cart->brand->code_name))
                    ->getMailer();
                $mailer->to($cart->client->email)
                    ->send(new \App\Mail\ErrorDuplicate($payment, $cart, $duplicated));
            } catch (\Throwable $e) {
                Log::error("Error enviando mail duplicado: {$e->getMessage()}");
            }
            return;
        }

        // --- confirmación normal ---
        $cart->confirmation_code = $payment->order_code;
        $cart->save();

        $payment->paid_at = now();
        $payment->gateway = $this->gateway_code;
        $payment->gateway_response = json_encode($this->getJsonResponse());
        $payment->save();

        // --- dispatch Job ---
        \App\Jobs\CartConfirm::dispatch(
            $cart,
            ['pdf' => config('base.inscription.ticket-web-params')]
        );
    }

    /**
     * Construye el XML de respuesta para Redsys SOAP.
     */
    private function createResponseXML(): string
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $message = $dom->createElement('Message');
        $response = $dom->createElement('Response');
        $response->setAttribute('Ds_Version', '0.0');
        $response->appendChild(
            $dom->createElement('Ds_Response_Merchant', $this->success ? 'OK' : 'KO')
        );
        $message->appendChild($response);
        $dom->appendChild($message);

        $datos = $dom->saveXML($dom->documentElement);

        $signature = $this->redsys_lib
            ->createMerchantSignatureNotifSOAPResponse(
                $this->tpv_config->sermepaMerchantKey,
                $datos,
                $this->payment->order_code
            );

        $message->appendChild($dom->createElement('Signature', $signature));

        return $dom->saveXML($dom->documentElement);
    }

    /**
     * Devuelve el nombre del gateway (para el callback URL).
     */
    public function getName(): string
    {
        return $this->gateway_code;
    }
}
