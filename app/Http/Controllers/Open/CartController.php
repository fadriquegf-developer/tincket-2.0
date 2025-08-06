<?php

namespace App\Http\Controllers\Open;

use App\Services\PDF\PDFService;
use App\Http\Controllers\Controller;

class CartController extends Controller
{
    public function download($cart)
    {
        $cart = \App\Models\Cart::where('id', $cart)->firstOrFail();

        if(!is_null($cart->confirmation_code)){

            //Ahora podemos coger todas las inscripciones del carrito
            
            $inscriptions = $cart->allInscriptions;
            \Log::info('Descargando PDF para el carrito:', $inscriptions->toArray());

            $merged_file = (new PDFService)->inscriptions($inscriptions, $cart->token);

            return response()->download($merged_file)->deleteFileAfterSend(true);

        }

        return abort(404);
    }

}
