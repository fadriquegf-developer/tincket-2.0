<?php

namespace App\Http\Controllers\Open;

use App\Services\PDF\PDFService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function download($cart, Request $request)
    {
        $cart = \App\Models\Cart::withoutGlobalScopes()
            ->with(['allInscriptions', 'brand'])
            ->where('id', $cart)
            ->firstOrFail();

        $token = $request->get('token');
        if (!$token || $cart->token !== $token) {
            abort(403, 'Token inválido o faltante');
        }

        if (!is_null($cart->confirmation_code)) {
            $inscriptions = $cart->allInscriptions;

            $merged_file = (new PDFService)->inscriptions($inscriptions, $cart->token);

            return response()->download($merged_file)->deleteFileAfterSend(true);
        }

        return abort(404);
    }

    public function downloadPack($cartId, $packId, Request $request)
    {
        $cart = \App\Models\Cart::withoutGlobalScopes()
            ->with(['groupPacks.inscriptions', 'brand'])
            ->where('id', $cartId)
            ->firstOrFail();

        // Validación del token (igual que download general)
        $token = $request->get('token');
        if (!$token || $cart->token !== $token) {
            abort(403, 'Token inválido o faltante');
        }

        // Buscar el pack concreto
        $pack = $cart->groupPacks->where('id', $packId)->first();
        if (!$pack) {
            abort(404, 'Pack no encontrado en este carrito.');
        }

        // Las inscripciones del pack
        $inscriptions = $pack->inscriptions;

        if ($inscriptions->isEmpty()) {
            abort(404, 'Este pack no tiene entradas disponibles.');
        }

        // Generar PDF solo con las inscripciones de este pack
        $merged_file = (new PDFService)->inscriptions($inscriptions, $cart->token);

        return response()->download($merged_file)->deleteFileAfterSend(true);
    }

}
