<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Tpv;
use App\Services\TpvConfigurationDecoder;
use App\Services\Payment\Lib\RedsysAPI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * RedsysRefundService
 * ════════════════════════════════════════════════════════════════════════════
 * 
 * Servicio para procesar devoluciones/reembolsos a través de Redsys.
 * 
 * REQUISITOS DE REDSYS:
 * ---------------------
 * - La operación original debe estar confirmada (pagada)
 * - Se usa Ds_Merchant_TransactionType = 3 (Devolución)
 * - El importe puede ser total o parcial
 * - Se necesita el Ds_Order original de la operación a devolver
 * 
 * IMPORTANTE:
 * -----------
 * Para usar devoluciones automáticas, el comercio debe tener habilitada
 * esta funcionalidad en su contrato con Redsys/banco.
 * 
 * ════════════════════════════════════════════════════════════════════════════
 */
class RedsysRefundService
{
    /** URL del endpoint de Redsys para operaciones REST */
    private const REDSYS_URL_PRODUCTION = 'https://sis.redsys.es/sis/rest/trataPeticionREST';
    private const REDSYS_URL_TEST = 'https://sis-t.redsys.es:25443/sis/rest/trataPeticionREST';

    /** Tipo de transacción para devolución */
    private const TRANSACTION_TYPE_REFUND = '3';

    /** @var RedsysAPI */
    private RedsysAPI $redsysLib;

    /** @var TpvConfigurationDecoder|null */
    private ?TpvConfigurationDecoder $tpvConfig = null;

    /** @var Tpv|null */
    private ?Tpv $tpv = null;

    /** @var bool */
    private bool $testMode = false;

    public function __construct()
    {
        $this->redsysLib = new RedsysAPI();
    }

