<?php

namespace App\Http\Controllers\ApiBackend\Statistics;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Payment\Impl\PaymentTicketOfficeService;

class StatisticsBalanceController extends Controller
{
    /**
     * GET /api-backend/statistics/balance
     * Params:
     *  - from: 2025-07-03 o 20250703 (opcional)
     *  - to:   2025-07-20 o 20250720 (opcional)
     *  - breakdown: U (usuario/app) | E (evento) | P (promotor/brand)
     */
    public function get(Request $request)
    {
        // 1) Validación
        $request->validate([
            'from'      => ['nullable', 'regex:/^\d{4}-?\d{2}-?\d{2}$/'],
            'to'        => ['nullable', 'regex:/^\d{4}-?\d{2}-?\d{2}$/'],
            'breakdown' => ['required', 'in:U,E,P'],
        ]);

        // 2) Parseo de fechas (acepta con o sin guiones)
        $start_date = $this->parseDate($request->input('from'), true);
        $end_date   = $this->parseDate($request->input('to'), false);

        // LOG de entrada
        Log::info('Statistics Balance Request', [
            'from'        => $request->input('from'),
            'to'          => $request->input('to'),
            'breakdown'   => $request->input('breakdown'),
            'parsed_start' => $start_date->toDateTimeString(),
            'parsed_end'  => $end_date->toDateTimeString(),
        ]);

        // 3) Cache
        $brand = get_current_brand();
        $cacheKey = sprintf(
            'balance_%s_%s_%s_%s',
            $request->breakdown,
            $start_date->format('Ymd'),
            $end_date->format('Ymd'),
            $brand->id
        );

        $balance = Cache::remember($cacheKey, 300, function () use ($request, $start_date, $end_date) {
            return match ($request->breakdown) {
                'E'     => $this->breakdownByEventOptimized($start_date, $end_date),
                'P'     => $this->breakdownByPromoterOptimized($start_date, $end_date),
                default => $this->breakdownByUserOptimized($start_date, $end_date),
            };
        });

        // LOG de salida
        Log::info('Statistics Balance Response', [
            'breakdown'     => $request->breakdown,
            'total_results' => $balance instanceof \Illuminate\Support\Collection ? $balance->count() : (is_array($balance) ? count($balance) : null),
            'sample'        => $balance instanceof \Illuminate\Support\Collection ? $balance->take(2)->toArray() : null,
        ]);

        return response()->json($balance);
    }

    /**
     * Convierte 'YYYYMMDD' o 'YYYY-MM-DD' en Carbon y aplica start/end.
     */
    private function parseDate(?string $value, bool $isStart = true): Carbon
    {
        if (empty($value) || $value === 'null') {
            return $isStart ? Carbon::minValue() : Carbon::maxValue();
        }

        $format = str_contains($value, '-') ? 'Y-m-d' : 'Ymd';
        $date   = Carbon::createFromFormat($format, $value);

        return $isStart ? $date->startOfDay() : $date->endOfDay();
    }

    /**
     * IDs del brand actual y sus hijos (promotores).
     * Requiere que el modelo Brand tenga relación children().
     */
    private function getBrandAndChildrenIds(): array
    {
        $brand = get_current_brand();

        $childrenIds = method_exists($brand, 'children')
            ? $brand->children()->pluck('id')->toArray()
            : [];

        $brandIds = array_merge([$brand->id], $childrenIds);

        return array_values(array_unique(array_map('intval', $brandIds)));
    }

    /**
     * Locale actual para traducciones.
     */
    private function getCurrentLocale(): string
    {
        return app()->getLocale() ?: 'es';
    }

    /**
     * Extrae valor traducido desde un JSON ({"es":"..","ca":"..","en":".."}).
     */
    private function getTranslatedValue($jsonValue): string
    {
        if (!$jsonValue) {
            return '';
        }

        // Si no es JSON, devolver tal cual
        if (!is_string($jsonValue) || !str_starts_with($jsonValue, '{')) {
            return (string) $jsonValue;
        }

        $decoded = json_decode($jsonValue, true);
        if (!is_array($decoded)) {
            return (string) $jsonValue;
        }

        $locale = $this->getCurrentLocale();

        return (string) ($decoded[$locale] ?? $decoded['es'] ?? $decoded['ca'] ?? reset($decoded) ?? '');
    }

