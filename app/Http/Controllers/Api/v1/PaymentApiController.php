<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\Payment\PaymentServiceFactory;

class PaymentApiController extends \App\Http\Controllers\Api\ApiController
{
    public function callback(Request $request)
    {
        // Log inicial con todos los datos recibidos
        Log::info('💳 Payment callback recibido', [
            'gateway' => $request->get('gateway', 'Redsys_Redirect'),
            'content_type' => $request->header('Content-Type'),
            'ip' => $request->ip(),
            'all_params' => $request->except(['password', 'token']), // Excluir datos sensibles
        ]);

        // Distingue SOAP vs REDIRECT
        $gateway = $request->get('gateway', 'Redsys_Redirect');
        $isXml = str_contains($request->header('Content-Type', ''), 'xml')
            || str_contains($request->header('Content-Type', ''), 'soap');

        if ($gateway === 'RedsysSoap' || $isXml) {
            return $this->processRedsysSoapCallback($request);
        }

        // Proceso de 3-party (Redirect)
        try {
            $service = PaymentServiceFactory::create($gateway);

            // Intentar obtener el payment primero para saber si existe
            $payment = $service->getPayment();

            if (!$payment) {
                Log::error('❌ Payment no encontrado en callback', [
                    'gateway' => $gateway,
                    'request_params' => $request->except(['password', 'token']),
                    'ds_merchant_parameters' => $request->input('Ds_MerchantParameters'),
                ]);

                return redirect()->away('/');
            }

            // Ahora verificar si el pago fue exitoso
            $successful = $service->isPaymentSuccessful($request);

            if (!$successful) {
                // Intentar obtener más información del servicio
                $responseData = null;
                try {
                    $responseData = $service->getJsonResponse();
                } catch (\Exception $e) {
                    // Si no se puede obtener la respuesta, continuamos
                }

                Log::error('❌ Pago no exitoso - Validación fallida', [
                    'payment_id' => $payment->id,
                    'order_code' => $payment->order_code,
                    'cart_id' => $payment->cart_id,
                    'gateway' => $gateway,
                    'amount' => $payment->amount,
                    'gateway_response' => $responseData,
                    'request_params' => $request->except(['password', 'token']),
                    'ds_response' => $request->input('Ds_Response'), // Código de respuesta de Redsys
                    'ds_error_code' => $request->input('Ds_ErrorCode'),
                ]);

                // Creamos fallback para redirect KO
                $koUrl = '/';
                if ($payment->cart && $service->getConfigDecoder()) {
                    $cfg = $service->getConfigDecoder();
                    $koTpl = $cfg->get('sermepaUrlKO');
                    $koUrl = str_replace(
                        ['{id}', '{locale}'],
                        [$payment->cart->id, $payment->cart->client->locale],
                        $koTpl
                    );
                }

                return redirect()->away($koUrl);
            }

            // ✅ Pago exitoso
            Log::info('✅ Pago exitoso confirmado', [
                'payment_id' => $payment->id,
                'order_code' => $payment->order_code,
                'cart_id' => $payment->cart_id,
                'amount' => $payment->amount,
            ]);

            $service->confirmPayment();

            $cart = $payment->cart;
            $cfg = $service->getConfigDecoder();

            $okUrl = str_replace(
                ['{token}', '{locale}'],
                [$cart->token, $cart->client->locale],
                $cfg->get('sermepaUrlOK')
            );

            return redirect()->away($okUrl);
        } catch (\Exception $e) {
            Log::error('💥 Excepción en payment callback', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'gateway' => $gateway ?? 'unknown',
                'request_params' => $request->except(['password', 'token']),
            ]);

            return redirect()->away('/');
        }
    }


    private function processRedsysSoapCallback(Request $request)
    {
        $response = response('', 200, ['Content-Type' => 'text/xml; charset=utf-8']);
        try {
            $server = new \SoapServer(null, ['uri' => 'https://api.yesweticket.com']);
            $server->setClass(\App\Services\Payment\Impl\PaymentRedsysSoapService::class, 'RedsysSoapService');
            ob_start();
            $server->handle();
            $response->setContent(ob_get_clean());
        } catch (\SoapFault $e) {
            logger()->warning('SOAP Redsys ERROR: ' . $e->faultstring);
        }
        return $response;
    }
}
