<?php

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

    /** Datos parseados del XML de Redsys */
    protected array $redsysData = [];

    /**
     * Mapeo de códigos de respuesta Redsys a mensajes legibles
     */
    protected const RESPONSE_CODES = [
        '0000' => 'Transacción autorizada',
        '0001' => 'Transacción autorizada previa identificación titular',
        '0099' => 'Operación autorizada',
        '0101' => 'Tarjeta caducada',
        '0102' => 'Tarjeta en excepción transitoria o bajo sospecha de fraude',
        '0104' => 'Operación no permitida para esa tarjeta o terminal',
        '0106' => 'Intentos de PIN excedidos',
        '0107' => 'Contactar con el emisor',
        '0109' => 'Comercio no válido',
        '0110' => 'Importe inválido',
        '0116' => 'Disponible insuficiente',
        '0118' => 'Tarjeta no registrada',
        '0125' => 'Tarjeta no efectiva',
        '0129' => 'Código de seguridad (CVV2/CVC2) incorrecto',
        '0180' => 'Tarjeta fuera de servicio',
        '0184' => 'Error en la autenticación del titular',
        '0190' => 'Denegación del emisor sin especificar motivo',
        '0191' => 'Fecha de caducidad errónea',
        '0195' => 'Requiere autenticación SCA',
        '0904' => 'Comercio no registrado en FUC',
        '0909' => 'Error de sistema',
        '0912' => 'Emisor no disponible',
        '0913' => 'Pedido repetido',
        '0944' => 'Sesión incorrecta',
        '0950' => 'Operación de devolución no permitida',
        '9064' => 'Número de posiciones del CVV2 incorrecto',
        '9078' => 'No existe método de pago válido para esa tarjeta',
        '9093' => 'Tarjeta no existente',
        '9104' => 'Operación no permitida para ese comercio con esa tarjeta',
        '9915' => 'Cancelado por el usuario',
        '9997' => 'Transacción simultánea',
        '9999' => 'Operación redirigida al emisor a autenticar',
    ];

    /**
     * Mapeo de códigos de error SIS
     */
    protected const SIS_ERROR_CODES = [
        'SIS0051' => 'Pedido repetido',
        'SIS0054' => 'Pedido erróneo',
        'SIS0057' => 'Importe no coincide',
        'SIS0075' => 'Error en el número de pedido',
        'SIS0078' => 'Método de pago no disponible',
        'SIS0093' => 'Tarjeta no válida',
        'SIS0094' => 'Error en la llamada al MPI',
        'SIS0216' => 'Error CVV2 en operación con tarjeta',
        'SIS0217' => 'Error en fecha de caducidad de tarjeta',
        'SIS0256' => 'Error genérico',
        'SIS0261' => 'Operación detenida por superar el control de restricciones',
        'SIS0431' => 'Error al verificar la firma',
    ];

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

        // 2) Parsear datos del XML para logging
        $this->parseRedsysData();

        // 3) Log inicial con datos recibidos
        Log::info('📥 SOAP Redsys: Notificación recibida', [
            'order_code' => $this->redsysData['Ds_Order'] ?? 'N/A',
            'ds_response' => $this->redsysData['Ds_Response'] ?? 'N/A',
            'ds_amount' => $this->redsysData['Ds_Amount'] ?? 'N/A',
        ]);

        // 4) Carga el Payment
        $this->setPaymentFromRequest();

        // 5) Carga config de marca
        (new \App\Http\Middleware\CheckBrandHost())
            ->loadBrandConfig($this->payment->cart->brand->code_name);

        // 6) Carga TPV
        $this->loadTpvConfiguration();

        // 7) Verificar firma
        if (!$this->isSignatureValid()) {
            Log::error('❌ SOAP Redsys: Firma inválida', [
                'order_code' => $this->payment->order_code,
                'cart_id' => $this->payment->cart_id,
            ]);
            $this->success = false;
            return $this->createResponseXML();
        }

        // 8) Verificar código de respuesta Ds_Response
        $responseCode = $this->redsysData['Ds_Response'] ?? null;

        if (!$this->isResponseCodeSuccessful($responseCode)) {
            $this->logPaymentError($responseCode);
            $this->success = false;
            return $this->createResponseXML();
        }

        // 9) Todo OK - Confirmar pago
        Log::info('✅ SOAP Redsys: Pago autorizado', [
            'order_code' => $this->payment->order_code,
            'cart_id' => $this->payment->cart_id,
            'ds_response' => $responseCode,
            'ds_authorisation_code' => $this->redsysData['Ds_AuthorisationCode'] ?? 'N/A',
        ]);

        // ════════════════════════════════════════════════════════════════
        // FIX: Inicializar gateway antes de confirmar
        // ════════════════════════════════════════════════════════════════
        $this->initGateway();

        try {
            $this->confirmPayment();
            $this->success = true;

            Log::info('✅ SOAP Redsys: Carrito confirmado correctamente', [
                'order_code' => $this->payment->order_code,
                'cart_id' => $this->payment->cart_id,
                'confirmation_code' => $this->payment->cart->confirmation_code,
            ]);
        } catch (\Throwable $e) {
            Log::error('❌ SOAP Redsys: ERROR al confirmar pago', [
                'order_code' => $this->payment->order_code,
                'cart_id' => $this->payment->cart_id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->success = false;
        }

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
     * Comprueba solo la firma de la notificación SOAP.
     */
    protected function isSignatureValid(): bool
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
     * Método público para compatibilidad - verifica firma Y respuesta
     */
    public function isPaymentSuccessful(Request $request): bool
    {
        if (!$this->isSignatureValid()) {
            return false;
        }

        $responseCode = $this->redsysData['Ds_Response'] ?? null;
        return $this->isResponseCodeSuccessful($responseCode);
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
            $dsOrder = $this->redsysData['Ds_Order']
                ?? $this->redsys_lib->getOrderNotifSOAP($this->xml);

            $this->payment = Payment::where('order_code', $dsOrder)
                ->with(['cart.brand', 'cart.client', 'cart.allInscriptions.session', 'tpv'])
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

    /**
     * Parsear los datos del XML de Redsys
     */
    protected function parseRedsysData(): void
    {
        $this->redsysData = [];

        $fields = [
            'Ds_Amount',
            'Ds_Currency',
            'Ds_Order',
            'Ds_MerchantCode',
            'Ds_Terminal',
            'Ds_Response',
            'Ds_AuthorisationCode',
            'Ds_TransactionType',
            'Ds_SecurePayment',
            'Ds_Language',
            'Ds_Card_Country',
            'Ds_Card_Brand',
            'Ds_ErrorCode',
            'Ds_ProcessedPayMethod',
        ];

        foreach ($fields as $field) {
            $value = $this->extractXmlField($field);
            if ($value !== null) {
                $this->redsysData[$field] = $value;
            }
        }
    }

    /**
     * Extraer un campo del XML
     */
    protected function extractXmlField(string $fieldName): ?string
    {
        $pattern = "/<{$fieldName}>(.*?)<\/{$fieldName}>/";
        if (preg_match($pattern, $this->xml, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    /**
     * Verificar si el código de respuesta indica éxito
     * Códigos 0000-0099 son transacciones autorizadas
     */
    protected function isResponseCodeSuccessful(?string $responseCode): bool
    {
        if ($responseCode === null) {
            return false;
        }

        $code = str_pad($responseCode, 4, '0', STR_PAD_LEFT);
        $numericCode = intval($code);

        return $numericCode >= 0 && $numericCode <= 99;
    }

    /**
     * Obtener nombre legible de la marca de tarjeta
     */
    protected function getCardBrandName(?string $brandCode): string
    {
        $brands = [
            '1' => 'VISA',
            '2' => 'MasterCard',
            '6' => 'Diners Club',
            '8' => 'American Express',
            '9' => 'JCB',
            '22' => 'UPI',
            '112' => 'Bizum',
        ];

        return $brands[$brandCode] ?? "Desconocida ({$brandCode})";
    }

    /**
     * Loggear error de pago con información detallada
     */
    protected function logPaymentError(?string $responseCode): void
    {
        $responseCode = $responseCode ? str_pad($responseCode, 4, '0', STR_PAD_LEFT) : 'N/A';
        $errorCode = $this->redsysData['Ds_ErrorCode'] ?? null;

        $responseMessage = self::RESPONSE_CODES[$responseCode] ?? 'Código de respuesta desconocido';
        $errorMessage = $errorCode ? (self::SIS_ERROR_CODES[$errorCode] ?? 'Error SIS desconocido') : null;

        Log::error('❌ SOAP Redsys: Pago DENEGADO', [
            'order_code' => $this->payment->order_code,
            'cart_id' => $this->payment->cart_id,
            'brand' => $this->payment->cart->brand->name ?? 'N/A',
            'client_email' => $this->payment->cart->client->email ?? 'N/A',
            'ds_response' => $responseCode,
            'ds_response_message' => $responseMessage,
            'ds_error_code' => $errorCode,
            'ds_error_message' => $errorMessage,
            'ds_amount' => $this->redsysData['Ds_Amount'] ?? 'N/A',
            'ds_card_brand' => $this->getCardBrandName($this->redsysData['Ds_Card_Brand'] ?? null),
        ]);
    }
}
