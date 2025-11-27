<?php

namespace App\Http\Controllers\Admin\Charts;

use App\Models\Cart;
use Carbon\Carbon;
use ConsoleTVs\Charts\Classes\Chartjs\Chart;

class SalesEvolutionChartController extends BaseChartController
{
    /**
     * Verificar permisos específicos para esta gráfica
     */
    public function hasPermission(): bool
    {
        // Requiere poder ver carritos o estadísticas
        return $this->canViewSales() || $this->canViewStatistics();
    }

    /**
     * Setup específico del chart
     */
    protected function setupChart()
    {
        $this->chart = new Chart();
        
        $brandId = $this->getBrandId();
        $days = 30;
        $labels = [];
        $data = [];
        
        // Generar datos de los últimos 30 días
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d/m');
            
            $dayRevenue = Cart::whereBrandId($brandId)
                ->whereHas('payments', fn($q) => 
                    $q->whereNotNull('paid_at')->whereDate('paid_at', $date)
                )
                ->get()
                ->sum(fn($cart) => $cart->price_sold);
            
            $data[] = round($dayRevenue, 2);
        }

        $this->chart->labels($labels);
        $this->chart->dataset(__('dashboard.sales_daily'), 'line', $data)
            ->color('rgb(75, 192, 192)')
            ->backgroundColor('rgba(75, 192, 192, 0.15)')
            ->options([
                'tension' => 0.3,
                'fill' => true,
                'borderWidth' => 2
            ]);

        // Configuración adicional
        $this->chart->options([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(value) { return value + '€'; }"
                    ]
                ]
            ],
            'plugins' => [
                'legend' => [
                    'display' => false
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => "function(context) { 
                            return '" . __('dashboard.revenue') . ": ' + context.parsed.y.toFixed(2) + '€'; 
                        }"
                    ]
                ]
            ]
        ]);
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

        $brandId = $this->getBrandId();
        
        return Cart::whereBrandId($brandId)
            ->confirmed()
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->exists();
    }

    /**
     * Respond to AJAX calls with all the chart data points.
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
}