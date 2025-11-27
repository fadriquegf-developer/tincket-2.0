<?php

namespace App\Http\Controllers\ApiBackend;

use App\Http\Controllers\Controller;
use App\Http\Resources\SessionStaticsCollection;
use App\Models\AssignatedRate;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\Inscription;
use App\Models\Session;
use Carbon\Carbon;

class SessionApiBackendController extends Controller
{
    /**
     * Devuelve la configuración de una sesión usando REDIS
     */
    public function getConfiguration($sessionId)
    {
        app()->setLocale(brand_setting('app.locale'));

        $session = Session::findOrFail($sessionId);
        $session->checkBrandOwnership();

        if (!$session->space_id) {
            abort(404, 'Session has no associated space');
        }

        // ✅ Usar RedisSlotsService para obtener configuración completa desde Redis
        $cacheService = app(\App\Services\RedisSlotsService::class, ['session' => $session]);
        $cacheService->setShowPrivateRates(true);

        // Si no está cacheado, regenerar
        if (!$cacheService->isCached()) {
            $cacheService->regenerateCache();
        }

        // ✅ Obtener configuración completa desde Redis
        $configuration = $cacheService->getConfiguration();

        // ✅ Añadir información adicional del space si es necesario
        if ($session->space) {
            $configuration['space'] = [
                'id' => $session->space->id,
                'name' => $session->space->name,
                'svg_host_path' => $session->space->svg_host_path ?? null,
                'zoom' => $session->space->zoom ?? false,
            ];
        }

        return response()->json($configuration);
    }

    /**
     * Devuelve un listado de sesiones según filtros (expiradas, ventas, etc.).
     */
    public function search()
    {
        $now = Carbon::now();

        $sessions = Session::query()
            ->ownedByBrandOrPartneship()
            ->with(['event:id,brand_id,name'])
            ->when(!request()->boolean('show_expired'), function ($query) use ($now) {
                return $query->where('starts_on', '>', $now)
                    ->where('inscription_starts_on', '<', $now)
                    ->where('inscription_ends_on', '>', $now);
            })
            ->when(request()->boolean('with_sales'), function ($query) {
                return $query->whereHas('inscriptions', function ($q) {
                    $q->paid();
                });
            })
            ->select(['id', 'brand_id', 'name', 'starts_on', 'event_id'])
            ->orderByDesc('starts_on')
            ->get();

        return new SessionStaticsCollection($sessions);
    }

    /**
     * Obtiene las tarifas de una sesión con su disponibilidad
     */
    public function getRates($sessionId)
    {
        $session = Session::findOrFail($sessionId);
        $session->checkBrandOwnership();

        // ✅ Usar RedisSlotsService para obtener disponibilidad real
        $redisService = app(\App\Services\RedisSlotsService::class, ['session' => $session]);
        $carTtl = $session->brand->getSetting(Brand::EXTRA_CONFIG['CART_TTL_KEY'], Cart::DEFAULT_MINUTES_TO_EXPIRE);

        // Cargar las tarifas públicas
        $rates = $session->allRates()
            ->withoutGlobalScopes()
            ->with([
                'rate' => function ($query) {
                    $query->withoutGlobalScopes();
                }
            ])
            ->get()
            ->map(function ($assignatedRate) use ($session, $redisService, $carTtl) {
                $rate = $assignatedRate->rate;

                if (!$rate) {
                    return null; // Skip si no existe la tarifa
                }

                $maxOnSale = $assignatedRate->max_on_sale ?? 0;

                // Inscripciones confirmadas
                $confirmedInscriptions = Inscription::where('session_id', $session->id)
                    ->where('rate_id', $rate->id)
                    ->join('carts', 'carts.id', '=', 'inscriptions.cart_id')
                    ->whereNotNull('carts.confirmation_code')
                    ->count();

                // Carritos no expirados
                $notExpiredCarts = Inscription::where('session_id', $session->id)
                    ->where('rate_id', $rate->id)
                    ->join('carts', 'carts.id', '=', 'inscriptions.cart_id')
                    ->where('carts.expires_on', '>', Carbon::now()->subMinutes($carTtl))
                    ->whereNull('carts.confirmation_code')
                    ->count();

                $blockedInscriptions = $confirmedInscriptions + $notExpiredCarts;

                // ✅ Usar Redis para obtener posiciones libres
                $freePositions = $redisService->getFreePositions();
                $available = max(0, min($freePositions, $maxOnSale - $blockedInscriptions));

                return [
                    'id' => $rate->id,
                    'name' => $rate->name,
                    'price' => $assignatedRate->price ?? 0,
                    'available' => $available,
                    'max_per_order' => $assignatedRate->max_per_order ?? 1,
                    'max_on_sale' => $maxOnSale,
                ];
            })
            ->filter() // Eliminar nulls
            ->values(); // Re-indexar

        return response()->json($rates);
    }
}
