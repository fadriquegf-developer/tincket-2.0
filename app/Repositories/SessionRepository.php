<?php

namespace App\Repositories;

use App\Models\Session;
use App\Models\Inscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SessionRepository
{
    // Aumentar TTL para datos que cambian poco
    private const CACHE_TTL_SHORT = 300;    // 5 minutos para datos volátiles
    private const CACHE_TTL_MEDIUM = 1800;  // 30 minutos para datos semi-estables
    private const CACHE_TTL_LONG = 3600;    // 1 hora para datos estables

    /**
     * Obtener inscripciones vendidas con cache inteligente
     */
    public function getSelledInscriptions(Session $session, bool $onlyWeb = true): int
    {
        $cacheKey = $this->getCacheKey($session->id, 'selled', $onlyWeb ? 'web' : 'all');

        // TTL más largo porque estos datos cambian poco una vez confirmados
        return Cache::tags($this->getSessionTags($session))
            ->remember($cacheKey, self::CACHE_TTL_MEDIUM, function () use ($session, $onlyWeb) {
                return $this->calculateSelledInscriptions($session, $onlyWeb);
            });
    }

    /**
     * Calcular inscripciones vendidas (query real)
     */
    private function calculateSelledInscriptions(Session $session, bool $onlyWeb): int
    {
        $query = Inscription::query()
            ->where('session_id', $session->id)
            ->whereHas('cart', function ($q) {
                $q->whereNotNull('confirmation_code');
            });

        if ($onlyWeb) {
            $query->whereHas('cart.payments', function ($p) {
                $p->whereIn('gateway', ['Sermepa', 'SermepaSoapService', 'Free', 'RedsysSoapService', 'Redsys Redirect']);
            });
        } else {
            $query->whereHas('cart.payments', function ($p) {
                $p->whereNotIn('gateway', ['Sermepa', 'SermepaSoapService', 'Free', 'RedsysSoapService', 'Redsys Redirect']);
            });
        }

        return $query->count();
    }

    /**
     * Obtener inscripciones vendidas en taquilla
     */
    public function getSelledOfficeInscriptions(Session $session): int
    {
        $cacheKey = $this->getCacheKey($session->id, 'selled', 'office');

        return Cache::tags($this->getSessionTags($session))
            ->remember($cacheKey, self::CACHE_TTL_MEDIUM, function () use ($session) {
                return Inscription::query()
                    ->where('session_id', $session->id)
                    ->whereHas('cart', function ($q) {
                        $q->whereNotNull('confirmation_code');
                    })
                    ->whereHas('cart.payments', function ($q) {
                        $q->where('gateway', 'TicketOffice');
                    })
                    ->count();
            });
    }

    /**
     * Obtener estadísticas de validación con cache optimizado
     */
    public function getValidatedCount(Session $session): array
    {
        $cacheKey = $this->getCacheKey($session->id, 'validated', 'stats');

        return Cache::tags($this->getSessionTags($session))
            ->remember($cacheKey, self::CACHE_TTL_SHORT, function () use ($session) {
                $stats = DB::table('inscriptions')
                    ->join('carts', 'carts.id', '=', 'inscriptions.cart_id')
                    ->where('inscriptions.session_id', $session->id)
                    ->whereNotNull('carts.confirmation_code')
                    ->selectRaw('
                        COUNT(CASE WHEN inscriptions.checked_at IS NOT NULL THEN 1 END) as validated,
                        COUNT(CASE 
                            WHEN inscriptions.checked_at IS NOT NULL 
                            AND inscriptions.out_event = 1 THEN 1 
                        END) as validated_out,
                        COUNT(*) as total
                    ')
                    ->first();

                return [
                    'validated' => $stats->validated ?? 0,
                    'validated_out' => $stats->validated_out ?? 0,
                    'total' => $stats->total ?? 0,
                    'pending' => ($stats->total ?? 0) - ($stats->validated ?? 0)
                ];
            });
    }

    /**
     * Obtener tarifa general con cache largo
     */
    public function getGeneralRate(Session $session)
    {
        $cacheKey = $this->getCacheKey($session->id, 'general', 'rate');

        // Cache más largo porque las tarifas cambian poco
        return Cache::tags($this->getSessionTags($session))
            ->remember($cacheKey, self::CACHE_TTL_LONG, function () use ($session) {
                $today = now();

                $query = DB::table('assignated_rates as ar')
                    ->join('rates as r', 'r.id', '=', 'ar.rate_id')
                    ->where('ar.session_id', $session->id)
                    ->select(
                        'ar.*',
                        'r.name as rate_name',
                        'r.needs_code as rate_needs_code',
                        'r.form_id as rate_form_id'
                    );

                // Buscar tarifa privada válida
                $privateRate = (clone $query)
                    ->where('ar.is_private', true)
                    ->where(function ($sub) use ($today) {
                        $sub->where(function ($q) use ($today) {
                            $q->where('ar.available_since', '<=', $today)
                                ->orWhereNull('ar.available_since');
                        })
                            ->where(function ($q) use ($today) {
                                $q->where('ar.available_until', '>=', $today)
                                    ->orWhereNull('ar.available_until');
                            });
                    })
                    ->orderBy('ar.price', 'desc')
                    ->first();

                // Si no hay privada, obtener la de mayor precio
                return $privateRate ?: $query->orderBy('ar.price', 'desc')->first();
            });
    }

    /**
     * Invalidar cache cuando se crea/actualiza una inscripción
     */
    public function invalidateInscriptionCache(Session $session): void
    {
        Cache::tags($this->getSessionTags($session))->flush();
    }

    /**
     * Invalidar cache cuando se valida una entrada
     */
    public function invalidateValidationCache(Session $session): void
    {
        // Solo invalidar cache de validación, no todo
        $validationKeys = [
            $this->getCacheKey($session->id, 'validated', 'stats')
        ];

        foreach ($validationKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Invalidar cache cuando cambian las tarifas
     */
    public function invalidateRateCache(Session $session): void
    {
        Cache::forget($this->getCacheKey($session->id, 'general', 'rate'));

        // También invalidar en Redis si es numerada
        if ($session->is_numbered) {
            $redisService = new \App\Services\RedisSlotsService($session);
            $redisService->clearAllCache();
        }
    }

    /**
     * Pre-calentar cache para una sesión
     */
    public function warmupCache(Session $session): void
    {
        // Pre-cargar datos que se usan frecuentemente
        $this->getSelledInscriptions($session, true);
        $this->getSelledOfficeInscriptions($session);
        $this->getValidatedCount($session);
        $this->getGeneralRate($session);
    }

    /**
     * Obtener estadísticas de inscripciones (optimizado)
     */
    public function getInscriptionStats(Session $session): array
    {
        $cacheKey = $this->getCacheKey($session->id, 'stats', 'complete');

        return Cache::tags($this->getSessionTags($session))
            ->remember($cacheKey, self::CACHE_TTL_SHORT, function () use ($session) {
                $stats = DB::table('inscriptions as i')
                    ->join('carts as c', 'c.id', '=', 'i.cart_id')
                    ->leftJoin('payments as p', function ($join) {
                        $join->on('p.cart_id', '=', 'c.id')
                            ->whereNotNull('p.paid_at')
                            ->whereNull('p.deleted_at');
                    })
                    ->where('i.session_id', $session->id)
                    ->whereNotNull('c.confirmation_code')
                    ->selectRaw("
                        COUNT(DISTINCT i.id) as total_inscriptions,
                        COUNT(DISTINCT c.id) as total_carts,
                        SUM(i.price_sold) as total_revenue,
                        AVG(i.price_sold) as avg_price,
                        COUNT(CASE WHEN i.checked_at IS NOT NULL THEN 1 END) as validated_count,
                        COUNT(CASE WHEN p.gateway = 'TicketOffice' THEN 1 END) as office_sales,
                        COUNT(CASE WHEN p.gateway IN ('Redsys Redirect', 'Free') THEN 1 END) as web_sales
                    ")
                    ->first();

                return [
                    'total_inscriptions' => $stats->total_inscriptions ?? 0,
                    'total_carts' => $stats->total_carts ?? 0,
                    'total_revenue' => round($stats->total_revenue ?? 0, 2),
                    'avg_price' => round($stats->avg_price ?? 0, 2),
                    'validated_count' => $stats->validated_count ?? 0,
                    'office_sales' => $stats->office_sales ?? 0,
                    'web_sales' => $stats->web_sales ?? 0,
                    'validation_rate' => $stats->total_inscriptions > 0
                        ? round(($stats->validated_count / $stats->total_inscriptions) * 100, 2)
                        : 0
                ];
            });
    }

    /**
     * Limpiar toda la cache de la sesión
     */
    public function clearAllCache(Session $session): void
    {
        Cache::tags($this->getSessionTags($session))->flush();
    }

    // ================== HELPERS ==================

    /**
     * Generar cache key consistente
     */
    private function getCacheKey(int $sessionId, string $type, string $subtype = ''): string
    {
        $key = "session_repo:{$sessionId}:{$type}";
        if ($subtype) {
            $key .= ":{$subtype}";
        }
        return $key;
    }

    /**
     * Obtener tags para cache
     */
    private function getSessionTags(Session $session): array
    {
        return [
            'session:' . $session->id,
            'brand:' . $session->brand_id
        ];
    }
}
