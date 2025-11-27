<?php

namespace App\Http\Controllers\Admin\Charts;

use Backpack\CRUD\app\Http\Controllers\ChartController;

abstract class BaseChartController extends ChartController
{
    /**
     * Verificar si hay datos para mostrar la gráfica
     */
    abstract public function hasData(): bool;

    /**
     * Verificar si el usuario tiene permisos para ver esta gráfica
     */
    abstract public function hasPermission(): bool;

    /**
     * Obtener el brand_id actual
     */
    protected function getBrandId(): int
    {
        return get_current_brand()->id;
    }

    /**
     * Setup del chart con verificación de permisos
     */
    public function setup()
    {
        // Verificar permisos antes de generar el chart
        if (!$this->hasPermission()) {
            $this->chart = new \ConsoleTVs\Charts\Classes\Chartjs\Chart();
            $this->chart->labels([]);
            $this->chart->dataset(__('dashboard.no_permissions'), 'line', []);
            $this->chart->options([
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => __('dashboard.no_permissions')
                    ]
                ]
            ]);
            return;
        }

        // Si tiene permisos, ejecutar el setup específico
        $this->setupChart();
    }

    /**
     * Setup específico del chart (a implementar en cada clase hija)
     */
    abstract protected function setupChart();

    /**
     * Respond to AJAX calls
     */
    public function response()
    {
        // Verificar permisos antes de responder
        if (!$this->hasPermission()) {
            return response()->json([
                'error' => __('dashboard.no_permissions')
            ], 403);
        }

        $this->setup();
        return response()->json($this->chart->api());
    }

    /**
     * Helper para aplicar filtros por rol si es necesario
     */
    protected function applyRoleFilters($query)
    {
        // Aquí puedes añadir lógica de filtrado por rol si lo necesitas
        return $query;
    }

    /**
     * Formatear moneda
     */
    protected function formatCurrency($amount): string
    {
        return number_format($amount, 2, ',', '.') . '€';
    }

    /**
     * Obtener colores predefinidos para gráficas
     */
    protected function getChartColors($index = null)
    {
        $colors = [
            'primary' => 'rgba(13, 110, 253, 0.8)',
            'success' => 'rgba(25, 135, 84, 0.8)',
            'info' => 'rgba(13, 202, 240, 0.8)',
            'warning' => 'rgba(255, 193, 7, 0.8)',
            'danger' => 'rgba(220, 53, 69, 0.8)',
            'secondary' => 'rgba(108, 117, 125, 0.8)',
        ];

        $colorArray = array_values($colors);

        if ($index !== null) {
            return $colorArray[$index % count($colorArray)];
        }

        return $colorArray;
    }

    /**
     * Verificaciones de permisos comunes
     */
    protected function canViewSales(): bool
    {
        $user = backpack_user();
        return $user->can('carts.index') || $user->can('statistics.index');
    }

    protected function canViewStatistics(): bool
    {
        return backpack_user()->can('statistics.index');
    }

    protected function canViewEvents(): bool
    {
        return backpack_user()->can('events.index');
    }

    protected function canViewSessions(): bool
    {
        return backpack_user()->can('sessions.index');
    }

    protected function canViewClients(): bool
    {
        return backpack_user()->can('clients.index');
    }

    protected function canViewFinancials(): bool
    {
        $user = backpack_user();
        return $user->can('statistics.index') || 
               $user->can('carts.index') ||
               $user->hasRole('admin') ||
               $user->hasRole('sales_manager');
    }
}