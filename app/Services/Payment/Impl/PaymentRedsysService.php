<?php
// app/Services/Payment/Impl/PaymentRedsysService.php

namespace App\Services\Payment\Impl;

use App\Models\Tpv;
use App\Models\Cart;
use Omnipay\Omnipay;
use App\Models\Payment;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Omnipay\Redsys\Message\Security;
use App\Services\TpvConfigurationDecoder;
use Omnipay\Common\Message\AbstractResponse;
use App\Services\Payment\AbstractPaymentService;
use Omnipay\Common\Exception\InvalidResponseException;

class PaymentRedsysService extends AbstractPaymentService
{
    protected const OMNIPAY_DRIVER = 'Redsys_Redirect';
    protected AbstractResponse $response;
    protected ?Tpv $tpv = null;
    protected ?TpvConfigurationDecoder $cfg = null;
    protected string $gatewayAlias;

    public function __construct(string $gatewayAlias = 'Redsys_Redirect')
    {
        $this->gatewayAlias = $gatewayAlias;
        parent::__construct($gatewayAlias);
    }

    protected function loadTpvConfiguration(): void
    {
        $cart = $this->payment?->cart ?? $this->cart;
        if (!$cart) {
            throw new \RuntimeException('Cart no seteado');
        }

        $tpvId = $cart->allInscriptions
            ->pluck('session.tpv_id')
            ->filter()
            ->unique()
            ->sole();

        $this->tpv = Tpv::findOrFail($tpvId);
        $this->payment->update([
            'tpv_id' => $this->tpv->id,
            'tpv_name' => $this->tpv->name,
        ]);

        // 1) obtenemos el JSON
        $raw = $this->tpv->config;

        // 2) si viene envuelto en {"config":[…]}, extraemos sólo el array
        if (isset($raw['config']) && is_array($raw['config'])) {
            $raw = $raw['config'];
        }

        Log::info('TPV config used:', ['config' => $raw]);

        // 3) inicializamos el decoder con el array de {key,value}
        $this->cfg = new TpvConfigurationDecoder($raw);
    }

    public function initGateway(): void
    {
        if ($this->gateway) {
            return;
        }

        $this->loadTpvConfiguration();

        $this->gateway = Omnipay::create(self::OMNIPAY_DRIVER);
        $this->gateway->initialize([
            'merchantId' => $this->cfg->get('sermepaMerchantCode'),
            'terminalId' => $this->cfg->get('sermepaTerminal', '001'),
            'hmacKey' => $this->cfg->get('sermepaMerchantKey'),
            'merchantName' => $this->cfg->get('sermepaMerchantName', ''),
            'testMode' => (bool) $this->cfg->get('sermepaTestMode', 0),
        ]);
    }

    /**
     * 1) Prepara el purchase
     * 2) Lo envía (firma datos)
     * 3) Devuelve URL + params (signature) para el form
     */

    public function getData(Payment $payment = null): array
    {
        if ($payment) {
            $this->payment = $payment;
        } elseif (!$this->payment) {
            throw new \InvalidArgumentException('Debes proporcionar un Payment a getData()');
        }

        $this->initGateway();
        $cart = $this->payment->cart;

        $notifyUrl = route('api1.payment.callback', [
            'gateway' => $this->gatewayAlias,
        ]);
        \Log::info('REDSYS notifyUrl', ['url' => $notifyUrl, 'gateway' => $this->gateway->getName()]);
        $returnUrl = str_replace(
            ['{token}', '{locale}'],
            [$cart->token, $cart->client->locale],
            $this->cfg->get('sermepaUrlOK')
        );

        $purchase = $this->gateway->purchase([
            /* credenciales */
            'merchantId' => $this->cfg->get('sermepaMerchantCode'),
            'terminalId' => $this->cfg->get('sermepaTerminal', '001'),
            'hmacKey' => $this->cfg->get('sermepaMerchantKey'),

            /* operación */
            'amount' => number_format($cart->price_sold, 2, '.', ''),
            'multiply' => true,
            'currency' => 'EUR',
            'transactionId' => $this->payment->order_code,
            'transactionType' => '0',
            'description' => $this->getDescription($this->payment),
            'merchantName' => $this->cfg->get('sermepaMerchantName', ''),

            /* idioma: ISO 639-1 o código interno */
            'consumerLanguage' => $cart->client->locale ?: 'es',

            /* URLs */
            'notifyUrl' => $notifyUrl,
            'returnUrl' => $returnUrl,
        ]);

        /** @var \Omnipay\Redsys\Message\PurchaseResponse $response */
        $response = $purchase->send();
        $redirectUrl = $response->getRedirectUrl();
        $redirectData = $response->getRedirectData();

        Log::info('REDSYS_POST', $redirectData);
        Log::info('REDSYS_URL ', ['url' => $redirectUrl]);

        return [
            'url' => $redirectUrl,
            'params' => $redirectData,
        ];
    }

