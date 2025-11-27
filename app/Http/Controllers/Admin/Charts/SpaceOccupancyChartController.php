<?php

namespace App\Http\Controllers\Admin\Charts;

use App\Models\Session;
use App\Models\Space;
use Carbon\Carbon;
use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use Illuminate\Support\Facades\DB;

class SpaceOccupancyChartController extends BaseChartController
{
    /**
     * Verificar permisos específicos para esta gráfica
     */
    public function hasPermission(): bool
    {
        // Requiere poder ver sesiones y espacios
        // No requiere estadísticas ya que es información operativa
        return $this->canViewSessions() && backpack_user()->can('spaces.index');
    }

    /**
     * Setup específico del chart
     */
    protected function setupChart()
    {
        $this->chart = new Chart();

        $brandId = $this->getBrandId();
        $now = Carbon::now();

        // Obtener ocupación por espacios
        $spaceData = DB::table('sessions')
            ->join('spaces', 'sessions.space_id', '=', 'spaces.id')
            ->leftJoin('inscriptions', 'sessions.id', '=', 'inscriptions.session_id')
            ->leftJoin('carts', function ($join) {
                $join->on('inscriptions.cart_id', '=', 'carts.id')
                    ->whereNotNull('carts.confirmation_code');
            })
            ->where('sessions.brand_id', $brandId)
            ->where('sessions.starts_on', '>', $now)
            ->where('sessions.inscription_ends_on', '>', $now)
            ->select(
                'spaces.id',
                'spaces.name',
                DB::raw('SUM(sessions.max_places) as total_capacity'),
                DB::raw('COUNT(DISTINCT CASE WHEN carts.confirmation_code IS NOT NULL THEN inscriptions.id END) as sold')
            )
            ->groupBy('spaces.id', 'spaces.name')
            ->having('total_capacity', '>', 0)
            ->get();

        // Calcular porcentajes de ocupación
        $labels = [];
        $occupancies = [];
        $availables = [];

        foreach ($spaceData as $space) {
            $labels[] = $space->name;
            $occupancy = round(($space->sold / $space->total_capacity) * 100, 1);
            $occupancies[] = $occupancy;
            $availables[] = 100 - $occupancy;
        }

        $this->chart->labels($labels);

        // Dataset de ocupación
        $this->chart->dataset(__('dashboard.sold') . ' (%)', 'bar', $occupancies)
            ->backgroundColor('rgba(220, 53, 69, 0.8)')
            ->options([
                'borderWidth' => 1,
                'borderColor' => 'rgba(220, 53, 69, 1)'
            ]);

        // Dataset de disponible
        $this->chart->dataset(__('dashboard.available') . ' (%)', 'bar', $availables)
            ->backgroundColor('rgba(25, 135, 84, 0.8)')
            ->options([
                'borderWidth' => 1,
                'borderColor' => 'rgba(25, 135, 84, 1)'
            ]);

        $this->chart->options([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'indexAxis' => 'y', // Barras horizontales
            'scales' => [
                'x' => [
                    'stacked' => true,
                    'beginAtZero' => true,
                    'max' => 100,
                    'ticks' => [
                        'callback' => "function(value) { return value + '%'; }"
                    ]
                ],
                'y' => [
                    'stacked' => true
                ]
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom'
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => "function(context) {
                            return context.dataset.label + ': ' + context.parsed.x.toFixed(1) + '%';
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

        return Session::whereBrandId($this->getBrandId())
            ->where('starts_on', '>', Carbon::now())
            ->whereNotNull('space_id')
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
}