    /**
     * CONSISTENTE: Desglose por usuario/app (seller) usando SIEMPRE inscriptions.
     * - count = nº de carritos distintos.
     * - sum = SUM(inscriptions.price_sold).
     * - totalCash/totalCard = sumatorios de price_sold solo cuando gateway=TicketOffice y payment_type coincide.
     */
    private function breakdownByUserOptimized(Carbon $start_date, Carbon $end_date)
    {
        $brand = get_current_brand();

        Log::info('BreakdownByUser: Starting', [
            'brand_id'   => $brand->id,
            'start_date' => $start_date->toDateTimeString(),
            'end_date'   => $end_date->toDateTimeString(),
        ]);

        $results = DB::table('inscriptions')
            ->join('carts', 'inscriptions.cart_id', '=', 'carts.id')
            ->join('payments', function ($join) {
                $join->on('carts.id', '=', 'payments.cart_id')
                    ->whereNotNull('payments.paid_at');
            })
            ->where('carts.brand_id', $brand->id)
            ->whereNotNull('carts.confirmation_code')
            ->whereBetween('payments.paid_at', [$start_date, $end_date])
            ->whereNotNull('carts.seller_type')
            ->whereNotNull('carts.seller_id')
            ->select(
                'carts.seller_type',
                'carts.seller_id',
                DB::raw('COUNT(DISTINCT carts.id) as cart_count'),
                DB::raw('COUNT(DISTINCT inscriptions.id) as inscription_count'),
                DB::raw('SUM(inscriptions.price_sold) as total'),
                DB::raw('SUM(
                    CASE 
                        WHEN payments.gateway = "TicketOffice" 
                         AND JSON_UNQUOTE(JSON_EXTRACT(payments.gateway_response, "$.payment_type")) = "' . PaymentTicketOfficeService::CASH . '"
                    THEN inscriptions.price_sold
                    ELSE 0 
                END
                ) as total_cash'),
                DB::raw('SUM(
                    CASE 
                        WHEN payments.gateway = "TicketOffice" 
                         AND JSON_UNQUOTE(JSON_EXTRACT(payments.gateway_response, "$.payment_type")) = "' . PaymentTicketOfficeService::CARD . '"
                    THEN inscriptions.price_sold
                    ELSE 0 
                END
                ) as total_card')
            )
            ->groupBy('carts.seller_type', 'carts.seller_id')
            ->get();

        Log::info('BreakdownByUser: Query results', [
            'count'  => $results->count(),
            'sample' => $results->take(2)->toArray(),
        ]);

        // Batch de sellers
        $userIds = $results->where('seller_type', 'App\\Models\\User')->pluck('seller_id')->unique();
        $appIds  = $results->where('seller_type', 'App\\Models\\Application')->pluck('seller_id')->unique();

        $users = $userIds->isNotEmpty()
            ? \App\Models\User::whereIn('id', $userIds)->select('id', 'name', 'email')->get()->keyBy('id')
            : collect();

        $apps = $appIds->isNotEmpty()
            ? \App\Models\Application::whereIn('id', $appIds)->select('id', 'code_name')->get()->keyBy('id')
            : collect();

        $balance = $results->map(function ($row) use ($users, $apps, $start_date, $end_date) {
            $seller = null;
            $name   = '';
            $email  = '';

            if ($row->seller_type === 'App\\Models\\User' && isset($users[$row->seller_id])) {
                $seller = $users[$row->seller_id];
                $name   = $seller->name;
                $email  = $seller->email;
            } elseif ($row->seller_type === 'App\\Models\\Application' && isset($apps[$row->seller_id])) {
                $seller = $apps[$row->seller_id];
                $name   = $seller->code_name;
                $email  = ''; // Applications sin email
            }

            if (!$seller) {
                return null;
            }

            $result = [
                'name'      => $name,
                'email'     => $email,
                'from'      => $start_date->toDateString(),
                'to'        => $end_date->toDateString(),
                'count'     => (int) $row->cart_count,
                'totalCash' => round((float) $row->total_cash, 2),
                'totalCard' => round((float) $row->total_card, 2),
                'sum'       => round((float) $row->total, 2),
            ];

            Log::debug('BreakdownByUser: Seller processed', [
                'type'              => $row->seller_type,
                'id'                => $row->seller_id,
                'inscription_count' => (int) $row->inscription_count,
                'cart_count'        => (int) $row->cart_count,
                'result'            => $result,
            ]);

            return $result;
        })->filter()->values();

        return $balance;
    }

