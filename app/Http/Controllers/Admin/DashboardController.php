<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Client;
use App\Models\Session;
use App\Models\Event;
use App\Models\Inscription;
use App\Models\Payment;
use App\Scopes\BrandScope;
use Backpack\ActivityLog\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $brandId = get_current_brand()->id;
        $capability = get_brand_capability();
        $user = backpack_user();

        // Verificar que el usuario tenga al menos permisos de lectura básicos
        //Los permisos se gestionan en la vista, el user no puede hacer nada sin dashboard.index
        /* if (!$this->canAccessDashboard($user)) {
            abort(403, __('dashboard.no_permissions_dashboard'));
        } */

        // NUEVA LÓGICA: Redirigir según capability
        if ($capability === 'engine') {
            return $this->engineDashboard($brandId, $user);
        }

        // Para basic y promoter, usar el dashboard actual
        return $this->standardDashboard($brandId, $user);
    }

    /**
     * Dashboard para capability 'engine'
     */
    private function engineDashboard($brandId, $user)
    {
        // Preparar datos específicos para engine
        // $metrics = $this->getEngineDashboardMetrics($brandId, $user); // Temp Disabled
        $metrics = [];

        return view(backpack_view('dashboard-engine'), [
            'metrics' => $metrics,
            'permissions' => $this->getUserPermissions($user)
        ]);
    }

    /**
     * Dashboard estándar para 'basic' y 'promoter'
     */
    private function standardDashboard($brandId, $user)
    {
        // Calcular métricas basadas en permisos
        $metrics = $this->getDashboardMetrics($brandId, $user);

        // Obtener historial si existe y tiene permisos
        $history = $this->getUpdateHistory();

        // Pasar los datos a la vista con información de permisos
        return view(backpack_view('dashboard'), [
            'metrics' => $metrics,
            'history' => $history,
            'permissions' => $this->getUserPermissions($user)
        ]);
    }

    /**
     * Obtener métricas específicas para dashboard engine
     */
    private function getEngineDashboardMetrics($brandId, $user)
    {
        $now = Carbon::now();
        $metrics = [];

        // Fechas base
        $dates = [
            'now' => $now,
            'startOfDay' => $now->copy()->startOfDay(),
            'startOfWeek' => $now->copy()->startOfWeek(),
            'startOfMonth' => $now->copy()->startOfMonth(),
            'startOfLastMonth' => $now->copy()->subMonth()->startOfMonth(),
            'endOfLastMonth' => $now->copy()->subMonth()->endOfMonth(),
            'startOfYear' => $now->copy()->startOfYear(),
        ];

        // 1. MÉTRICAS GLOBALES DEL SISTEMA
        $metrics['global'] = $this->getEngineGlobalMetrics($brandId, $dates);

        // 2. ESTADÍSTICAS DEL SISTEMA HOY
        $metrics['system'] = $this->getEngineSystemStats($brandId, $dates, $user);

        // 3. TOP BRANDS POR RENDIMIENTO
        if ($user->can('brands.index')) {
            $metrics['top_brands'] = $this->getEngineTopBrands($brandId, $dates);
        }

        // 4. GRÁFICOS DE EVOLUCIÓN (si tiene permisos)
        if ($user->can('statistics.index')) {
            $metrics['charts'] = $this->getEngineCharts($brandId, $dates);
        }

        // 5. MONITOREO DEL SISTEMA
        $metrics['monitoring'] = $this->getEngineMonitoring($brandId);

        // 6. ACTIVIDAD RECIENTE
        $metrics['recent_activity'] = $this->getEngineRecentActivity($brandId, $dates);

        return $metrics;
    }

    /**
     * Métricas globales del sistema Engine - CORREGIDO PARA VER TODAS LAS BRANDS
     */
    private function getEngineGlobalMetrics($brandId, $dates)
    {
        // Para engine, necesitamos ver TODAS las brands del sistema, no solo las hijas directas
        // Engine no debería estar limitado por BrandScope

        // Obtener TODAS las brands del sistema (excepto la propia engine)
        $allBrands = \App\Models\Brand::where('id', '!=', $brandId)->get();
        $allBrandIds = $allBrands->pluck('id');

        // Contar brands activas (que tienen eventos con sesiones futuras)
        $activeBrands = \DB::table('brands')
            ->where('brands.id', '!=', $brandId)
            ->whereExists(function ($q) use ($dates) {
                $q->select(\DB::raw(1))
                    ->from('events')
                    ->whereColumn('events.brand_id', 'brands.id')
                    ->whereExists(function ($sq) use ($dates) {
                        $sq->select(\DB::raw(1))
                            ->from('sessions')
                            ->whereColumn('sessions.event_id', 'events.id')
                            ->where('sessions.starts_on', '>', $dates['now'])
                            ->whereNull('sessions.deleted_at');
                    })
                    ->whereNull('events.deleted_at');
            })
            ->whereNull('brands.deleted_at')
            ->count();

        // Usar queries directas para evitar BrandScope
        return [
            'total_brands' => $allBrands->count(),
            'active_brands' => $activeBrands,
            'total_users' => \DB::table('brand_user')
                ->distinct('user_id')
                ->count('user_id'),
            'total_events' => \DB::table('events')
                ->whereNull('deleted_at')
                ->count(),
            'active_events' => \DB::table('events')
                ->whereExists(function ($q) use ($dates) {
                    $q->select(\DB::raw(1))
                        ->from('sessions')
                        ->whereColumn('sessions.event_id', 'events.id')
                        ->where('sessions.starts_on', '>', $dates['now'])
                        ->whereNull('sessions.deleted_at');
                })
                ->whereNull('deleted_at')
                ->count(),
            'total_clients' => \DB::table('clients')
                ->whereNull('deleted_at')
                ->count(),
            'total_sessions' => \DB::table('sessions')
                ->whereNull('deleted_at')
                ->count(),
            'total_inscriptions' => \DB::table('inscriptions')
                ->join('carts', 'carts.id', '=', 'inscriptions.cart_id')
                ->whereNotNull('carts.confirmation_code')
                ->whereNull('inscriptions.deleted_at')
                ->count(),
        ];
    }

    /**
     * Estadísticas del sistema para hoy - CORREGIDO PARA ENGINE
     */
    private function getEngineSystemStats($brandId, $dates, $user)
    {
        // Para engine, ver datos de TODO el sistema
        $stats = [
            'carts_today' => \DB::table('payments')
                ->join('carts', 'payments.cart_id', '=', 'carts.id')
                ->whereNotNull('payments.paid_at')
                ->where('payments.paid_at', '>=', $dates['startOfDay'])
                ->whereNull('carts.deleted_at')
                ->count(),
            'sales_today' => \DB::table('carts')
                ->whereNotNull('confirmation_code')
                ->where('created_at', '>=', $dates['startOfDay'])
                ->whereNull('deleted_at')
                ->count(),
            'new_clients_today' => \DB::table('clients')
                ->where('created_at', '>=', $dates['startOfDay'])
                ->whereNull('deleted_at')
                ->count(),
            'active_sessions_now' => \DB::table('sessions')
                ->where('starts_on', '<=', $dates['now'])
                ->where('ends_on', '>=', $dates['now'])
                ->whereNull('deleted_at')
                ->count(),
        ];

        // Calcular ingresos si tiene permisos
        if ($user->can('statistics.index')) {
            // Calcular revenue_today usando SQL directo
            $revenueToday = \DB::table('payments')
                ->join('carts', 'payments.cart_id', '=', 'carts.id')
                ->whereNotNull('payments.paid_at')
                ->where('payments.paid_at', '>=', $dates['startOfDay'])
                ->whereNull('carts.deleted_at')
                ->select('carts.id')
                ->get()
                ->sum(function ($cart) {
                    $inscriptions = \DB::table('inscriptions')
                        ->where('cart_id', $cart->id)
                        ->sum('price_sold');
                    $giftCards = \DB::table('gift_cards')
                        ->where('cart_id', $cart->id)
                        ->sum('price');
                    return $inscriptions + $giftCards;
                });

            $stats['revenue_today'] = $revenueToday;

            // Revenue del mes
            $revenueMonth = \DB::table('payments')
                ->join('carts', 'payments.cart_id', '=', 'carts.id')
                ->whereNotNull('payments.paid_at')
                ->where('payments.paid_at', '>=', $dates['startOfMonth'])
                ->whereNull('carts.deleted_at')
                ->select('carts.id')
                ->get()
                ->sum(function ($cart) {
                    $inscriptions = \DB::table('inscriptions')
                        ->where('cart_id', $cart->id)
                        ->sum('price_sold');
                    $giftCards = \DB::table('gift_cards')
                        ->where('cart_id', $cart->id)
                        ->sum('price');
                    return $inscriptions + $giftCards;
                });

            $stats['revenue_month'] = $revenueMonth;

            // Revenue mes anterior
            $revenueLastMonth = \DB::table('payments')
                ->join('carts', 'payments.cart_id', '=', 'carts.id')
                ->whereNotNull('payments.paid_at')
                ->whereBetween('payments.paid_at', [$dates['startOfLastMonth'], $dates['endOfLastMonth']])
                ->whereNull('carts.deleted_at')
                ->select('carts.id')
                ->get()
                ->sum(function ($cart) {
                    $inscriptions = \DB::table('inscriptions')
                        ->where('cart_id', $cart->id)
                        ->sum('price_sold');
                    $giftCards = \DB::table('gift_cards')
                        ->where('cart_id', $cart->id)
                        ->sum('price');
                    return $inscriptions + $giftCards;
                });

            $stats['revenue_last_month'] = $revenueLastMonth;

            $stats['growth_percentage'] = $stats['revenue_last_month'] > 0
                ? round((($stats['revenue_month'] - $stats['revenue_last_month']) / $stats['revenue_last_month']) * 100, 1)
                : 0;
        }

        return $stats;
    }

    /**
     * Top Brands por diferentes métricas - CORREGIDO PARA VER TODAS LAS BRANDS
     */
    private function getEngineTopBrands($brandId, $dates)
    {
        // Obtener TODAS las brands del sistema excepto engine
        $allBrands = \DB::table('brands')
            ->where('id', '!=', $brandId)
            ->whereNull('deleted_at')
            ->select('id', 'name')
            ->get();

        if ($allBrands->isEmpty()) {
            return [
                'by_revenue' => collect(),
                'by_sales' => collect(),
                'by_clients' => collect(),
                'by_events' => collect(),
            ];
        }

        $brandIds = $allBrands->pluck('id');

        // Obtener datos de ventas usando SQL directo
        $salesData = \DB::table('carts')
            ->whereIn('brand_id', $brandIds)
            ->whereNotNull('confirmation_code')
            ->where('created_at', '>=', $dates['startOfMonth'])
            ->whereNull('deleted_at')
            ->select(
                'brand_id',
                \DB::raw('COUNT(*) as sales_count')
            )
            ->groupBy('brand_id')
            ->get()
            ->keyBy('brand_id');

        // Calcular revenues por brand
        $revenueData = collect();
        foreach ($brandIds as $bid) {
            $revenue = \DB::table('carts')
                ->where('brand_id', $bid)
                ->whereNotNull('confirmation_code')
                ->where('created_at', '>=', $dates['startOfMonth'])
                ->whereNull('deleted_at')
                ->select('id')
                ->get()
                ->sum(function ($cart) {
                    $inscriptions = \DB::table('inscriptions')
                        ->where('cart_id', $cart->id)
                        ->sum('price_sold');
                    $giftCards = \DB::table('gift_cards')
                        ->where('cart_id', $cart->id)
                        ->sum('price');
                    return $inscriptions + $giftCards;
                });

            if ($revenue > 0) {
                $revenueData->put($bid, $revenue);
            }
        }

        // Contar eventos y clientes
        $eventsData = \DB::table('events')
            ->whereIn('brand_id', $brandIds)
            ->whereNull('deleted_at')
            ->select(
                'brand_id',
                \DB::raw('COUNT(*) as events_count'),
                \DB::raw('SUM(CASE WHEN EXISTS(
                    SELECT 1 FROM sessions 
                    WHERE sessions.event_id = events.id 
                    AND sessions.starts_on > NOW()
                    AND sessions.deleted_at IS NULL
                ) THEN 1 ELSE 0 END) as active_events')
            )
            ->groupBy('brand_id')
            ->get()
            ->keyBy('brand_id');

        $clientsData = \DB::table('clients')
            ->whereIn('brand_id', $brandIds)
            ->whereNull('deleted_at')
            ->select(
                'brand_id',
                \DB::raw('COUNT(*) as clients_count'),
                \DB::raw('SUM(CASE WHEN created_at >= "' . $dates['startOfMonth']->toDateTimeString() . '" THEN 1 ELSE 0 END) as new_clients_month')
            )
            ->groupBy('brand_id')
            ->get()
            ->keyBy('brand_id');

        // Combinar datos
        $brandsWithMetrics = $allBrands->map(function ($brand) use ($salesData, $revenueData, $eventsData, $clientsData) {
            $sales = $salesData->get($brand->id);
            $events = $eventsData->get($brand->id);
            $clients = $clientsData->get($brand->id);

            return [
                'brand' => $brand,
                'revenue_month' => $revenueData->get($brand->id, 0),
                'sales_count' => $sales->sales_count ?? 0,
                'events_count' => $events->events_count ?? 0,
                'active_events' => $events->active_events ?? 0,
                'clients_count' => $clients->clients_count ?? 0,
                'new_clients_month' => $clients->new_clients_month ?? 0,
            ];
        });

        return [
            'by_revenue' => $brandsWithMetrics->sortByDesc('revenue_month')->take(5)->values(),
            'by_sales' => $brandsWithMetrics->sortByDesc('sales_count')->take(5)->values(),
            'by_clients' => $brandsWithMetrics->sortByDesc('clients_count')->take(5)->values(),
            'by_events' => $brandsWithMetrics->sortByDesc('active_events')->take(5)->values(),
        ];
    }

    /**
     * Datos para gráficos del dashboard engine - CORREGIDO
     */
    private function getEngineCharts($brandId, $dates)
    {
        // Para engine, necesitamos ver TODAS las brands del sistema
        $allBrands = \App\Models\Brand::where('id', '!=', $brandId)->pluck('id');

        // 1. Evolución de ingresos últimos 30 días - CORREGIDO
        $last30Days = collect(range(29, 0))->map(fn($i) => $dates['now']->copy()->subDays($i));

        $salesEvolution = $last30Days->map(function ($date) use ($allBrands) {
            // Usar SQL directo para evitar BrandScope y obtener TODAS las ventas del sistema
            $dayRevenue = \DB::table('carts')
                ->whereIn('brand_id', $allBrands)
                ->whereNotNull('confirmation_code')
                ->whereDate('created_at', $date)
                ->whereNull('deleted_at')
                ->select('id')
                ->get()
                ->sum(function ($cart) {
                    $inscriptions = \DB::table('inscriptions')
                        ->where('cart_id', $cart->id)
                        ->sum('price_sold');
                    $giftCards = \DB::table('gift_cards')
                        ->where('cart_id', $cart->id)
                        ->sum('price');
                    return $inscriptions + $giftCards;
                });

            $dayCount = \DB::table('carts')
                ->whereIn('brand_id', $allBrands)
                ->whereNotNull('confirmation_code')
                ->whereDate('created_at', $date)
                ->whereNull('deleted_at')
                ->count();

            return [
                'date' => $date->format('d/m'),
                'revenue' => $dayRevenue,
                'count' => $dayCount,
            ];
        });

        // 2. Distribución de ingresos por brand (este mes) - CORREGIDO
        $revenueByBrand = \App\Models\Brand::whereIn('id', $allBrands)
            ->get()
            ->map(function ($brand) use ($dates) {
                // Calcular revenue usando SQL directo
                $revenue = \DB::table('carts')
                    ->where('brand_id', $brand->id)
                    ->whereNotNull('confirmation_code')
                    ->where('created_at', '>=', $dates['startOfMonth'])
                    ->whereNull('deleted_at')
                    ->select('id')
                    ->get()
                    ->sum(function ($cart) {
                        $inscriptions = \DB::table('inscriptions')
                            ->where('cart_id', $cart->id)
                            ->sum('price_sold');
                        $giftCards = \DB::table('gift_cards')
                            ->where('cart_id', $cart->id)
                            ->sum('price');
                        return $inscriptions + $giftCards;
                    });

                return [
                    'brand' => $brand->name,
                    'revenue' => $revenue,
                ];
            })
            ->filter(fn($item) => $item['revenue'] > 0)
            ->sortByDesc('revenue')
            ->take(10) // Limitar a top 10 brands
            ->values();

        // 3. Crecimiento de brands y eventos (últimos 12 meses)
        $growthData = collect(range(11, 0))->map(function ($monthsAgo) use ($allBrands) {
            $date = Carbon::now()->subMonths($monthsAgo);

            return [
                'month' => $date->format('M Y'),
                'brands' => \DB::table('brands')
                    ->whereIn('id', $allBrands)
                    ->where('created_at', '<=', $date->endOfMonth())
                    ->whereNull('deleted_at')
                    ->count(),
                'events' => \DB::table('events')
                    ->whereIn('brand_id', $allBrands)
                    ->where('created_at', '<=', $date->endOfMonth())
                    ->whereNull('deleted_at')
                    ->count(),
            ];
        });

        return [
            'salesEvolution' => $salesEvolution,
            'revenueByBrand' => $revenueByBrand,
            'growthData' => $growthData,
        ];
    }

    /**
     * Información de monitoreo del sistema
     */
    private function getEngineMonitoring($brandId)
    {
        $monitoring = [
            'jobs' => [
                'pending' => 0,
                'failed' => 0,
                'failed_today' => 0,
            ],
            'applications' => [
                'total' => 0,
            ],
            'logs' => [
                'errors_today' => 0,
                'last_activity' => null,
            ],
        ];

        // Jobs - verificar si las tablas existen
        if (\Schema::hasTable('jobs')) {
            $monitoring['jobs']['pending'] = \DB::table('jobs')->count();
        }

        if (\Schema::hasTable('failed_jobs')) {
            $monitoring['jobs']['failed'] = \DB::table('failed_jobs')->count();
            $monitoring['jobs']['failed_today'] = \DB::table('failed_jobs')
                ->whereDate('failed_at', Carbon::today())
                ->count();
        }

        // Applications - sin campo 'active'
        if (class_exists('\App\Models\Application')) {
            $monitoring['applications']['total'] = \App\Models\Application::where('brand_id', $brandId)->count();
        }

        // Activity Log - verificar si existe
        if (class_exists('\App\Models\ActivityLog')) {
            $monitoring['logs']['last_activity'] = ActivityLog::latest()->first();
        }

        return $monitoring;
    }

    /**
     * Actividad reciente del sistema - OPTIMIZADO
     */
    private function getEngineRecentActivity($brandId, $dates)
    {
        $childBrandIds = \App\Models\Brand::where('parent_id', $brandId)->pluck('id');
        $allBrandIds = $childBrandIds->push($brandId);

        return [
            'recent_brands' => \App\Models\Brand::whereIn('id', $childBrandIds)
                ->select('id', 'name', 'created_at')
                ->latest()
                ->limit(5)
                ->get(),

            'recent_events' => Event::whereIn('brand_id', $allBrandIds)
                ->select('id', 'name', 'brand_id', 'created_at')
                ->with('brand:id,name')
                ->latest()
                ->limit(5)
                ->get(),

            'recent_sales' => Cart::whereIn('brand_id', $allBrandIds)
                ->whereNotNull('confirmation_code')
                ->select('id', 'brand_id', 'client_id', 'confirmation_code', 'created_at')
                ->with([
                    'brand:id,name',
                    'client:id,email'
                ])
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($cart) {
                    // Calcular price_sold solo para los carritos mostrados
                    $revenue = \DB::table('inscriptions')
                        ->where('cart_id', $cart->id)
                        ->sum('price_sold');

                    $giftCardRevenue = \DB::table('gift_cards')
                        ->where('cart_id', $cart->id)
                        ->sum('price');

                    return [
                        'cart' => $cart,
                        'revenue' => $revenue + $giftCardRevenue,
                        'brand_name' => $cart->brand->name ?? 'N/A',
                        'client_email' => $cart->client->email ?? 'N/A',
                    ];
                }),
        ];
    }

    /**
     * Verificar si el usuario puede acceder al dashboard
     */
    private function canAccessDashboard($user)
    {
        // El dashboard es accesible si tiene al menos uno de estos permisos de lectura
        $readPermissions = [
            'events.index',
            'sessions.index',
            'carts.index',
            'clients.index',
            'inscriptions.index',
            'statistics.index',
            'brands.index',
        ];

        foreach ($readPermissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtener permisos del usuario para el frontend
     */
    private function getUserPermissions($user)
    {
        return [
            'canViewSales' => $user->can('carts.index') || $user->can('statistics.index'),
            'canViewEvents' => $user->can('events.index'),
            'canViewSessions' => $user->can('sessions.index'),
            'canViewClients' => $user->can('clients.index'),
            'canViewStatistics' => $user->can('statistics.index'),
            'canCreateTicketOffice' => $user->can('carts.create'),
            'canViewValidations' => $user->can('validations.index'),
            'canViewMailings' => $user->can('mailings.index'),
            'canViewCodes' => $user->can('codes.index'),
            'canViewGiftCards' => $user->can('gift_cards.index'),
            'canViewBrands' => $user->can('brands.index'),
        ];
    }

    /**
     * Obtener todas las métricas del dashboard basadas en permisos
     */
    private function getDashboardMetrics($brandId, $user)
    {
        $now = Carbon::now();
        $metrics = [];

        // Fechas base
        $dates = [
            'now' => $now,
            'startOfDay' => $now->copy()->startOfDay(),
            'startOfWeek' => $now->copy()->startOfWeek(),
            'startOfMonth' => $now->copy()->startOfMonth(),
            'startOfLastMonth' => $now->copy()->subMonth()->startOfMonth(),
            'endOfLastMonth' => $now->copy()->subMonth()->endOfMonth(),
        ];

        // KPIs PRINCIPALES - Solo si puede ver ventas o estadísticas
        if ($user->can('carts.index') || $user->can('statistics.index')) {
            $metrics['kpis'] = $this->getKPIs($brandId, $dates);
        } else {
            $metrics['kpis'] = $this->getDefaultKPIs();
        }

        // DATOS PARA GRÁFICAS - Solo si puede ver estadísticas
        if ($user->can('statistics.index')) {
            $metrics['charts'] = $this->getChartsData($brandId, $dates);
        } else {
            $metrics['charts'] = $this->getDefaultCharts();
        }

        // TABLAS Y LISTADOS - Filtrados por permisos
        $metrics['tables'] = $this->getTablesData($brandId, $dates, $user);

        // ANÁLISIS ADICIONALES - Solo si puede ver estadísticas
        if ($user->can('statistics.index')) {
            $metrics['analysis'] = $this->getAnalysisData($brandId, $dates);
        } else {
            $metrics['analysis'] = $this->getDefaultAnalysis();
        }

        return $metrics;
    }

    /**
     * Obtener KPIs principales con verificación de permisos
     */
    private function getKPIs($brandId, $dates)
    {
        $user = backpack_user();
        $kpis = [];

        // Si puede ver carritos o estadísticas, mostrar ventas
        if ($user->can('carts.index') || $user->can('statistics.index')) {
            // Ventas de hoy
            $salesToday = Cart::whereBrandId($brandId)
                ->confirmed()
                ->where('created_at', '>=', $dates['startOfDay'])
                ->get();

            // Ventas de la semana
            $salesWeek = Cart::whereBrandId($brandId)
                ->confirmed()
                ->where('created_at', '>=', $dates['startOfWeek'])
                ->get();

            // Ventas del mes
            $salesMonth = Cart::whereBrandId($brandId)
                ->confirmed()
                ->where('created_at', '>=', $dates['startOfMonth'])
                ->get();

            $kpis['today'] = [
                'revenue' => $salesToday->sum(fn($cart) => $cart->price_sold),
                'count' => $salesToday->count(),
            ];

            $kpis['week'] = [
                'revenue' => $salesWeek->sum(fn($cart) => $cart->price_sold),
                'count' => $salesWeek->count(),
            ];

            $kpis['month'] = [
                'revenue' => $salesMonth->sum(fn($cart) => $cart->price_sold),
                'count' => $salesMonth->count(),
            ];
        } else {
            // Valores por defecto ocultos
            $kpis['today'] = ['revenue' => 0, 'count' => 0, 'hidden' => true];
            $kpis['week'] = ['revenue' => 0, 'count' => 0, 'hidden' => true];
            $kpis['month'] = ['revenue' => 0, 'count' => 0, 'hidden' => true];
        }

        // Métricas adicionales solo si tiene permisos de estadísticas
        if ($user->can('statistics.index')) {
            // Tasa de conversión
            $totalCartsMonth = Cart::whereBrandId($brandId)
                ->where('created_at', '>=', $dates['startOfMonth'])
                ->count();

            $salesMonth = isset($kpis['month']) ?
                Cart::whereBrandId($brandId)
                ->confirmed()
                ->where('created_at', '>=', $dates['startOfMonth'])
                ->count() : 0;

            $kpis['conversionRate'] = $totalCartsMonth > 0
                ? round(($salesMonth / $totalCartsMonth) * 100, 1)
                : 0;

            // Ticket medio
            $revenueMonth = $kpis['month']['revenue'] ?? 0;
            $countMonth = $kpis['month']['count'] ?? 0;
            $kpis['averageTicket'] = $countMonth > 0
                ? round($revenueMonth / $countMonth, 2)
                : 0;
        } else {
            $kpis['conversionRate'] = 0;
            $kpis['averageTicket'] = 0;
            $kpis['hideAdvancedMetrics'] = true;
        }

        // Próximos eventos activos - Solo si puede ver eventos
        if ($user->can('events.index')) {
            $kpis['upcomingEvents'] = Event::whereBrandId($brandId)
                ->whereHas('sessions', function ($q) use ($dates) {
                    $q->where('starts_on', '>', $dates['now'])
                        ->where('inscription_ends_on', '>', $dates['now']);
                })
                ->count();
        } else {
            $kpis['upcomingEvents'] = 0;
            $kpis['hideEvents'] = true;
        }

        return $kpis;
    }

    /**
     * Obtener datos para las gráficas con verificación de permisos
     */
    private function getChartsData($brandId, $dates)
    {
        $user = backpack_user();
        $charts = [];

        // Solo mostrar gráficas si tiene permisos de estadísticas
        if (!$user->can('statistics.index')) {
            return $this->getDefaultCharts();
        }

        // 1. Evolución de ventas últimos 30 días
        if ($user->can('carts.index') || $user->can('statistics.index')) {
            $last30Days = collect(range(29, 0))->map(fn($i) => $dates['now']->copy()->subDays($i));

            $salesLast30Raw = Cart::whereBrandId($brandId)
                ->confirmed()
                ->where('created_at', '>=', $dates['now']->copy()->subDays(30))
                ->get()
                ->groupBy(fn($cart) => $cart->created_at->format('Y-m-d'));

            $charts['salesEvolution'] = $last30Days->map(function ($date) use ($salesLast30Raw) {
                $dateKey = $date->format('Y-m-d');
                $daySales = $salesLast30Raw->get($dateKey, collect());

                return [
                    'date' => $date->format('d/m'),
                    'fullDate' => $dateKey,
                    'revenue' => $daySales->sum(fn($cart) => $cart->price_sold),
                    'count' => $daySales->count(),
                ];
            });
        } else {
            $charts['salesEvolution'] = collect();
        }

        // 2. Ventas por canal (Este mes) - Solo con permisos avanzados
        if ($user->can('statistics.index') && get_brand_capability() != 'promoter') {
            $charts['salesByChannel'] = Payment::query()
                ->join('carts', 'payments.cart_id', '=', 'carts.id')
                ->where('carts.brand_id', $brandId)
                ->whereNotNull('payments.paid_at')
                ->where('payments.created_at', '>=', $dates['startOfMonth'])
                ->selectRaw("
                    CASE 
                        WHEN gateway = 'TicketOffice' THEN '" . __('dashboard.channel_box_office') . "'
                        WHEN gateway IN ('Sermepa', 'SermepaSoapService', 'RedsysSoapService', 'Redsys Redirect') THEN '" . __('dashboard.channel_web') . "'
                        WHEN gateway = 'Free' THEN '" . __('dashboard.channel_free') . "'
                        ELSE '" . __('dashboard.channel_others') . "'
                    END as channel,
                    COUNT(*) as count,
                    SUM(payments.gateway_amount) as total
                ")
                ->groupBy('channel')
                ->get()
                ->keyBy('channel');
        } else {
            $charts['salesByChannel'] = collect();
        }

        // 3. Comparativa mes actual vs mes anterior
        if ($user->can('statistics.index')) {
            $salesLastMonth = Cart::whereBrandId($brandId)
                ->confirmed()
                ->whereBetween('created_at', [$dates['startOfLastMonth'], $dates['endOfLastMonth']])
                ->get();

            $charts['monthComparison'] = [
                'current' => [
                    'revenue' => $charts['salesEvolution']->sum('revenue'),
                    'count' => $charts['salesEvolution']->sum('count'),
                ],
                'previous' => [
                    'revenue' => $salesLastMonth->sum(fn($cart) => $cart->price_sold),
                    'count' => $salesLastMonth->count(),
                ],
            ];
        }

        return $charts;
    }

    /**
     * Obtener datos para las tablas con verificación de permisos
     */
    private function getTablesData($brandId, $dates, $user)
    {
        $tables = [];
        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale');

        // 1. Top 5 Eventos por ventas - Solo si puede ver eventos
        if ($user->can('events.index')) {
            $tables['topEvents'] = Inscription::query()->withoutGlobalScope(BrandScope::class)
                ->join('sessions', 'inscriptions.session_id', '=', 'sessions.id')
                ->join('events', 'sessions.event_id', '=', 'events.id')
                ->join('carts', 'inscriptions.cart_id', '=', 'carts.id')
                ->where('events.brand_id', $brandId)
                ->whereNotNull('carts.confirmation_code')
                ->where('sessions.starts_on', '>', $dates['now']->copy()->subMonths(3))
                ->selectRaw('
                    events.id,
                    COALESCE(
                        JSON_UNQUOTE(JSON_EXTRACT(events.name, "$.\\"' . $locale . '\\"")),
                        JSON_UNQUOTE(JSON_EXTRACT(events.name, "$.\\"' . $fallback . '\\"")),
                        JSON_UNQUOTE(JSON_EXTRACT(events.name, "$.*[0]")),
                        events.name
                    ) AS name,
                    COUNT(DISTINCT inscriptions.id) as total_sales,
                    SUM(inscriptions.price_sold) as total_revenue,
                    COUNT(DISTINCT carts.client_id) as unique_buyers
                ')
                ->groupBy('events.id', 'events.name')
                ->orderByDesc('total_revenue')
                ->limit(5)
                ->get();

            // Si no puede ver estadísticas de ventas, ocultar columnas financieras
            if (!$user->can('statistics.index')) {
                $tables['topEvents'] = $tables['topEvents']->map(function ($event) {
                    $event->hideFinancials = true;
                    return $event;
                });
            }
        } else {
            $tables['topEvents'] = collect();
        }

        // 2. Ocupación próximas sesiones - Solo si puede ver sesiones
        if ($user->can('sessions.index')) {
            $tables['upcomingSessions'] = Session::whereBrandId($brandId)
                ->where('starts_on', '>', $dates['now'])
                ->where('inscription_ends_on', '>', $dates['now'])
                ->with(['event', 'space.location.city'])
                ->orderBy('starts_on')
                ->limit(10)
                ->get()
                ->map(function ($session) {
                    $sold = $session->countBlockedInscriptions();
                    $capacity = $session->max_places ?? 0;
                    $occupancy = $capacity > 0 ? round(($sold / $capacity) * 100, 1) : 0;

                    return [
                        'id' => $session->id,
                        'name' => $session->name ?: $session->event->name,
                        'event_name' => $session->event->name ?? '-',
                        'date' => $session->starts_on->format('d/m/Y'),
                        'time' => $session->starts_on->format('H:i'),
                        'space' => $session->space->name ?? '-',
                        'city' => $session->space->location->city->name ?? '-',
                        'occupancy' => $occupancy,
                        'sold' => $sold,
                        'capacity' => $capacity,
                        'available' => max(0, $capacity - $sold),
                        'status' => $this->getSessionStatus($occupancy),
                    ];
                });
        } else {
            $tables['upcomingSessions'] = collect();
        }

        // 3. Top 10 Clientes - Solo si puede ver clientes Y estadísticas
        if ($user->can('clients.index') && $user->can('statistics.index')) {
            $tables['topCustomers'] = DB::table('clients')
                ->join('carts', 'clients.id', '=', 'carts.client_id')
                ->join('inscriptions', 'carts.id', '=', 'inscriptions.cart_id')
                ->where('clients.brand_id', $brandId)
                ->whereNotNull('carts.confirmation_code')
                ->where('carts.created_at', '>=', $dates['now']->copy()->subMonths(6))
                ->select(
                    'clients.id',
                    'clients.name',
                    'clients.surname',
                    'clients.email',
                    DB::raw('COUNT(DISTINCT carts.id) as total_purchases'),
                    DB::raw('SUM(inscriptions.price_sold) as total_spent'),
                    DB::raw('MAX(carts.created_at) as last_purchase')
                )
                ->groupBy('clients.id', 'clients.name', 'clients.surname', 'clients.email')
                ->orderByDesc('total_spent')
                ->limit(10)
                ->get();
        } else {
            $tables['topCustomers'] = collect();
        }

        // 4. Sesiones críticas - Solo si puede ver sesiones
        if ($user->can('sessions.index')) {
            $tables['criticalSessions'] = Session::whereBrandId($brandId)
                ->where('starts_on', '>', $dates['now'])
                ->where('inscription_ends_on', '>', $dates['now'])
                ->with(['event'])
                ->get()
                ->map(function ($session) {
                    $available = $session->getFreePositions();
                    $capacity = $session->max_places ?? 0;
                    $percentAvailable = $capacity > 0 ? ($available / $capacity) * 100 : 0;

                    return [
                        'session' => $session,
                        'available' => $available,
                        'percent_available' => $percentAvailable,
                    ];
                })
                ->filter(fn($item) => $item['percent_available'] < 20 && $item['percent_available'] > 0)
                ->sortBy('percent_available')
                ->take(5)
                ->values();
        } else {
            $tables['criticalSessions'] = collect();
        }

        return $tables;
    }

    /**
     * Obtener datos de análisis adicionales con verificación de permisos
     */
    private function getAnalysisData($brandId, $dates)
    {
        $user = backpack_user();
        $analysis = [];

        // Solo mostrar análisis si tiene permisos de estadísticas
        if (!$user->can('statistics.index')) {
            return $this->getDefaultAnalysis();
        }

        // 1. Clientes nuevos vs recurrentes - Solo si puede ver clientes
        if ($user->can('clients.index')) {
            $analysis['customers'] = [
                'new' => Client::whereBrandId($brandId)
                    ->where('created_at', '>=', $dates['startOfMonth'])
                    ->count(),

                'recurring' => Cart::whereBrandId($brandId)
                    ->confirmed()
                    ->where('created_at', '>=', $dates['startOfMonth'])
                    ->whereHas('client', function ($q) use ($dates) {
                        $q->where('created_at', '<', $dates['startOfMonth']);
                    })
                    ->distinct('client_id')
                    ->count('client_id'),

                'total' => Client::whereBrandId($brandId)->count(),
            ];
        } else {
            $analysis['customers'] = [
                'new' => 0,
                'recurring' => 0,
                'total' => 0,
                'hidden' => true
            ];
        }

        // 2. Carritos abandonados - Solo si puede ver carritos
        if ($user->can('carts.index')) {
            $abandonedCartsWeek = Cart::whereBrandId($brandId)
                ->whereNull('confirmation_code')
                ->where('created_at', '>=', $dates['startOfWeek'])
                ->where('expires_on', '<', $dates['now'])
                ->withInscriptions()
                ->get();

            $abandonedCartsMonth = Cart::whereBrandId($brandId)
                ->whereNull('confirmation_code')
                ->where('created_at', '>=', $dates['startOfMonth'])
                ->where('expires_on', '<', $dates['now'])
                ->withInscriptions()
                ->count();

            $analysis['abandonedCarts'] = [
                'count' => $abandonedCartsWeek->count(),
                'value' => $abandonedCartsWeek->sum(fn($cart) => $cart->price_sold),
                'thisMonth' => $abandonedCartsMonth,
            ];
        } else {
            $analysis['abandonedCarts'] = [
                'count' => 0,
                'value' => 0,
                'thisMonth' => 0,
                'hidden' => true
            ];
        }

        // 3. Horarios de mayor venta - Solo con permisos de estadísticas
        if ($user->can('statistics.index') && $user->can('carts.index')) {
            $analysis['peakHours'] = Cart::whereBrandId($brandId)
                ->join('payments', 'carts.id', '=', 'payments.cart_id')
                ->whereNotNull('payments.paid_at')
                ->where('payments.paid_at', '>=', $dates['now']->copy()->subDays(7))
                ->select('carts.*', 'payments.paid_at')
                ->get()
                ->groupBy(fn($cart) => \Carbon\Carbon::parse($cart->paid_at)->format('H'))
                ->map(fn($carts, $hour) => [
                    'hour' => $hour . ':00',
                    'count' => $carts->count(),
                    'revenue' => $carts->sum(fn($cart) => $cart->price_sold),
                ])
                ->sortByDesc('count')
                ->take(5)
                ->values();
        } else {
            $analysis['peakHours'] = collect();
        }

        // 4. Tarifas más vendidas - Solo si puede ver tarifas
        if ($user->can('rates.index')) {
            $locale = app()->getLocale();
            $fallback = config('app.fallback_locale');
            $analysis['topRates'] = DB::table('inscriptions')
                ->join('rates', 'inscriptions.rate_id', '=', 'rates.id')
                ->join('carts', 'inscriptions.cart_id', '=', 'carts.id')
                ->join('payments', 'carts.id', '=', 'payments.cart_id')
                ->join('sessions', 'inscriptions.session_id', '=', 'sessions.id')
                ->where('sessions.brand_id', $brandId)
                ->whereNotNull('payments.paid_at')
                ->where('payments.paid_at', '>=', $dates['startOfMonth'])
                ->select(
                    'rates.id',
                    DB::raw("
                        COALESCE(
                            JSON_UNQUOTE(JSON_EXTRACT(rates.name, '$.\"{$locale}\"')),
                            JSON_UNQUOTE(JSON_EXTRACT(rates.name, '$.\"{$fallback}\"')),
                            JSON_UNQUOTE(JSON_EXTRACT(rates.name, '$.*[0]'))
                        ) AS name
                    "),
                    DB::raw('COUNT(inscriptions.id) as times_sold'),
                    DB::raw('SUM(inscriptions.price_sold) as total_revenue'),
                    DB::raw('AVG(inscriptions.price_sold) as avg_price')
                )
                ->groupBy('rates.id', 'rates.name')
                ->orderByDesc('times_sold')
                ->limit(5)
                ->get();
        } else {
            $analysis['topRates'] = collect();
        }

        // 5. Métodos de pago más usados - Solo con permisos de estadísticas
        if ($user->can('statistics.index')) {
            $analysis['paymentMethods'] = Payment::query()
                ->join('carts', 'payments.cart_id', '=', 'carts.id')
                ->where('carts.brand_id', $brandId)
                ->whereNotNull('payments.paid_at')
                ->where('payments.created_at', '>=', $dates['startOfMonth'])
                ->select('gateway', DB::raw('COUNT(*) as count'))
                ->groupBy('gateway')
                ->get()
                ->keyBy('gateway');
        } else {
            $analysis['paymentMethods'] = collect();
        }

        return $analysis;
    }

    /**
     * Obtener valores por defecto para KPIs cuando no hay permisos
     */
    private function getDefaultKPIs()
    {
        return [
            'today' => ['revenue' => 0, 'count' => 0, 'hidden' => true],
            'week' => ['revenue' => 0, 'count' => 0, 'hidden' => true],
            'month' => ['revenue' => 0, 'count' => 0, 'hidden' => true],
            'conversionRate' => 0,
            'averageTicket' => 0,
            'upcomingEvents' => 0,
            'hideAll' => true
        ];
    }

    /**
     * Obtener valores por defecto para gráficas cuando no hay permisos
     */
    private function getDefaultCharts()
    {
        return [
            'salesEvolution' => collect(),
            'salesByChannel' => collect(),
            'monthComparison' => ['current' => ['revenue' => 0, 'count' => 0], 'previous' => ['revenue' => 0, 'count' => 0]],
            'hidden' => true
        ];
    }

    /**
     * Obtener valores por defecto para análisis cuando no hay permisos
     */
    private function getDefaultAnalysis()
    {
        return [
            'customers' => ['new' => 0, 'recurring' => 0, 'total' => 0, 'hidden' => true],
            'abandonedCarts' => ['count' => 0, 'value' => 0, 'thisMonth' => 0, 'hidden' => true],
            'peakHours' => collect(),
            'topRates' => collect(),
            'paymentMethods' => collect(),
            'hidden' => true
        ];
    }

    /**
     * Determinar el estado de una sesión según su ocupación
     */
    private function getSessionStatus($occupancy)
    {
        if ($occupancy >= 95) return 'critical';
        if ($occupancy >= 80) return 'warning';
        if ($occupancy >= 50) return 'good';
        return 'low';
    }

    /**
     * Obtener historial de actualizaciones
     */
    private function getUpdateHistory()
    {
        $history = Auth::user()->updateNotifications()->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return $history;
    }
}
