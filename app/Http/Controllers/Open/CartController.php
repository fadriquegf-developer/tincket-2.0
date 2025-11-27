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
}
