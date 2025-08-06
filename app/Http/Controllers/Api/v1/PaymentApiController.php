<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\Payment\PaymentServiceFactory;

class PaymentApiController extends \App\Http\Controllers\Api\ApiController
{
    public function callback(Request $request)
    {
        Log::info('🔔 Redsys callback RAW:', $request->all());

        $decoded = [];
        if ($request->filled('Ds_MerchantParameters')) {
            $decoded = json_decode(base64_decode($request->input('Ds_MerchantParameters')), true);
            Log::info('🔔 Redsys callback DECODED:', $decoded);

            // Log del Ds_Order
            if (isset($decoded['Ds_Order'])) {
                Log::info('🔔 Redsys Ds_Order:', ['Ds_Order' => $decoded['Ds_Order']]);
            }
        }

        // Distingue SOAP vs REDIRECT
        $isXml = str_contains($request->header('Content-Type', ''), 'xml');
        $gateway = $request->get('gateway', 'Redsys_Redirect');
        if ($gateway === 'RedsysSoap' || ($gateway === 'Redsys_Redirect' && $isXml)) {
            return $this->processRedsysSoapCallback($request);
        }

        // Proceso de 3-party (Redirect)
        $service = PaymentServiceFactory::create($gateway);
        $successful = $service->isPaymentSuccessful($request);
        $payment = $service->getPayment();

        // Creamos fallback para redirect KO si falla antes de obtener config
        $koUrl = '/';

        // Intentamos obtener la config para URL KO personalizada
        if ($payment && $payment->cart && $service->getConfigDecoder()) {
            $cfg = $service->getConfigDecoder();
            $koTpl = $cfg->get('sermepaUrlKO');
            $koUrl = str_replace(
                ['{id}', '{locale}'],
                [$payment->cart->id, $payment->cart->client->locale],
                $koTpl
            );
        }

        if (!$successful || !$payment) {
            Log::warning('⚠️ El pago no fue exitoso o no se encontró el payment');
            return redirect()->away($koUrl);
        }

        $service->confirmPayment();

        $cart = $payment->cart;
        $cfg = $service->getConfigDecoder();

        $okUrl = str_replace(
            ['{token}', '{locale}'],
            [$cart->token, $cart->client->locale],
            $cfg->get('sermepaUrlOK')
        );

        return redirect()->away($okUrl);
    }


    private function processRedsysSoapCallback(Request $request)
    {
        $response = response('', 200, ['Content-Type' => 'text/xml; charset=utf-8']);
        try {
            $server = new \SoapServer(null, ['uri' => 'https://api-develop.yesweticket.com']);
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