    /**
     * Procesar una devolución para un pago
     * 
     * @param Payment $payment El pago a devolver
     * @param int|null $amountCents Importe a devolver en céntimos (null = total)
     * @param string $reason Motivo de la devolución
     * @return array Resultado de la operación
     */
    public function processRefund(Payment $payment, ?int $amountCents = null, string $reason = 'customer_request'): array
    {
        // ─────────────────────────────────────────────────────────────────────
        // PASO 1: Validaciones previas
        // ─────────────────────────────────────────────────────────────────────

        if (!$payment->paid_at) {
            return $this->errorResponse('El pago no está confirmado', 'PAYMENT_NOT_CONFIRMED');
        }

        if ($payment->isRefunded()) {
            return $this->errorResponse('El pago ya fue reembolsado', 'ALREADY_REFUNDED');
        }

        // Verificar que el gateway es Redsys
        $validGateways = ['Sermepa', 'Redsys', 'Redsys Redirect', 'SermepaSoapService', 'RedsysSoapService'];
        if (!in_array($payment->gateway, $validGateways)) {
            return $this->errorResponse(
                "El gateway '{$payment->gateway}' no soporta devoluciones automáticas",
                'GATEWAY_NOT_SUPPORTED'
            );
        }

        // Cargar el TPV
        if (!$this->loadTpvConfiguration($payment)) {
            return $this->errorResponse('No se pudo cargar la configuración del TPV', 'TPV_CONFIG_ERROR');
        }

        // Calcular importe
        // NOTA: Cart usa priceSold (accessor), no total
        $cart = $payment->cart;
        $totalCents = (int) round($cart->priceSold * 100);
        $refundCents = $amountCents ?? $totalCents;

        if ($refundCents <= 0) {
            return $this->errorResponse('El importe a devolver debe ser mayor que 0', 'INVALID_AMOUNT');
        }

        if ($refundCents > $totalCents) {
            return $this->errorResponse(
                "El importe a devolver ({$refundCents}) supera el total del pago ({$totalCents})",
                'AMOUNT_EXCEEDS_TOTAL'
            );
        }

        // ─────────────────────────────────────────────────────────────────────
        // PASO 2: Preparar la petición de devolución
        // ─────────────────────────────────────────────────────────────────────

        $orderCode = $payment->order_code;
        $merchantCode = $this->tpvConfig->get('sermepaMerchantCode');
        $terminal = $this->tpvConfig->get('sermepaTerminal', '001');
        $merchantKey = $this->tpvConfig->get('sermepaMerchantKey');
        $currency = '978'; // EUR

        // Configurar los parámetros de la operación
        $this->redsysLib->setParameter('DS_MERCHANT_AMOUNT', $refundCents);
        $this->redsysLib->setParameter('DS_MERCHANT_ORDER', $orderCode);
        $this->redsysLib->setParameter('DS_MERCHANT_MERCHANTCODE', $merchantCode);
        $this->redsysLib->setParameter('DS_MERCHANT_CURRENCY', $currency);
        $this->redsysLib->setParameter('DS_MERCHANT_TRANSACTIONTYPE', self::TRANSACTION_TYPE_REFUND);
        $this->redsysLib->setParameter('DS_MERCHANT_TERMINAL', $terminal);

        // Generar parámetros codificados y firma
        $params = $this->redsysLib->createMerchantParameters();
        $signature = $this->redsysLib->createMerchantSignature($merchantKey);

        Log::info('Redsys Refund: Preparando petición', [
            'payment_id' => $payment->id,
            'order_code' => $orderCode,
            'amount_cents' => $refundCents,
            'reason' => $reason,
            'test_mode' => $this->testMode,
        ]);

        // ─────────────────────────────────────────────────────────────────────
        // PASO 3: Enviar petición a Redsys
        // ─────────────────────────────────────────────────────────────────────

        $url = $this->testMode ? self::REDSYS_URL_TEST : self::REDSYS_URL_PRODUCTION;

        try {
            $response = Http::timeout(30)
                ->asForm()
                ->post($url, [
                    'Ds_SignatureVersion' => 'HMAC_SHA256_V1',
                    'Ds_MerchantParameters' => $params,
                    'Ds_Signature' => $signature,
                ]);

            if (!$response->successful()) {
                Log::error('Redsys Refund: Error HTTP', [
                    'payment_id' => $payment->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $this->errorResponse(
                    'Error de comunicación con Redsys: HTTP ' . $response->status(),
                    'HTTP_ERROR'
                );
            }

            // ─────────────────────────────────────────────────────────────────
            // PASO 4: Procesar respuesta
            // ─────────────────────────────────────────────────────────────────

            $responseData = $response->json();

            // Decodificar parámetros de respuesta
            if (!isset($responseData['Ds_MerchantParameters'])) {
                Log::error('Redsys Refund: Respuesta sin Ds_MerchantParameters', [
                    'payment_id' => $payment->id,
                    'response' => $responseData,
                ]);

                return $this->errorResponse('Respuesta de Redsys inválida', 'INVALID_RESPONSE');
            }

            $decodedParams = json_decode(
                base64_decode($responseData['Ds_MerchantParameters']),
                true
            );

            $dsResponse = $decodedParams['Ds_Response'] ?? null;
            $dsAuthorisationCode = $decodedParams['Ds_AuthorisationCode'] ?? null;

            Log::info('Redsys Refund: Respuesta recibida', [
                'payment_id' => $payment->id,
                'ds_response' => $dsResponse,
                'ds_auth_code' => $dsAuthorisationCode,
                'decoded_params' => $decodedParams,
            ]);

            // Verificar si la operación fue exitosa
            // Códigos de éxito según documentación oficial Redsys:
            // - 0000-0099 = Transacción autorizada (pagos y preautorizaciones)
            // - 0400 = Anulación aceptada
            // - 0900 = Devolución/Confirmación aceptada
            $responseCode = (int) $dsResponse;
            $isSuccess = ($responseCode >= 0 && $responseCode <= 99)
                || $responseCode === 400
                || $responseCode === 900;

            if ($isSuccess) {
                // ─────────────────────────────────────────────────────────
                // ÉXITO: Marcar el pago como reembolsado
                // ─────────────────────────────────────────────────────────

                // Si no estaba marcado para refund, marcarlo primero
                if (!$payment->requires_refund) {
                    $payment->markForRefund($reason, [
                        'requested_by' => 'admin',
                        'requested_at' => now()->toIso8601String(),
                    ]);
                }

                // Marcar como reembolsado
                $payment->markAsRefunded($dsAuthorisationCode);

                // Actualizar detalles con info de Redsys
                $details = $payment->refund_details ?? [];
                $details['redsys_response'] = $decodedParams;
                $details['refund_amount_cents'] = $refundCents;
                $details['refund_type'] = $refundCents === $totalCents ? 'full' : 'partial';
                $payment->refund_details = $details;
                $payment->save();

                Log::info('Redsys Refund: Devolución exitosa', [
                    'payment_id' => $payment->id,
                    'auth_code' => $dsAuthorisationCode,
                    'amount_cents' => $refundCents,
                ]);

                return [
                    'success' => true,
                    'message' => 'Devolución procesada correctamente',
                    'refund_reference' => $dsAuthorisationCode,
                    'amount_cents' => $refundCents,
                    'redsys_response' => $decodedParams,
                ];
            } else {
                // ─────────────────────────────────────────────────────────
                // ERROR: La devolución fue rechazada
                // ─────────────────────────────────────────────────────────

                $errorMessage = $this->getRedsysErrorMessage($dsResponse);

                Log::error('Redsys Refund: Devolución rechazada', [
                    'payment_id' => $payment->id,
                    'ds_response' => $dsResponse,
                    'error_message' => $errorMessage,
                ]);

                return $this->errorResponse(
                    "Redsys rechazó la devolución: {$errorMessage} (Código: {$dsResponse})",
                    'REDSYS_REJECTED',
                    ['ds_response' => $dsResponse, 'redsys_params' => $decodedParams]
                );
            }
        } catch (\Exception $e) {
            Log::error('Redsys Refund: Excepción', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Error al procesar la devolución: ' . $e->getMessage(),
                'EXCEPTION'
            );
        }
    }

    /**
     * Cargar configuración del TPV desde el pago
     */
    private function loadTpvConfiguration(Payment $payment): bool
    {
        try {
            $this->tpv = $payment->tpv;

            if (!$this->tpv) {
                // Intentar cargar desde el brand del carrito
                $this->tpv = Tpv::withoutGlobalScope(\App\Scopes\BrandScope::class)
                    ->where('brand_id', $payment->cart->brand_id)
                    ->where('is_active', true)
                    ->orderBy('is_default', 'desc')
                    ->first();
            }

            if (!$this->tpv) {
                return false;
            }

            $this->tpvConfig = new TpvConfigurationDecoder($this->tpv->config);
            $this->testMode = (bool) $this->tpvConfig->get('sermepaTestMode', false);

            return true;
        } catch (\Exception $e) {
            Log::error('Error loading TPV config for refund', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Obtener mensaje de error legible de Redsys
     * 
     * Códigos según documentación oficial:
     * https://pagosonline.redsys.es/desarrolladores-inicio/integrate-con-nosotros/parametros-de-entrada-y-salida/
     */
    private function getRedsysErrorMessage(string $code): string
    {
        $errors = [
            // ─────────────────────────────────────────────────────────────
            // Códigos de ÉXITO (para referencia, no deberían llegar aquí)
            // ─────────────────────────────────────────────────────────────
            '0' => 'Transacción autorizada',
            '400' => 'Anulación aceptada',
            '900' => 'Devolución/Confirmación aceptada',

            // ─────────────────────────────────────────────────────────────
            // Códigos de ERROR comunes (100-999)
            // ─────────────────────────────────────────────────────────────
            '101' => 'Tarjeta caducada',
            '102' => 'Tarjeta bloqueada temporalmente o bajo sospecha de fraude',
            '104' => 'Operación no permitida para ese tipo de tarjeta',
            '106' => 'Intentos de PIN excedidos',
            '107' => 'Contactar con el emisor',
            '109' => 'Identificación inválida del comercio o terminal',
            '110' => 'Importe inválido',
            '114' => 'Tarjeta no soporta el tipo de operación solicitado',
            '116' => 'Disponible insuficiente',
            '118' => 'Tarjeta no registrada o inexistente',
            '125' => 'Tarjeta no efectiva',
            '129' => 'Código de seguridad (CVV2/CVC2) incorrecto',
            '167' => 'Contactar con el emisor: sospecha de fraude',
            '180' => 'Tarjeta no válida o fuera de servicio',
            '181' => 'Tarjeta con restricciones de débito o crédito',
            '182' => 'Contactar con el emisor',
            '184' => 'Error en autenticación del titular',
            '190' => 'Denegación sin especificar motivo',
            '191' => 'Fecha de caducidad errónea',
            '195' => 'Requiere autenticación SCA',
            '202' => 'Tarjeta bloqueada por posible fraude o retirada',
            '904' => 'Comercio no registrado en el FUC',
            '909' => 'Error de sistema',
            '912' => 'Emisor no disponible',
            '913' => 'Pedido repetido',
            '916' => 'Importe demasiado pequeño',
            '928' => 'Tiempo excedido',
            '940' => 'Transacción anulada anteriormente',
            '941' => 'Transacción de autorización ya anulada',
            '942' => 'Autorización original denegada',
            '943' => 'Datos de la transacción original distintos',
            '944' => 'Sesión errónea',
            '945' => 'Transmisión duplicada',
            '946' => 'Operación de anulación en proceso',
            '947' => 'Transmisión duplicada en proceso',
            '949' => 'Terminal inoperativo',
            '950' => 'Devolución no permitida',
            '965' => 'Contactar con el emisor',

            // ─────────────────────────────────────────────────────────────
            // Códigos SIS (errores del sistema Redsys 9xxx)
            // ─────────────────────────────────────────────────────────────
            '9001' => 'Error genérico del sistema',
            '9002' => 'Error genérico del sistema',
            '9051' => 'Número de pedido repetido',
            '9054' => 'No existe operación sobre la que realizar la devolución',
            '9055' => 'Existe más de un pago con el mismo número de pedido',
            '9056' => 'Operación original no autorizada para devolución',
            '9057' => 'El importe a devolver supera el permitido',
            '9058' => 'Datos de validación erróneos',
            '9059' => 'No existe operación sobre la que realizar la confirmación',
            '9060' => 'Ya existe confirmación asociada a la preautorización',
            '9061' => 'Preautorización no autorizada',
            '9062' => 'El importe a confirmar supera el permitido',
            '9063' => 'Número de tarjeta no válido',
            '9064' => 'Número de posiciones de tarjeta incorrecto',
            '9065' => 'El número de tarjeta no es numérico',
            '9071' => 'Tarjeta caducada',
            '9073' => 'Error en la anulación',
            '9074' => 'Falta Ds_Merchant_Order',
            '9075' => 'Ds_Merchant_Order tiene longitud incorrecta',
            '9078' => 'Tipo de operación no permitida para esa tarjeta',
            '9093' => 'Tarjeta no existente',
            '9094' => 'Denegación de emisores internacionales',
            '9104' => 'Comercio con titular seguro y tarjeta sin clave',
            '9112' => 'Tipo de transacción no permitido para el terminal',
            '9126' => 'Operación duplicada',
            '9142' => 'Tiempo excedido para el pago',
            '9214' => 'El comercio no permite devoluciones (firma ampliada requerida)',
            '9218' => 'El comercio no permite operaciones seguras por entrada H2H',
            '9253' => 'Tarjeta no cumple el check-digit',
            '9256' => 'El comercio no puede realizar preautorizaciones',
            '9257' => 'La tarjeta no permite preautorizaciones',
            '9261' => 'Operación detenida por superar control de restricciones',
            '9268' => 'La devolución no se puede procesar por WebService',
            '9280' => 'Bloqueo por control de seguridad',
            '9281' => 'Bloqueo por control de seguridad',
            '9282' => 'Bloqueo por control de seguridad',
            '9283' => 'Bloqueo por control de seguridad',
            '9334' => 'Bloqueo por control de seguridad',
            '9429' => 'Error en la versión de firma (Ds_SignatureVersion)',
            '9430' => 'Error al decodificar Ds_MerchantParameters',
            '9431' => 'Error en el JSON de Ds_MerchantParameters',
            '9432' => 'FUC del comercio erróneo',
            '9433' => 'Terminal del comercio erróneo',
            '9500' => 'Error en DCC Dinámico',
            '9909' => 'Error de sistema',
            '9912' => 'Emisor no disponible',
            '9913' => 'Error en la confirmación (SOAP)',
            '9914' => 'Confirmación KO del comercio (SOAP)',
            '9915' => 'Pago cancelado por el usuario',
            '9928' => 'Cancelación de preautorización denegada',
            '9997' => 'Transacción en proceso',
            '9998' => 'Operación en proceso de solicitud de datos',
            '9999' => 'Operación redirigida al emisor para autenticar',
        ];

        return $errors[$code] ?? "Error desconocido (código: {$code})";
    }

    /**
     * Crear respuesta de error
     */
    private function errorResponse(string $message, string $code, array $extra = []): array
    {
        return array_merge([
            'success' => false,
            'message' => $message,
            'error_code' => $code,
        ], $extra);
    }

    /**
     * Verificar si un pago puede ser reembolsado automáticamente
     */
    public function canRefund(Payment $payment): array
    {
        if (!$payment->paid_at) {
            return ['can_refund' => false, 'reason' => 'El pago no está confirmado'];
        }

        if ($payment->isRefunded()) {
            return ['can_refund' => false, 'reason' => 'El pago ya fue reembolsado'];
        }

        $validGateways = ['Sermepa', 'Redsys', 'Redsys Redirect', 'SermepaSoapService', 'RedsysSoapService'];
        if (!in_array($payment->gateway, $validGateways)) {
            return [
                'can_refund' => false,
                'reason' => "El gateway '{$payment->gateway}' no soporta devoluciones automáticas. Debe hacerse manualmente.",
                'manual_required' => true,
            ];
        }

        if (!$this->loadTpvConfiguration($payment)) {
            return ['can_refund' => false, 'reason' => 'No se pudo cargar la configuración del TPV'];
        }

        return ['can_refund' => true];
    }
}