    /**
     * Desglose por evento (brand actual + hijos).
     * Traduce el nombre del evento si viene como JSON de locales.
     */
    private function breakdownByEventOptimized(Carbon $start_date, Carbon $end_date)
    {
        $brandId  = get_current_brand()->id;
        $brandIds = $this->getBrandAndChildrenIds();

        Log::info('BreakdownByEvent: Starting', [
            'brand_id'  => $brandId,
            'brand_ids' => $brandIds,
        ]);

        $results = DB::table('inscriptions')
            ->join('sessions', 'inscriptions.session_id', '=', 'sessions.id')
            ->join('events', 'sessions.event_id', '=', 'events.id')
            ->join('carts', 'inscriptions.cart_id', '=', 'carts.id')
            ->join('payments', function ($join) {
                $join->on('carts.id', '=', 'payments.cart_id')
                    ->whereNotNull('payments.paid_at');
            })
            ->where('carts.brand_id', $brandId)
            ->whereNotNull('carts.confirmation_code')
            ->whereBetween('payments.paid_at', [$start_date, $end_date])
            ->whereIn('sessions.brand_id', $brandIds)
            ->select(
                'events.id as event_id',
                'events.name as event_name',
                DB::raw('COUNT(DISTINCT inscriptions.id) as count'),
                DB::raw('SUM(inscriptions.price_sold) as total')
            )
            ->groupBy('events.id', 'events.name')
            ->get();

        $balance = $results->map(function ($row) use ($start_date, $end_date) {
            $eventName = $this->getTranslatedValue($row->event_name);

            $result = [
                'name'  => $eventName,
                'count' => (int) $row->count,
                'sum'   => round((float) $row->total, 2),
                'from'  => $start_date->toDateString(),
                'to'    => $end_date->toDateString(),
            ];

            Log::debug('BreakdownByEvent: Event processed', [
                'event_id'        => $row->event_id,
                'original_name'   => $row->event_name,
                'translated_name' => $eventName,
                'result'          => $result,
            ]);

            return $result;
        })->values();

        return $balance;
    }

    /**
     * Desglose por promotor (brand actual + hijos).
     */
    private function breakdownByPromoterOptimized(Carbon $start_date, Carbon $end_date)
    {
        $brandId  = get_current_brand()->id;
        $brandIds = $this->getBrandAndChildrenIds();

        Log::info('BreakdownByPromoter: Starting', [
            'brand_id'  => $brandId,
            'brand_ids' => $brandIds,
        ]);

        $results = DB::table('inscriptions')
            ->join('sessions', 'inscriptions.session_id', '=', 'sessions.id')
            ->join('brands', 'sessions.brand_id', '=', 'brands.id')
            ->join('carts', 'inscriptions.cart_id', '=', 'carts.id')
            ->join('payments', function ($join) {
                $join->on('carts.id', '=', 'payments.cart_id')
                    ->whereNotNull('payments.paid_at');
            })
            ->where('carts.brand_id', $brandId)
            ->whereNotNull('carts.confirmation_code')
            ->whereBetween('payments.paid_at', [$start_date, $end_date])
            ->whereIn('sessions.brand_id', $brandIds)
            ->select(
                'brands.id',
                'brands.name as brand_name',
                DB::raw('COUNT(DISTINCT inscriptions.id) as count'),
                DB::raw('SUM(inscriptions.price_sold) as total')
            )
            ->groupBy('brands.id', 'brands.name')
            ->get();

        return $results->map(function ($row) use ($start_date, $end_date) {
            return [
                'name'  => $row->brand_name,
                'count' => (int) $row->count,
                'sum'   => round((float) $row->total, 2),
                'from'  => $start_date->toDateString(),
                'to'    => $end_date->toDateString(),
            ];
        })->values();
    }
}