    public function getConfigDecoder(): TpvConfigurationDecoder|null
    {
        if (!$this->cfg) {
            $this->initGateway();
        }
        return $this->cfg;
    }

    public function getJsonResponse(): mixed
    {
        return $this->response->getData();
    }

    protected function setPaymentFromRequest(Request $request = null): ?Payment
    {
        $req = $request ?? request();

        // Decodificar Ds_MerchantParameters correctamente
        if (!$req->filled('Ds_MerchantParameters')) {
            Log::error('❌ Ds_MerchantParameters no encontrado en request');
            return null;
        }

        $merchantParams = json_decode(base64_decode($req->input('Ds_MerchantParameters')), true);

        $orderCode = $merchantParams['Ds_Order'] ?? null;

        if (!$orderCode) {
            Log::error('❌ Ds_Order no encontrado tras decodificar Ds_MerchantParameters');
            return null;
        }

        Log::info('🔍 Buscando Payment por order_code', ['order_code' => $orderCode]);

        $this->payment = Payment::where('order_code', $orderCode)->first();

        if (!$this->payment) {
            Log::error('❌ Payment no encontrado en setPaymentFromRequest', ['order_code' => $orderCode]);
            return null;
        }

        return $this->payment;
    }

    public function isPaymentSuccessful(Request $request): bool
    {
        try {
            if (!$this->setPaymentFromRequest($request)) {
                return false;
            }

            $this->initGateway();

            $this->response = $this->gateway
                ->completePurchase(['request' => $request->all()])
                ->send();

            return $this->response->isSuccessful();
        } catch (InvalidResponseException $e) {
            Log::error('❌ Redsys callback InvalidResponseException', ['message' => $e->getMessage()]);
            return false;
        } catch (\Exception $e) {
            Log::error('❌ Error general en Redsys callback', ['message' => $e->getMessage()]);
            return false;
        }
    }

    public function confirmPayment(): void
    {
        if (!$this->payment) {
            Log::error('❌ No hay payment seteado en confirmPayment');
            return;
        }

        if (!$this->response) {
            Log::error('❌ No hay respuesta de Redsys en confirmPayment');
            return;
        }

        $payment = $this->payment;
        $cart = $payment->cart;

        if ($this->response->isSuccessful()) {
            $cart->update(['confirmation_code' => $payment->order_code]);
            $payment->update([
                'paid_at' => now(),
                'gateway' => $this->gateway->getName(),
                'gateway_response' => json_encode($this->response->getData()),
            ]);

            Log::info('✅ Pago confirmado correctamente', ['order_code' => $payment->order_code]);

            \App\Jobs\CartConfirm::dispatch(
                $cart,
                ['pdf' => config('base.inscription.ticket-web-params')]
            );
        } else {
            Log::warning('⚠️ Redsys devolvió respuesta no exitosa', ['order_code' => $payment->order_code]);
        }
    }

    protected function getDescription(Payment $payment): string
    {
        $parts = [];
        foreach ($payment->cart->inscriptions as $ins) {
            $parts[] = Str::limit("{$ins->session->event->id}.{$ins->session->event->name}", 15);
        }
        foreach ($payment->cart->groupPacks as $gp) {
            $parts[] = Str::limit("P{$gp->pack->id}.{$gp->pack->name}", 25);
        }
        return Str::limit(implode('|', array_unique($parts)), 120, '(...)');
    }
}
