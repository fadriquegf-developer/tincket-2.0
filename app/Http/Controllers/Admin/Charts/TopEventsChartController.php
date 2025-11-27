<?php

namespace App\Http\Controllers\Admin\Charts;

use App\Models\Inscription;
use Carbon\Carbon;
use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use Illuminate\Support\Str;

class TopEventsChartController extends BaseChartController
{
    /**
     * Verificar permisos específicos para esta gráfica
     */
    public function hasPermission(): bool
    {
        $user = backpack_user();
        
        // Requiere poder ver eventos como mínimo
        if (!$this->canViewEvents()) {
            return false;
        }
        
        // Si solo puede ver eventos pero no estadísticas, 
        // permitir pero sin datos financieros
        $this->showFinancials = $this->canViewStatistics() || $this->canViewFinancials();
        
        return true;
    }

    /**
     * Setup específico del chart
     */
    protected function setupChart()
    {
        $this->chart = new Chart();

        $brandId = $this->getBrandId();
        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale');

        // Query base
        $query = Inscription::query()
            ->join('sessions', 'inscriptions.session_id', '=', 'sessions.id')
            ->join('events', 'sessions.event_id', '=', 'events.id')
            ->join('carts', 'inscriptions.cart_id', '=', 'carts.id')
            ->where('events.brand_id', $brandId)
            ->whereNotNull('carts.confirmation_code')
            ->where('sessions.starts_on', '>', Carbon::now()->subMonths(3));

        // Si puede ver datos financieros
        if ($this->showFinancials ?? true) {
            $topEvents = $query->selectRaw('
                events.id,
                COALESCE(
                    JSON_UNQUOTE(JSON_EXTRACT(events.name, "$.\\"' . $locale . '\\"")),
                    JSON_UNQUOTE(JSON_EXTRACT(events.name, "$.\\"' . $fallback . '\\"")),
                    JSON_UNQUOTE(JSON_EXTRACT(events.name, "$.*[0]")),
                    events.name
                ) AS name,
                COUNT(DISTINCT inscriptions.id) as total_sales,
                SUM(inscriptions.price_sold) as total_revenue
            ')
            ->groupBy('events.id', 'events.name')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

            $labels = $topEvents->map(fn($e) => Str::limit($e->name, 25));
            $revenues = $topEvents->pluck('total_revenue');
            $sales = $topEvents->pluck('total_sales');

            $this->chart->labels($labels);

            // Dataset de ingresos
            $this->chart->dataset(__('dashboard.revenue') . ' (€)', 'bar', $revenues)
                ->backgroundColor('rgba(54, 162, 235, 0.8)')
                ->options([
                    'borderWidth' => 1,
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'yAxisID' => 'y'
                ]);

            // Dataset de número de ventas (línea)
            $this->chart->dataset(__('dashboard.sales'), 'line', $sales)
                ->backgroundColor('rgba(255, 99, 132, 0.2)')
                ->color('rgba(255, 99, 132, 1)')
                ->options([
                    'borderWidth' => 2,
                    'fill' => false,
                    'yAxisID' => 'y1',
                    'tension' => 0.3
                ]);

            $this->chart->options([
                'responsive' => true,
                'maintainAspectRatio' => false,
                'interaction' => [
                    'mode' => 'index',
                    'intersect' => false
                ],
                'scales' => [
                    'y' => [
                        'type' => 'linear',
                        'display' => true,
                        'position' => 'left',
                        'beginAtZero' => true,
                        'ticks' => [
                            'callback' => "function(value) { return value + '€'; }"
                        ]
                    ],
                    'y1' => [
                        'type' => 'linear',
                        'display' => true,
                        'position' => 'right',
                        'beginAtZero' => true,
                        'grid' => [
                            'drawOnChartArea' => false
                        ]
                    ]
                ],
                'plugins' => [
                    'legend' => [
                        'position' => 'top'
                    ],
                    'tooltip' => [
                        'callbacks' => [
                            'label' => "function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.datasetIndex === 0) {
                                    label += context.parsed.y.toFixed(2) + '€';
                                } else {
                                    label += context.parsed.y + ' " . __('dashboard.sales') . "';
                                }
                                return label;
                            }"
                        ]
                    ]
                ]
            ]);
        } else {
            // Si NO puede ver datos financieros, mostrar solo cantidad de inscripciones
            $topEvents = $query->selectRaw('
                events.id,
                COALESCE(
                    JSON_UNQUOTE(JSON_EXTRACT(events.name, "$.\\"' . $locale . '\\"")),
                    JSON_UNQUOTE(JSON_EXTRACT(events.name, "$.\\"' . $fallback . '\\"")),
                    JSON_UNQUOTE(JSON_EXTRACT(events.name, "$.*[0]")),
                    events.name
                ) AS name,
                COUNT(DISTINCT inscriptions.id) as total_sales,
                COUNT(DISTINCT carts.client_id) as unique_buyers
            ')
            ->groupBy('events.id', 'events.name')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get();

            $labels = $topEvents->map(fn($e) => Str::limit($e->name, 25));
            $sales = $topEvents->pluck('total_sales');
            $buyers = $topEvents->pluck('unique_buyers');

            $this->chart->labels($labels);

            // Solo mostrar inscripciones y compradores únicos
            $this->chart->dataset(__('dashboard.inscriptions'), 'bar', $sales)
                ->backgroundColor('rgba(54, 162, 235, 0.8)')
                ->options([
                    'borderWidth' => 1,
                    'borderColor' => 'rgba(54, 162, 235, 1)'
                ]);

            $this->chart->dataset(__('dashboard.unique_buyers'), 'line', $buyers)
                ->backgroundColor('rgba(75, 192, 192, 0.2)')
                ->color('rgba(75, 192, 192, 1)')
                ->options([
                    'borderWidth' => 2,
                    'fill' => false,
                    'tension' => 0.3
                ]);

            $this->chart->options([
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'position' => 'top'
                    ],
                    'tooltip' => [
                        'callbacks' => [
                            'label' => "function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed.y;
                                return label;
                            }"
                        ]
                    ]
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true
                    ]
                ]
            ]);
        }
    }

    /**
     * Verificar si hay datos para mostrar
     */
    public function hasData(): bool
    {
        // Primero verificar permisos
        if (!$this->hasPermission()) {
            return false;
        }

        return Inscription::query()
            ->join('sessions', 'inscriptions.session_id', '=', 'sessions.id')
            ->join('events', 'sessions.event_id', '=', 'events.id')
            ->join('carts', 'inscriptions.cart_id', '=', 'carts.id')
            ->where('events.brand_id', $this->getBrandId())
            ->whereNotNull('carts.confirmation_code')
            ->exists();
    }

    /**
     * Respond to AJAX calls with all the chart data points
     */
    public function data()
    {
        // Verificar permisos
        if (!$this->hasPermission()) {
            return response()->json([
                'error' => __('dashboard.no_permissions')
            ], 403);
        }

        $this->setup();
        return $this->chart->api();
    }

    /**
     * Propiedad para determinar si mostrar datos financieros
     */
    private $showFinancials = true;
}