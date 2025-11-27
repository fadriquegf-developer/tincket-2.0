<?php

namespace App\Services\Payment\Impl;

use App\Models\Tpv;
use App\Models\Cart;
use Omnipay\Omnipay;
use App\Models\Payment;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        // ✅ Cargar inscripciones con sus sesiones, deshabilitando BrandScope
        $cart->load([
            'allInscriptions' => function ($q) {
                $q->withoutGlobalScope(\App\Scopes\BrandScope::class)
                    ->with([
                        'session' => function ($sq) {
                            $sq->withoutGlobalScope(\App\Scopes\BrandScope::class);
                        }
                    ]);
            }
        ]);

        // Buscar TPV en inscripciones
        $tpvId = $cart->allInscriptions
            ->pluck('session.tpv_id')
            ->filter()
            ->unique()
            ->first();

        // Si no hay inscripciones, buscar en gift cards
        if (!$tpvId) {
            // ✅ Cargar gift_cards con eventos y sesiones, deshabilitando BrandScope
            $cart->load([
                'gift_cards' => function ($q) {
                    $q->withoutGlobalScope(\App\Scopes\BrandScope::class)
                        ->with([
                            'event' => function ($eq) {
                                $eq->withoutGlobalScope(\App\Scopes\BrandScope::class)
                                    ->with([
                                        'sessions' => function ($sq) {
                                            $sq->withoutGlobalScope(\App\Scopes\BrandScope::class);
                                        }
                                    ]);
                            }
                        ]);
                }
            ]);

            $tpvId = $cart->gift_cards
                ->flatMap(fn($gc) => optional($gc->event?->sessions)->pluck('tpv_id') ?? collect())
                ->filter()
                ->unique()
                ->first();
        }

        if (!$tpvId) {
            // ✅ LOG 6: ERROR - No se encontró TPV
            \Log::error('❌ No se encontró TPV válido', [
                'cart_id' => $cart->id,
                'inscriptions_count' => $cart->allInscriptions->count(),
                'gift_cards_count' => $cart->gift_cards->count()
            ]);

            throw new \RuntimeException('No se pudo encontrar un TPV válido para el carrito');
        }

        // ✅ Buscar TPV deshabilitando BrandScope (por si acaso)
        $this->tpv = Tpv::withoutGlobalScope(\App\Scopes\BrandScope::class)
            ->findOrFail($tpvId);

        $this->payment->update([
            'tpv_id' => $this->tpv->id,
            'tpv_name' => $this->tpv->name,
        ]);

        $raw = $this->tpv->config;
        $this->cfg = new TpvConfigurationDecoder($raw);
    }

    public function initGateway(): void
    {
        if ($this->gateway) {
            return;
        }

        $this->loadTpvConfiguration();

        $this->gateway = Omnipay::create(self::OMNIPAY_DRIVER);

        $this->gateway->setMerchantId($this->cfg->get('sermepaMerchantCode'));
        $this->gateway->setTerminalId($this->cfg->get('sermepaTerminal', '001'));
        $this->gateway->setHmacKey($this->cfg->get('sermepaMerchantKey'));
        $this->gateway->setMerchantName($this->cfg->get('sermepaMerchantName', ''));
        $this->gateway->setTestMode((bool) $this->cfg->get('sermepaTestMode', 0));
    }

    public function getData(Payment $payment = null): array
    {
        if ($payment) {
            $this->payment = $payment;
        } elseif (!$this->payment) {
            throw new \InvalidArgumentException('Debes proporcionar un Payment a getData()');
        }

        $this->initGateway();
        $cart = $this->payment->cart;

        // URLs
        $notifyUrl = route('api1.payment.callback', [
            'gateway' => $this->gatewayAlias,
        ]);

        $returnUrl = str_replace(
            ['{token}', '{locale}'],
            [$cart->token, $cart->client->locale],
            $this->cfg->get('sermepaUrlOK')
        );

        $cancelUrl = str_replace(
            ['{id}', '{locale}'],
            [$cart->id, $cart->client->locale],
            $this->cfg->get('sermepaUrlKO')
        );

        // Crear el request usando el método del gateway
        $request = $this->gateway->purchase([
            'amount' => sprintf('%.2f', $cart->price_sold),
            'currency' => 'EUR',
            'transactionId' => $this->payment->order_code,
            'description' => $this->getDescription($this->payment),
            'notifyUrl' => $notifyUrl,
            'returnUrl' => $returnUrl, // Esto se usará para ambas URLs por el bug de la librería
            'consumerLanguage' => $this->mapLanguageCode($cart->client->locale),
        ]);

        // Obtener los datos antes de enviar
        $data = $request->getData();

        // CORRECCIÓN: Sobrescribir manualmente Ds_Merchant_UrlKO con la URL correcta
        $data['Ds_Merchant_UrlKO'] = $cancelUrl;

        // Enviar los datos modificados
        $response = $request->sendData($data);

        $redirectUrl = $response->getRedirectUrl();
        $redirectData = $response->getRedirectData();

        // Verificar el resultado
        if (isset($redirectData['Ds_MerchantParameters'])) {
            $decodedParams = json_decode(base64_decode($redirectData['Ds_MerchantParameters']), true);

            if ($decodedParams['Ds_Merchant_UrlKO'] === $decodedParams['Ds_Merchant_UrlOK']) {
                Log::error('CRITICAL: UrlKO and UrlOK are still identical!');
            }

            if (empty($decodedParams['Ds_Merchant_MerchantCode'])) {
                Log::error('CRITICAL: MerchantCode is empty!');
            }
        }

        return [
            'url' => $redirectUrl,
            'params' => $redirectData,
            'platform' => $this->gateway->getName(),
        ];
    }

    private function mapLanguageCode($locale): string
    {
        $languageMap = [
            'es' => '001',
            'ca' => '003',
            'en' => '002',
            'fr' => '004',
            'de' => '005',
            'nl' => '006',
            'it' => '007',
            'sv' => '008',
            'pt' => '009',
            'pl' => '011',
            'gl' => '012',
            'eu' => '013',
        ];

        return $languageMap[$locale] ?? '001';
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

        if (!$req->filled('Ds_MerchantParameters')) {
            Log::error('Ds_MerchantParameters no encontrado en request');
            return null;
        }

        $merchantParams = json_decode(base64_decode($req->input('Ds_MerchantParameters')), true);
        $orderCode = $merchantParams['Ds_Order'] ?? null;

        if (!$orderCode) {
            Log::error('Ds_Order no encontrado tras decodificar Ds_MerchantParameters');
            return null;
        }

        $this->payment = Payment::where('order_code', $orderCode)->first();

        if (!$this->payment) {
            Log::error('Payment no encontrado en setPaymentFromRequest', ['order_code' => $orderCode]);
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
            Log::error('Redsys callback InvalidResponseException', ['message' => $e->getMessage()]);
            return false;
        } catch (\Exception $e) {
            Log::error('Error general en Redsys callback', ['message' => $e->getMessage()]);
            return false;
        }
    }

    public function confirmPayment(): void
    {
        parent::confirmPayment();
    }

    /**
     * Genera la descripción del pago para Redsys
     * 
     * @param Payment $payment
     * @return string
     */
    protected function getDescription(Payment $payment): string
    {
        $parts = [];

        // ✅ Cargar relaciones necesarias con BrandScope deshabilitado
        $payment->cart->load([
            'allInscriptions' => function ($q) {  // ← CAMBIO: usar allInscriptions
                $q->withoutGlobalScope(\App\Scopes\BrandScope::class)
                    ->with([
                        'session' => function ($sq) {
                        $sq->withoutGlobalScope(\App\Scopes\BrandScope::class)
                            ->with([
                                'event' => function ($eq) {  // ← Cargar event dentro de session
                                    $eq->withoutGlobalScope(\App\Scopes\BrandScope::class);
                                }
                            ]);
                    }
                    ]);
            },
            'groupPacks.pack' => function ($q) {
                $q->withoutGlobalScope(\App\Scopes\BrandScope::class);
            },
            'gift_cards' => function ($q) {
                $q->withoutGlobalScope(\App\Scopes\BrandScope::class);
            }
        ]);

        // ✅ Usar allInscriptions y verificar que session y event existen
        foreach ($payment->cart->allInscriptions as $ins) {
            if ($ins->session && $ins->session->event) {
                $parts[] = Str::limit("{$ins->session->event->id}.{$ins->session->event->name}", 15);
            }
        }

        foreach ($payment->cart->groupPacks as $gp) {
            if ($gp->pack) {
                $parts[] = Str::limit("P{$gp->pack->id}.{$gp->pack->name}", 25);
            }
        }

        foreach ($payment->cart->gift_cards as $gc) {
            $parts[] = Str::limit("G{$gc->id}.GiftCard", 25);
        }

        return Str::limit(implode('|', array_unique($parts)), 120, '(...)');
    }
}
