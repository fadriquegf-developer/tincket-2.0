<?php

namespace App\Http\Controllers\Api\v1;


use App\Models\Cart;
use App\Models\Rate;
use App\Models\Brand;
use App\Models\Censu;
use App\Models\Session;
use App\Models\Inscription;
use Illuminate\Http\Request;
use App\Services\Rate\CodeValidatorFactory;

/**
 * Description of RateApiController
 *
 * @author miquel
 */
class RateApiController extends \App\Http\Controllers\Api\ApiController
{

    /**
     * Valida si un código es válido para una tarifa dada.
     */
    public function checkCode(Request $request, Rate $rate)
    {
        $session = Session::findOrFail($request->get('session_id'));
        $validator = CodeValidatorFactory::getInstance($rate);

        $isValid = $validator->isCodeValid($session, $request->get('code'));

        return response()->json([
            'is_valid' => $isValid,
            'message' => $isValid ?: $validator->getMessage()
        ])->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * Verifica si el DNI está enlazado a la sesión y si ha comprado menos del límite permitido.
     */
    public function checkDni(Request $request, Rate $rate)
    {
        $session = Session::findOrFail($request->get('session_id'));

        $codeValid = $session->code_type === 'census'
            ? Censu::where('code', $request->get('dni'))->exists()
            : $session->codes()->where('code', $request->get('dni'))->exists();

        $inscriptionsBuyed = Inscription::paid()
            ->where('session_id', $session->id)
            ->where('rate_id', $rate->id)
            ->where('code', $request->get('dni'))
            ->count();

        return response()->json([
            'is_valid'             => (bool) $codeValid,
            'inscriptions_buyed'   => $inscriptionsBuyed,
            'message'              => $codeValid ? 'DNI es válido' : 'DNI no es válido'
        ])
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }


    /**
     * Verifica cuántas inscripciones ha comprado el email de un usuario.
     */
    public function checkEmail(Request $request, Rate $rate)
    {
        $session = Session::findOrFail($request->get('session_id'));

        $inscriptionsBuyed = Inscription::paid()
            ->where('session_id', $session->id)
            ->where('rate_id', $rate->id)
            ->where('code', $request->get('email'))
            ->count();

        return response()->json([
            'inscriptions_buyed' => $inscriptionsBuyed
        ])->header('Access-Control-Allow-Origin', '*')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }


    /**
     * Comprueba si la tarifa de la sesión está llena.
     */
    public function rateIsFull(Request $request, Rate $rate)
    {
        $session = Session::findOrFail($request->get('session_id'));
        $assignatedRate = $rate->assignatedRates()->where('session_id', $session->id)->firstOrFail();

        // Calcular inscripciones confirmadas y no expiradas
        $blockedInscriptions = $this->calculateBlockedInscriptions($session, $rate);

        // Verificar si la tarifa está llena
        $isFull = min([$session->count_available_web_positions, $assignatedRate->max_on_sale - $blockedInscriptions]) < 1;

        return response()->json([
            'isFull' => $isFull,
            'message' => $isFull ? 'Tarifa exhaurida' : ''
        ])->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * Calcula la cantidad de inscripciones bloqueadas para una sesión y tarifa dadas.
     */
    private function calculateBlockedInscriptions(Session $session, Rate $rate)
    {
        $cartTTL = $session->brand->getSetting(Brand::EXTRA_CONFIG['CART_TTL_KEY'], Cart::DEFAULT_MINUTES_TO_EXPIRE);

        $confirmedInscriptions = Inscription::where('session_id', $session->id)
            ->where('rate_id', $rate->id)
            ->join('carts', 'carts.id', '=', 'inscriptions.cart_id')
            ->whereNotNull('carts.confirmation_code')
            ->count();

        $notExpiredCarts = Inscription::where('session_id', $session->id)
            ->where('rate_id', $rate->id)
            ->join('carts', 'carts.id', '=', 'inscriptions.cart_id')
            ->where('carts.expires_on', '>', \Carbon\Carbon::now()->subMinutes($cartTTL))
            ->whereNull('carts.confirmation_code')
            ->count();

        return $confirmedInscriptions + $notExpiredCarts;
    }
}
