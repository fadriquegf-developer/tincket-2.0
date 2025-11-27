<?php

namespace App\Http\Controllers\Open;

use App\Models\GiftCard;
use App\Http\Controllers\Controller;

class GiftCardController extends Controller
{
    public function getPDF($id)
    {
        $gift = GiftCard::withoutGlobalScopes()
            ->with(['cart' => fn($q) => $q->withoutGlobalScopes()->with([
                'brand' => fn($q) => $q->withoutGlobalScopes(),
                'client' => fn($q) => $q->withoutGlobalScopes(),
            ])])
            ->findOrFail($id);

        $cart = $gift->cart;

        // Verificar token
        if (!$cart->confirmation_code || $cart->token !== request()->get('token')) {
            abort(404, "PDF cannot be rendered");
        }

        // ✅ USAR brand_code DE LA URL SI EXISTE
        $brandCode = request()->get('brand_code') ?? $cart->brand->code_name;

        // Cargar configuración de brand
        $middleware = new \App\Http\Middleware\CheckBrandHost();
        $middleware->loadBrandConfig($brandCode);

        // Idioma cliente
        \App::setLocale($cart->client->locale ?? config('app.locale'));

        $view = 'core.gift_card.pdf';

        return \View::make($view, compact('gift'));
    }

    public function download($id)
    {
        $gift = GiftCard::withoutGlobalScopes()
            ->with(['cart' => fn($q) => $q->withoutGlobalScopes()->with([
                'brand' => fn($q) => $q->withoutGlobalScopes(),
                'client' => fn($q) => $q->withoutGlobalScopes(),
            ])])
            ->findOrFail($id);

        $cart = $gift->cart;

        // Verificar token
        if (
            !$cart->confirmation_code
            || $cart->token !== request()->get('token')
        ) {
            abort(404, "PDF cannot be rendered");
        }

        $destination_path = 'pdf/gift_card';

        try {
            $filePath = "$destination_path/{$gift->pdf_name}";

            if (!\Storage::disk('local')->exists($filePath)) {
                if (!\Storage::disk('local')->exists($destination_path)) {
                    \Storage::disk('local')->makeDirectory($destination_path);
                }

                $url = url(route('open.gift_card.pdf', [
                    'gift' => $gift->id,
                    'token' => $cart->token,
                    'brand_code' => $cart->brand->code_name,
                ]));

                $options = [
                    'ph' => 140,
                    'pw' => 170,
                    'mb' => 0,
                    'mt' => 5,
                    'ml' => 5,
                    'mr' => 5,
                ];

                $pdfService = app(\App\Services\PdfGeneratorService::class);
                $pdf_content = $pdfService->generateFromUrl($url, $options);

                if (\Storage::disk('local')->put($filePath, $pdf_content)) {
                    $gift->pdf = $filePath;
                    $gift->save();
                }
            }

            $pdf_path = \Storage::disk('local')->path($gift->pdf);
            return response()->download($pdf_path);
        } catch (\Exception $e) {
            \Log::error("Gift card download failed", [
                'gift_id' => $gift->id,
                'error' => $e->getMessage()
            ]);

            if (app()->environment('production')) {
                throw $e;
            }

            abort(404);
        }
    }
}
