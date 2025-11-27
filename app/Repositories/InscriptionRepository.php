<?php

namespace App\Repositories;

use App\Models\Inscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;

class InscriptionRepository
{
    private const CACHE_TTL = 60;

    /**
     * Obtiene inscripciones con datos joined para listados optimizados
     * Reemplaza el scopeWithJoinedData del modelo
     */
    public function getWithJoinedData($filters = [])
    {
        $locale = app()->getLocale() ?? 'ca';

        $query = DB::table('inscriptions')
            ->select([
                'inscriptions.*',
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(sessions.name, '$.\"{$locale}\"')) as session_name"),
                'sessions.starts_on',
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(events.name, '$.\"{$locale}\"')) as event_name"),
                'slots.name as slot_name',
                'carts.client_id',
                'carts.confirmation_code',
                'carts.created_at as cart_created_at',
                'payments.gateway',
                'payments.tpv_name',
                'payments.paid_at',
                'clients.name as client_name',
                'clients.surname as client_surname',
                'clients.email as client_email'
            ])
            ->leftJoin('sessions', 'inscriptions.session_id', '=', 'sessions.id')
            ->leftJoin('events', 'sessions.event_id', '=', 'events.id')
            ->leftJoin('slots', 'inscriptions.slot_id', '=', 'slots.id')
            ->leftJoin('carts', 'inscriptions.cart_id', '=', 'carts.id')
            ->leftJoin('clients', 'carts.client_id', '=', 'clients.id')
            ->leftJoin('payments', function ($join) {
                $join->on('carts.id', '=', 'payments.cart_id')
                    ->whereNotNull('payments.paid_at')
                    ->whereNull('payments.deleted_at');
            });

        // Aplicar filtros
        if (!empty($filters['brand_id'])) {
            $query->where('inscriptions.brand_id', $filters['brand_id']);
        }

        if (!empty($filters['session_id'])) {
            $query->where('inscriptions.session_id', $filters['session_id']);
        }

        if (!empty($filters['event_id'])) {
            $query->where('sessions.event_id', $filters['event_id']);
        }

        if (!empty($filters['only_paid'])) {
            $query->whereNotNull('carts.confirmation_code');
        }

        if (!empty($filters['date_from'])) {
            $query->where('inscriptions.created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('inscriptions.created_at', '<=', $filters['date_to']);
        }

        return $query;
    }

    /**
     * Obtiene inscripciones para una sesión específica con cache
     */
    public function getSessionInscriptions($sessionId, $onlyPaid = false)
    {
        $cacheKey = "session_{$sessionId}_inscriptions_" . ($onlyPaid ? 'paid' : 'all');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($sessionId, $onlyPaid) {
            $query = Inscription::where('session_id', $sessionId)
                ->with(['cart.client', 'rate', 'slot']);

            if ($onlyPaid) {
                $query->whereHas('cart', function ($q) {
                    $q->whereNotNull('confirmation_code');
                });
            }

            return $query->get();
        });
    }

    /**
     * Obtiene estadísticas de inscripciones para reportes
     */
    public function getInscriptionStats($filters = [])
    {
        $query = DB::table('inscriptions as i')
            ->join('carts as c', 'c.id', '=', 'i.cart_id')
            ->join('sessions as s', 's.id', '=', 'i.session_id')
            ->whereNull('i.deleted_at')
            ->whereNotNull('c.confirmation_code');

        if (!empty($filters['brand_id'])) {
            $query->where('i.brand_id', $filters['brand_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('i.created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('i.created_at', '<=', $filters['date_to']);
        }

        return $query->selectRaw('
            COUNT(DISTINCT i.id) as total_inscriptions,
            COUNT(DISTINCT c.id) as total_carts,
            COUNT(DISTINCT s.id) as total_sessions,
            SUM(i.price_sold) as total_revenue,
            AVG(i.price_sold) as avg_price,
            COUNT(CASE WHEN i.checked_at IS NOT NULL THEN 1 END) as validated_count
        ')->first();
    }

    /**
     * Invalida cache relacionado con inscripciones
     */
    public function invalidateCache($sessionId = null)
    {
        if ($sessionId) {
            Cache::forget("session_{$sessionId}_inscriptions_paid");
            Cache::forget("session_{$sessionId}_inscriptions_all");
        }
    }
}
