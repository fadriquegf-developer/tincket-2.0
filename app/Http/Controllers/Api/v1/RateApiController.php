<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Cart;
use App\Models\Rate;
use App\Models\Brand;
use App\Models\Censu;
use App\Models\Session;
use App\Models\Inscription;
use App\Scopes\BrandScope;
use Illuminate\Http\Request;
use App\Services\Rate\CodeValidatorFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RateApiController extends \App\Http\Controllers\Api\ApiController
{
    /**
     * Valida si un código es válido para una tarifa dada.
     */
    public function checkCode(Request $request, Rate $rate)
    {
        $brand = $request->get('brand') ?? $rate->brand;

        $allowedBrandIds = array_merge(
            [$brand->id],
            $brand->children->pluck('id')->toArray()
        );

        // ✅ CAMBIO: Quitar BrandScope de Session
        $session = Session::withoutGlobalScope(BrandScope::class)
            ->whereIn('brand_id', $allowedBrandIds)
            ->findOrFail($request->get('session_id'));

        $code = $request->get('code');

        // Cache del resultado por 60 segundos
        $cacheKey = "code_check:{$session->id}:{$rate->id}:" . md5($code);

        $result = Cache::remember($cacheKey, 60, function () use ($session, $rate, $code) {
            $validator = CodeValidatorFactory::getInstance($rate);
            $isValid = $validator->isCodeValid($session, $code);

            return [
                'is_valid' => $isValid,
                'message' => $isValid ? 'Código válido' : $validator->getMessage()
            ];
        });

        return response()->json($result)
            ->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * Verifica si el DNI está enlazado a la sesión y si ha comprado menos del límite permitido.
     */
    public function checkDni(Request $request, Rate $rate)
    {
        $session = Session::findOrFail($request->get('session_id'));
        $dni = $request->get('dni');

        // Usar una sola query optimizada
        $result = DB::transaction(function () use ($session, $rate, $dni) {
            // Verificar código válido
            $codeValid = false;
            if ($session->code_type === 'census') {
                $codeValid = Censu::where('code', $dni)->exists();
            } else {
                $codeValid = $session->codes()->where('code', $dni)->exists();
            }

            // Contar inscripciones compradas (con cache)
            $cacheKey = "dni_inscriptions:{$session->id}:{$rate->id}:" . md5($dni);
            $inscriptionsBuyed = Cache::remember($cacheKey, 30, function () use ($session, $rate, $dni) {
                return Inscription::paid()
                    ->where('session_id', $session->id)
                    ->where('rate_id', $rate->id)
                    ->where('code', $dni)
                    ->count();
            });

            return [
                'is_valid' => (bool) $codeValid,
                'inscriptions_buyed' => $inscriptionsBuyed,
                'message' => $codeValid ? 'DNI es válido' : 'DNI no es válido'
            ];
        });

        return response()->json($result)
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
        $email = $request->get('email');

        // Contar inscripciones SIN caché para evitar datos obsoletos
        $inscriptionsBuyed = Inscription::paid()
            ->where('session_id', $session->id)
            ->where('rate_id', $rate->id)
            ->where('code', $email)
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
        $brand = $request->get('brand') ?? $rate->brand;
        $allowedBrandIds = array_merge(
            [$brand->id],
            $brand->children->pluck('id')->toArray()
        );

        $session = Session::withoutGlobalScope(BrandScope::class)
            ->whereIn('brand_id', $allowedBrandIds)
            ->findOrFail($request->get('session_id'));

        // Cache key única para esta verificación
        $cacheKey = "rate_full:{$session->id}:{$rate->id}";

        $result = Cache::remember($cacheKey, 10, function () use ($session, $rate) {
            $assignatedRate = $rate->assignatedRates()
                ->where('session_id', $session->id)
                ->first();

            if (!$assignatedRate) {
                return [
                    'isFull' => true,
                    'message' => 'Tarifa no disponible'
                ];
            }

            // Calcular inscripciones bloqueadas
            $blockedInscriptions = $this->calculateBlockedInscriptionsOptimized($session, $rate);

            // Obtener posiciones disponibles web
            $availableWebPositions = $session->getAvailableWebPositions();

            // Calcular disponibilidad real
            $availableForRate = $assignatedRate->max_on_sale - $blockedInscriptions;
            $available = min($availableWebPositions, $availableForRate);

            $isFull = $available < 1;

            return [
                'isFull' => $isFull,
                'message' => $isFull ? 'Tarifa exhaurida' : '',
                'available' => max(0, $available)
            ];
        });

        return response()->json($result)
            ->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * Obtener disponibilidad de todas las tarifas de una sesión
     */
    public function sessionRatesAvailability(Request $request, int $session_id)
    {
        $brand = request()->get('brand');
        $allowedBrandIds = array_merge(
            [$brand->id],
            $brand->children->pluck('id')->toArray()
        );

        $session = Session::withoutGlobalScope(BrandScope::class)
            ->whereIn('brand_id', $allowedBrandIds)
            ->findOrFail($session_id);

        $cacheKey = "session_rates_availability:{$session_id}";

        $availability = Cache::remember($cacheKey, 15, function () use ($session) {
            $rates = [];

            // Obtener todas las tarifas asignadas públicas
            $assignatedRates = $session->allRates()
                ->where('is_public', true)
                ->with([
                    'rate' => function ($query) {
                        $query->withoutGlobalScope(BrandScope::class);
                    }
                ])
                ->get();

            foreach ($assignatedRates as $ar) {
                $blocked = $this->calculateBlockedInscriptionsOptimized($session, $ar->rate);
                $availableForRate = $ar->max_on_sale - $blocked;
                $available = min($session->getAvailableWebPositions(), $availableForRate);

                $rates[] = [
                    'rate_id' => $ar->rate_id,
                    'rate_name' => $ar->rate->name,
                    'price' => $ar->price,
                    'available' => max(0, $available),
                    'max_on_sale' => $ar->max_on_sale,
                    'max_per_order' => $ar->max_per_order,
                    'is_full' => $available < 1
                ];
            }

            return $rates;
        });

        return response()->json([
            'success' => true,
            'data' => $availability,
            'session_id' => $session_id,
            'cached_until' => now()->addSeconds(15)->toIso8601String()
        ]);
    }

    /**
     * Versión optimizada del cálculo de inscripciones bloqueadas
     */
    private function calculateBlockedInscriptionsOptimized(Session $session, Rate $rate): int
    {
        $cartTTL = $session->brand->getSetting(
            Brand::EXTRA_CONFIG['CART_TTL_KEY'],
            Cart::DEFAULT_MINUTES_TO_EXPIRE
        );

        // Una sola query optimizada
        $result = DB::selectOne("
            SELECT 
                COUNT(DISTINCT i.id) as blocked_count
            FROM inscriptions i
            INNER JOIN carts c ON c.id = i.cart_id
            WHERE i.session_id = ?
                AND i.rate_id = ?
                AND i.deleted_at IS NULL
                AND (
                    c.confirmation_code IS NOT NULL
                    OR (c.expires_on > ? AND c.confirmation_code IS NULL)
                )
        ", [
            $session->id,
            $rate->id,
            now()->subMinutes($cartTTL)
        ]);

        return (int) $result->blocked_count;
    }
}
