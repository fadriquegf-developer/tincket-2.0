<?php

/**
 * ════════════════════════════════════════════════════════════════════════════
 * PaymentRedsysSoapService - VERSIÓN CORREGIDA
 * ════════════════════════════════════════════════════════════════════════════
 * 
 * CAMBIO PRINCIPAL: Se elimina el método confirmPayment() duplicado.
 * Ahora usa el del padre (AbstractPaymentService) que tiene la protección
 * contra race conditions.
 * 
 * ¿Por qué?
 * - El confirmPayment() que tenía era código duplicado
 * - Hacía exactamente lo mismo que el padre
 * - Tenía un bug: no llamaba a confirmedPayment() al final
 * - Ahora hereda la protección con PaymentSlotLockService
 */

namespace App\Services\Payment\Impl;

use DOMDocument;
use App\Models\Cart;
use App\Models\Payment;
use BadMethodCallException;
use Illuminate\Http\Request;
use App\Services\MailerService;
use Illuminate\Support\Facades\Log;
use App\Services\Payment\Lib\RedsysAPI;
use App\Services\TpvConfigurationDecoder;
use Omnipay\Common\Message\AbstractResponse;
use App\Services\Payment\AbstractPaymentService;

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
        $this->loadTpvConfiguration();

        // 5) Valida firma y confirma
        if ($this->isPaymentSuccessful(request())) {
            // ════════════════════════════════════════════════════════════════
            // CAMBIO: Ahora usa el confirmPayment() del padre
            // que tiene la protección contra race conditions
            // ════════════════════════════════════════════════════════════════
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
     * Carga la configuración del TPV desde el payment
     */
    protected function loadTpvConfiguration(): void
    {
        if (!$this->payment->tpv_id) {
            // Cargar inscripciones con sesiones, deshabilitando BrandScope
            $this->payment->cart->load(['allInscriptions' => function ($q) {
                $q->withoutGlobalScope(\App\Scopes\BrandScope::class)
                    ->with(['session' => function ($sq) {
                        $sq->withoutGlobalScope(\App\Scopes\BrandScope::class);
                    }]);
            }]);

            $tpvId = $this->payment->cart->allInscriptions
                ->pluck('session.tpv_id')
                ->filter()
                ->first();

            if ($tpvId) {
                $this->payment->update(['tpv_id' => $tpvId]);
                $this->payment->load('tpv');
            }
        }

        if (!$this->payment->relationLoaded('tpv')) {
            $this->payment->load('tpv');
        }

        if (!$this->payment->tpv) {
            throw new \RuntimeException('TPV no encontrado para payment ID: ' . $this->payment->id);
        }

        $this->tpv_config = new TpvConfigurationDecoder($this->payment->tpv->config);
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
                ->with(['cart.brand', 'cart.allInscriptions.session', 'tpv'])
                ->firstOrFail();
        }
        return $this->payment;
    }

    // ════════════════════════════════════════════════════════════════════════
    // ELIMINADO: confirmPayment()
    // ════════════════════════════════════════════════════════════════════════
    // 
    // El método confirmPayment() que estaba aquí era código duplicado del padre.
    // Ahora usa el de AbstractPaymentService que tiene:
    //   - Lock distribuido para evitar callbacks simultáneos
    //   - Marcado de pagos para reembolso
    //   - Logs mejorados
    //   - Liberación de locks de Redis
    // ════════════════════════════════════════════════════════════════════════

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
     * Devuelve el nombre del gateway.
     * 
     * NOTA: Este método es necesario porque el padre llama a
     * $this->getGateway()->getName() y en este servicio $this->gateway = $this
     */
    public function getName(): string
    {
        return $this->gateway_code;
    }

    /**
     * Getter para el config decoder (usado por PaymentApiController)
     */
    public function getConfigDecoder(): ?TpvConfigurationDecoder
    {
        return $this->tpv_config ?? null;
    }
}
