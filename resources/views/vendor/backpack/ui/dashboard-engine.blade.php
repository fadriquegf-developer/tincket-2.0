@extends(backpack_view('layouts.horizontal'))

@section('header')
    <div class="container-fluid mb-3 mt-2">
        <div class="d-flex align-items-center justify-content-between">
            <h2 class="mb-0">
                <i class="la la-server"></i> {{ trans('backpack::base.dashboard') }} - Engine
            </h2>
            <div class="text-muted small">
                <i class="la la-clock"></i> {{ __('dashboard.last_update') }} {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        {{-- Botones de acceso rápido (arriba como en basic/promotor) --}}
        <div class="row mb-3">
            <div class="col-12">
                <div class="btn-toolbar" role="toolbar">
                    <div class="btn-group me-2" role="group">
                        @if($permissions['canViewBrands'])
                            <a href="{{ backpack_url('brand') }}" class="btn btn-primary">
                                <i class="la la-building me-1"></i> {{ __('menu.brands') }}
                            </a>
                        @endif
                        
                        <a href="{{ backpack_url('user') }}" class="btn btn-primary">
                            <i class="la la-users me-1"></i> {{ __('menu.users') }}
                        </a>
                        
                        <a href="{{ backpack_url('capability') }}" class="btn btn-primary">
                            <i class="la la-puzzle-piece me-1"></i> {{ __('menu.capability') }}
                        </a>
                    </div>
                    
                    <div class="btn-group" role="group">
                        <a href="{{ backpack_url('job') }}" class="btn btn-outline-primary">
                            <i class="la la-tasks me-1"></i> {{ __('menu.jobs') }}
                        </a>
                        
                        <a href="{{ backpack_url('failed-job') }}" class="btn btn-outline-danger">
                            <i class="la la-exclamation-triangle me-1"></i> {{ __('menu.failed_jobs') }}
                        </a>
                        
                        <a href="{{ backpack_url('activity-log') }}" class="btn btn-outline-info">
                            <i class="la la-stream me-1"></i> {{ __('menu.recent_activity') }}
                        </a>
                        
                        <a href="{{ backpack_url('log') }}" class="btn btn-outline-warning">
                            <i class="la la-terminal me-1"></i> Logs
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPIs Principales del Sistema --}}
        <div class="row g-3 mb-4">
            {{-- Total Brands --}}
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-start border-primary border-4 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="small text-muted mb-1">{{ __('dashboard.total_brands') }}</div>
                                <div class="h4 mb-0">{{ $metrics['global']['total_brands'] ?? 0 }}</div>
                                <div class="small text-success">
                                    <i class="la la-check-circle"></i> {{ $metrics['global']['active_brands'] ?? 0 }} {{ __('dashboard.active') }}
                                </div>
                            </div>
                            <div class="flex-shrink-0 ms-2">
                                <i class="la la-building text-primary" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Usuarios --}}
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-start border-info border-4 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="small text-muted mb-1">{{ __('dashboard.total_users') }}</div>
                                <div class="h4 mb-0">{{ number_format($metrics['global']['total_users'] ?? 0, 0, ',', '.') }}</div>
                                <div class="small text-info">
                                    {{ __('dashboard.system_wide') }}
                                </div>
                            </div>
                            <div class="flex-shrink-0 ms-2">
                                <i class="la la-users text-info" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Eventos --}}
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-start border-success border-4 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="small text-muted mb-1">{{ __('dashboard.total_events') }}</div>
                                <div class="h4 mb-0">{{ number_format($metrics['global']['total_events'] ?? 0, 0, ',', '.') }}</div>
                                <div class="small text-success">
                                    <i class="la la-calendar-check"></i> {{ $metrics['global']['active_events'] ?? 0 }} {{ __('dashboard.active') }}
                                </div>
                            </div>
                            <div class="flex-shrink-0 ms-2">
                                <i class="la la-calendar text-success" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Clientes --}}
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-start border-warning border-4 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="small text-muted mb-1">{{ __('dashboard.total_clients') }}</div>
                                <div class="h4 mb-0">{{ number_format($metrics['global']['total_clients'] ?? 0, 0, ',', '.') }}</div>
                                <div class="small text-muted">{{ __('dashboard.unique_clients') }}</div>
                            </div>
                            <div class="flex-shrink-0 ms-2">
                                <i class="la la-address-book text-warning" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Sesiones --}}
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-start border-danger border-4 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="small text-muted mb-1">{{ __('dashboard.total_sessions') }}</div>
                                <div class="h4 mb-0">{{ number_format($metrics['global']['total_sessions'] ?? 0, 0, ',', '.') }}</div>
                                <div class="small text-muted">{{ __('dashboard.all_time') }}</div>
                            </div>
                            <div class="flex-shrink-0 ms-2">
                                <i class="la la-clock text-danger" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Inscripciones --}}
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-start border-secondary border-4 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="small text-muted mb-1">{{ __('dashboard.total_inscriptions') }}</div>
                                <div class="h4 mb-0">{{ number_format($metrics['global']['total_inscriptions'] ?? 0, 0, ',', '.') }}</div>
                                <div class="small text-muted">{{ __('dashboard.confirmed') }}</div>
                            </div>
                            <div class="flex-shrink-0 ms-2">
                                <i class="la la-ticket text-secondary" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Estadísticas del Sistema Hoy --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-lg-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="la la-chart-line"></i> {{ __('dashboard.today_stats') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h3 mb-0">{{ $metrics['system']['carts_today'] ?? 0 }}</div>
                                    <small class="text-muted">{{ __('dashboard.carts_created') }}</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h3 mb-0 text-success">{{ $metrics['system']['sales_today'] ?? 0 }}</div>
                                    <small class="text-muted">{{ __('dashboard.confirmed_sales') }}</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h3 mb-0 text-info">{{ $metrics['system']['new_clients_today'] ?? 0 }}</div>
                                    <small class="text-muted">{{ __('dashboard.new_clients') }}</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h3 mb-0 text-warning">{{ $metrics['system']['active_sessions_now'] ?? 0 }}</div>
                                    <small class="text-muted">{{ __('dashboard.sessions_now') }}</small>
                                </div>
                            </div>
                        </div>
                        
                        @if($permissions['canViewStatistics'] && isset($metrics['system']['revenue_today']))
                        <hr>
                        <div class="text-center">
                            <small class="text-muted">{{ __('dashboard.revenue_today') }}</small>
                            <div class="h2 mb-0 text-primary">
                                {{ number_format($metrics['system']['revenue_today'] ?? 0, 2, ',', '.') }}€
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Comparación de Ingresos --}}
            @if($permissions['canViewStatistics'] && isset($metrics['system']['revenue_month']))
            <div class="col-12 col-lg-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="la la-euro"></i> {{ __('dashboard.revenue_comparison') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">{{ __('dashboard.this_month') }}</small>
                            <div class="h3 mb-0">{{ number_format($metrics['system']['revenue_month'] ?? 0, 2, ',', '.') }}€</div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">{{ __('dashboard.last_month') }}</small>
                            <div class="h3 mb-0">{{ number_format($metrics['system']['revenue_last_month'] ?? 0, 2, ',', '.') }}€</div>
                        </div>
                        <hr>
                        <div class="text-center">
                            @if(($metrics['system']['growth_percentage'] ?? 0) >= 0)
                                <div class="h2 mb-0 text-success">
                                    <i class="la la-arrow-up"></i> {{ $metrics['system']['growth_percentage'] }}%
                                </div>
                                <small class="text-muted">{{ __('dashboard.growth') }}</small>
                            @else
                                <div class="h2 mb-0 text-danger">
                                    <i class="la la-arrow-down"></i> {{ abs($metrics['system']['growth_percentage']) }}%
                                </div>
                                <small class="text-muted">{{ __('dashboard.decrease') }}</small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Monitoreo del Sistema --}}
            <div class="col-12 col-lg-4">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="la la-server"></i> {{ __('dashboard.system_monitoring') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span><i class="la la-tasks"></i> {{ __('dashboard.jobs_pending') }}</span>
                            <span class="badge bg-warning">{{ $metrics['monitoring']['jobs']['pending'] ?? 0 }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span><i class="la la-exclamation-circle"></i> {{ __('dashboard.jobs_failed') }}</span>
                            <span class="badge bg-danger">{{ $metrics['monitoring']['jobs']['failed'] ?? 0 }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span><i class="la la-calendar-times"></i> {{ __('dashboard.failed_today') }}</span>
                            <span class="badge bg-warning">{{ $metrics['monitoring']['jobs']['failed_today'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Gráficos (si tiene permisos) --}}
        @if($permissions['canViewStatistics'] && isset($metrics['charts']))
        <div class="row g-3 mb-4">
            {{-- Evolución de Ventas --}}
            @if(isset($metrics['charts']['salesEvolution']) && count($metrics['charts']['salesEvolution']) > 0)
            <div class="col-12 col-xl-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ __('dashboard.sales_evolution_30d') }}</h5>
                        <span class="text-muted">
                            {{ __('dashboard.total') }}: 
                            {{ number_format(collect($metrics['charts']['salesEvolution'])->sum('revenue'), 2, ',', '.') }}€
                        </span>
                    </div>
                    <div class="card-body">
                        <canvas id="salesEvolutionChart" style="height: 300px;"></canvas>
                    </div>
                </div>
            </div>
            @endif

            {{-- Distribución por Brand --}}
            @if(isset($metrics['charts']['revenueByBrand']) && count($metrics['charts']['revenueByBrand']) > 0)
            <div class="col-12 col-xl-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('dashboard.revenue_by_brand') }}</h5>
                    </div>
                    <div class="card-body">
                        <div style="position: relative; height: 250px;">
                            <canvas id="revenueByBrandChart"></canvas>
                        </div>
                        <div class="mt-3" style="max-height: 150px; overflow-y: auto;">
                            @foreach($metrics['charts']['revenueByBrand'] as $item)
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-truncate me-2">{{ $item['brand'] }}</span>
                                <strong>{{ number_format($item['revenue'], 2, ',', '.') }}€</strong>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- Top Brands --}}
        @if($permissions['canViewBrands'] && isset($metrics['top_brands']))
        <div class="row g-3 mb-4">
            {{-- Top por Ingresos --}}
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="mb-0">{{ __('dashboard.top_brands_revenue') }}</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @forelse($metrics['top_brands']['by_revenue'] as $index => $item)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-primary me-2">{{ $index + 1 }}</span>
                                        <strong>{{ Str::limit($item['brand']->name, 20) }}</strong>
                                    </div>
                                    <div class="text-end">
                                        <strong>{{ number_format($item['revenue_month'], 2, ',', '.') }}€</strong>
                                        <div class="small text-muted">{{ $item['sales_count'] }} ventas</div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="list-group-item text-center text-muted">
                                {{ __('dashboard.no_data') }}
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Top por Ventas --}}
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="mb-0">{{ __('dashboard.top_brands_sales') }}</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @forelse($metrics['top_brands']['by_sales'] as $index => $item)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-success me-2">{{ $index + 1 }}</span>
                                        <strong>{{ Str::limit($item['brand']->name, 20) }}</strong>
                                    </div>
                                    <div class="text-end">
                                        <strong>{{ $item['sales_count'] }}</strong>
                                        <div class="small text-muted">ventas</div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="list-group-item text-center text-muted">
                                {{ __('dashboard.no_data') }}
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Top por Clientes --}}
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="mb-0">{{ __('dashboard.top_brands_clients') }}</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @forelse($metrics['top_brands']['by_clients'] as $index => $item)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-info me-2">{{ $index + 1 }}</span>
                                        <strong>{{ Str::limit($item['brand']->name, 20) }}</strong>
                                    </div>
                                    <div class="text-end">
                                        <strong>{{ number_format($item['clients_count'], 0, ',', '.') }}</strong>
                                        <div class="small text-muted">+{{ $item['new_clients_month'] }} este mes</div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="list-group-item text-center text-muted">
                                {{ __('dashboard.no_data') }}
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Top por Eventos Activos --}}
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="mb-0">{{ __('dashboard.top_brands_events') }}</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @forelse($metrics['top_brands']['by_events'] as $index => $item)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-warning me-2">{{ $index + 1 }}</span>
                                        <strong>{{ Str::limit($item['brand']->name, 20) }}</strong>
                                    </div>
                                    <div class="text-end">
                                        <strong>{{ $item['active_events'] }}</strong>
                                        <div class="small text-muted">de {{ $item['events_count'] }} total</div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="list-group-item text-center text-muted">
                                {{ __('dashboard.no_data') }}
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Actividad Reciente --}}
        <div class="row g-3 mb-4">
            {{-- Últimos Eventos Creados --}}
            @if(isset($metrics['recent_activity']['recent_events']) && count($metrics['recent_activity']['recent_events']) > 0)
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('dashboard.recent_events') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>{{ __('dashboard.event') }}</th>
                                        <th>{{ __('dashboard.brand') }}</th>
                                        <th>{{ __('dashboard.created') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($metrics['recent_activity']['recent_events'] as $event)
                                    <tr>
                                        <td>{{ Str::limit($event->name, 30) }}</td>
                                        <td><span class="badge bg-secondary">{{ $event->brand->name ?? 'N/A' }}</span></td>
                                        <td>{{ $event->created_at->diffForHumans() }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Últimas Ventas --}}
            @if(isset($metrics['recent_activity']['recent_sales']) && count($metrics['recent_activity']['recent_sales']) > 0)
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('dashboard.recent_sales') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th>{{ __('dashboard.code') }}</th>
                                        <th>{{ __('dashboard.brand') }}</th>
                                        <th>{{ __('dashboard.amount') }}</th>
                                        <th>{{ __('dashboard.date') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($metrics['recent_activity']['recent_sales'] as $sale)
                                    <tr>
                                        <td><code>{{ $sale['cart']->confirmation_code }}</code></td>
                                        <td><small>{{ Str::limit($sale['brand_name'], 20) }}</small></td>
                                        <td><strong>{{ number_format($sale['revenue'], 2, ',', '.') }}€</strong></td>
                                        <td>{{ $sale['cart']->created_at->format('d/m H:i') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection

@section('after_scripts')
    {{-- Solo cargar Chart.js si el usuario tiene permisos de estadísticas --}}
    @if($permissions['canViewStatistics'] && isset($metrics['charts']))
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Configuración global
                Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
                Chart.defaults.plugins.legend.labels.usePointStyle = true;
                Chart.defaults.plugins.legend.labels.padding = 15;

                // 1. Gráfica de evolución de ventas (30 días)
                @if(isset($metrics['charts']['salesEvolution']) && count($metrics['charts']['salesEvolution']) > 0)
                    const salesCtx = document.getElementById('salesEvolutionChart');
                    if (salesCtx) {
                        const salesData = @json($metrics['charts']['salesEvolution']);
                        new Chart(salesCtx, {
                            type: 'line',
                            data: {
                                labels: salesData.map(d => d.date),
                                datasets: [{
                                    label: '{{ __('dashboard.revenue') }}',
                                    data: salesData.map(d => d.revenue),
                                    borderColor: 'rgb(75, 192, 192)',
                                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                                    tension: 0.3,
                                    fill: true,
                                    pointRadius: 3,
                                    pointHoverRadius: 5
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: {
                                    mode: 'index',
                                    intersect: false
                                },
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                const revenue = context.parsed.y.toFixed(2);
                                                const count = salesData[context.dataIndex].count;
                                                return [
                                                    '{{ __('dashboard.tooltip_revenue') }}' + revenue + '€',
                                                    '{{ __('dashboard.tooltip_sales') }}' + count
                                                ];
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function(value) {
                                                return value.toFixed(0) + '€';
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                @endif

                // 2. Gráfica de ingresos por Brand
                @if(isset($metrics['charts']['revenueByBrand']) && count($metrics['charts']['revenueByBrand']) > 0)
                    const brandCtx = document.getElementById('revenueByBrandChart');
                    if (brandCtx) {
                        const brandData = @json($metrics['charts']['revenueByBrand']);
                        new Chart(brandCtx, {
                            type: 'doughnut',
                            data: {
                                labels: brandData.map(b => b.brand),
                                datasets: [{
                                    data: brandData.map(b => b.revenue),
                                    backgroundColor: [
                                        'rgba(255, 99, 132, 0.8)',
                                        'rgba(54, 162, 235, 0.8)',
                                        'rgba(255, 206, 86, 0.8)',
                                        'rgba(75, 192, 192, 0.8)',
                                        'rgba(153, 102, 255, 0.8)',
                                        'rgba(255, 159, 64, 0.8)'
                                    ],
                                    borderWidth: 2,
                                    borderColor: '#fff'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                                return context.label + ': ' + context.parsed.toFixed(2) + '€ (' + percentage + '%)';
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                @endif
            });
        </script>
    @endif
@endsection