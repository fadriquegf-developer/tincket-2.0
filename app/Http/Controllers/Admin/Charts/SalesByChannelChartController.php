<?php

namespace App\Http\Controllers\Admin\Charts;

use App\Models\Payment;
use Carbon\Carbon;
use ConsoleTVs\Charts\Classes\Chartjs\Chart;

class SalesByChannelChartController extends BaseChartController
{
    /**
     * Verificar permisos específicos para esta gráfica
     */
    public function hasPermission(): bool
    {
        // Requiere permisos de estadísticas para ver distribución por canal
        return $this->canViewStatistics();
    }

    /**
     * Setup específico del chart
     */
    protected function setupChart()
    {
        $this->chart = new Chart();

        $brandId = $this->getBrandId();
        $startOfMonth = Carbon::now()->startOfMonth();

        // Obtener datos agrupados por canal
        $channelData = Payment::query()
            ->join('carts', 'payments.cart_id', '=', 'carts.id')
            ->where('carts.brand_id', $brandId)
            ->whereNotNull('payments.paid_at')
            ->where('payments.paid_at', '>=', $startOfMonth)
            ->selectRaw("
                CASE 
                    WHEN gateway = 'TicketOffice' THEN '" . __('dashboard.channel_box_office') . "'
                    WHEN gateway IN ('Sermepa', 'SermepaSoapService', 'RedsysSoapService', 'Redsys Redirect') THEN '" . __('dashboard.channel_web') . "'
                    WHEN gateway = 'Free' THEN '" . __('dashboard.channel_free') . "'
                    ELSE '" . __('dashboard.channel_others') . "'
                END as channel,
                COUNT(*) as count,
                (SELECT SUM(inscriptions.price_sold) 
                    FROM inscriptions 
                    WHERE inscriptions.cart_id = carts.id) as total
            ")
            ->groupBy('channel')
            ->get();

        $labels = $channelData->pluck('channel');
        $data = $channelData->pluck('count');
        $colors = $this->getChartColors();

        $this->chart->labels($labels);
        $this->chart->dataset(__('dashboard.sales'), 'doughnut', $data)
            ->backgroundColor($colors)
            ->options([
                'borderWidth' => 2,
                'borderColor' => '#fff'
            ]);

        $this->chart->options([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'padding' => 15,
                        'usePointStyle' => true
                    ]
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => "function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }"
                    ]
                ]
            ]
        ]);
    }

    public function hasData(): bool
    {
        // Primero verificar permisos
        if (!$this->hasPermission()) {
            return false;
        }

        // Verificar también la capacidad del brand
        if (get_brand_capability() == 'promoter') {
            return false;
        }

        return Payment::query()
            ->join('carts', 'payments.cart_id', '=', 'carts.id')
            ->where('carts.brand_id', $this->getBrandId())
            ->whereNotNull('payments.paid_at')
            ->where('payments.paid_at', '>=', Carbon::now()->startOfMonth())
            ->exists();
    }
}