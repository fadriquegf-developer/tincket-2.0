<?php

namespace App\Http\Controllers\ApiBackend\Statistics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class StatisticsSalesController extends Controller
{
    /**
     * GET /api/statistics/sales
     *
     * Parámetros esperados (desde sales.js):
     * - session_id: string "1,2,3" (opcional)
     * - breakdown: string "R" | "P" | "U" | "T"
     * - summary: 0|1 (si 1, devolvemos sólo agregados)
     * - sales_range: JSON {"from": <epoch_ms>, "to": <epoch_ms>} (opcional)
     * - page, per_page (opcional) para paginar detalle
     */
    public function get(Request $request)
    {
        // ---- 1) Parseo de filtros ----
        $sessionIds = collect(explode(',', trim((string) $request->get('session_id', ''))))
            ->filter(fn($v) => $v !== '')
            ->map(fn($v) => (int) $v)
            ->values();

        $breakdown = (string) $request->get('breakdown', 'R'); // R|P|U|T
        $summary   = (bool) $request->boolean('summary', false);

        $range     = $this->parseRange($request->get('sales_range'));
        $from      = $range['from'] ?? null; // Carbon|null
        $to        = $range['to']   ?? null; // Carbon|null

        // Paginación para detalle (si el front no la usa, al menos capea el tamaño)
        $perPage   = (int) $request->get('per_page', 5000);
        $perPage   = max(1, min(10000, $perPage)); // límite duro para no matar el navegador

        // Locale para extraer cadenas JSON traducidas
        $locale = app()->getLocale() ?: 'es';

        // 2) Subconsulta del ÚLTIMO pago por cart SIN funciones ventana (compatible MySQL/MariaDB)
        $latestPayments = DB::table('payments as p')
            ->select(
                'p.id',
                'p.cart_id',
                'p.gateway',
                'p.gateway_response',
                'p.paid_at'
            )
            ->whereNull('p.deleted_at')
            ->whereRaw("
                p.id = (
                    SELECT p2.id
                    FROM payments p2
                    WHERE p2.cart_id = p.cart_id
                    AND p2.deleted_at IS NULL
                    ORDER BY p2.paid_at DESC, p2.id DESC
                    LIMIT 1
                )
            ");

        // 3) Query base (reutiliza lo demás igual que lo tenías)
        $base = DB::table('inscriptions as i')
            ->join('sessions as s', 's.id', '=', 'i.session_id')
            ->leftJoin('rates as r', 'r.id', '=', 'i.rate_id')
            ->leftJoin('group_packs as gp', 'gp.id', '=', 'i.group_pack_id')
            ->leftJoin('packs as pk', 'pk.id', '=', 'gp.pack_id')
            ->join('carts as c', 'c.id', '=', 'i.cart_id')
            ->join('events as e', 'e.id', '=', 's.event_id')
            ->leftJoin(DB::raw('(' . $latestPayments->toSql() . ') as lp'), 'lp.cart_id', '=', 'c.id')
            ->mergeBindings($latestPayments)
            ->leftJoin('payments as pay', 'pay.id', '=', 'lp.id')
            ->leftJoin('users as u', 'u.id', '=', 'c.seller_id')
            ->leftJoin('clients as cl', 'cl.id', '=', 'c.client_id')
            ->whereNotNull('pay.paid_at');

        // ---- 4) Filtros ----
        if ($sessionIds->isNotEmpty()) {
            $base->whereIn('i.session_id', $sessionIds);
        }
        if ($from) {
            $base->where('pay.paid_at', '>=', $from);
        }
        if ($to) {
            // inclusivo hasta fin del día del "to"
            $base->where('pay.paid_at', '<', $to->copy()->addDay()->startOfDay());
        }

        // ---- 5) Selección de columnas mínimas ----
        $jsonLocalePath = '$."' . str_replace('"', '\"', $locale) . '"';

        $ticketPaymentType = "JSON_UNQUOTE(JSON_EXTRACT(pay.gateway_response, '$.payment_type'))";

        $detailSelect = [
            DB::raw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(r.name, '$jsonLocalePath')), r.name, '') as rate_name"),
            DB::raw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(pk.name, '$jsonLocalePath')), pk.name, '') as pack_name"),
            DB::raw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(s.name, '$jsonLocalePath')), s.name, '') as session_name"),
            DB::raw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(e.name, '$jsonLocalePath')), e.name, '') as event_name"),
            's.starts_on as session_starts_on',
            'pay.paid_at',
            'i.price_sold',
            'i.group_pack_id as group_pack_id',
            'pk.id as pack_id',
            'c.id as cart_id',
            'c.confirmation_code',
            'cl.email as client_email',
            DB::raw("CASE
           WHEN u.id IS NOT NULL THEN COALESCE(u.name,'—')
           ELSE 'Web API'
         END AS seller_name"),
            'pay.gateway as payment_method',
            DB::raw("$ticketPaymentType as ticket_payment_type"),
        ];


        // ---- 6) Resumen agregado en SQL cuando summary=1 ----
        if ($summary) {
            $summaryRows = $this->buildSummarySQL((clone $base), $breakdown, $jsonLocalePath, $ticketPaymentType)->get();

            return response()->json([
                'results' => [],
                'summary' => $summaryRows,
            ]);
        }

        // ---- 7) Detalle (con cap/paginación) ----
        $rows = (clone $base)
            ->select($detailSelect)
            ->limit($perPage)
            ->get();

        return response()->json([
            'results' => $rows,
        ]);
    }

    /**
     * Construye el query de summary según breakdown.
     *
     * R: por tarifa/pack
     * P: por método de pago (gateway)
     * U: por vendedor
     * T: pagos de TicketOffice por tipo (gateway_response.payment_type)
     */
    private function buildSummarySQL($base, string $bk, string $jsonLocalePath, string $ticketPaymentType)
    {
        // Expresiones que usaremos en SELECT/GROUP BY (compatibles con ONLY_FULL_GROUP_BY)
        $sellerExpr = "CASE
                 WHEN u.id IS NOT NULL THEN COALESCE(u.name,'—')
                 ELSE 'Web API'
               END";
        $methodExpr    = "COALESCE(pay.gateway, '—')";
        $ratePackExpr  = "
            COALESCE(
                NULLIF(COALESCE(JSON_EXTRACT(r.name, '$jsonLocalePath'), r.name, ''), ''),
                COALESCE(JSON_EXTRACT(pk.name, '$jsonLocalePath'), pk.name, ''),
                '—'
            )
        ";

        switch (strtoupper($bk)) {
            case 'U': // Vendedor
                return $base->selectRaw("
                        $sellerExpr AS name,
                        COUNT(*)    AS count,
                        SUM(i.price_sold) AS amount
                    ")
                    ->groupByRaw($sellerExpr)
                    ->orderByRaw('COUNT(*) DESC');

            case 'T': // TicketOffice por tipo
                return $base->where('pay.gateway', '=', 'TicketOffice')
                    ->selectRaw("
                        $ticketPaymentType AS name,
                        COUNT(*)    AS count,
                        SUM(i.price_sold) AS amount
                    ")
                    ->groupByRaw($ticketPaymentType)
                    ->orderByRaw('COUNT(*) DESC');

            case 'P': // Método de pago
                return $base->selectRaw("
                        $methodExpr AS name,
                        COUNT(*)    AS count,
                        SUM(i.price_sold) AS amount
                    ")
                    ->groupByRaw($methodExpr)
                    ->orderByRaw('COUNT(*) DESC');

            case 'R': // Tarifa/Pack (default)
            default:
                return $base->selectRaw("
                        $ratePackExpr AS name,
                        COUNT(*)      AS count,
                        SUM(i.price_sold) AS amount
                    ")
                    ->groupByRaw($ratePackExpr)
                    ->orderByRaw('COUNT(*) DESC');
        }
    }


    /**
     * sales_range: JSON {"from": <epoch_ms>, "to": <epoch_ms>}
     * Devuelve ['from' => Carbon|null, 'to' => Carbon|null]
     */
    private function parseRange($raw): array
    {
        if (empty($raw)) return ['from' => null, 'to' => null];

        $arr = is_array($raw) ? $raw : (json_decode((string) $raw, true) ?: []);
        $from = null;
        $to   = null;

        if (isset($arr['from']) && is_numeric($arr['from'])) {
            $from = Carbon::createFromTimestampMs($arr['from'])->startOfDay();
        }
        if (isset($arr['to']) && is_numeric($arr['to'])) {
            $to = Carbon::createFromTimestampMs($arr['to'])->endOfDay();
        }

        return ['from' => $from, 'to' => $to];
    }
}
