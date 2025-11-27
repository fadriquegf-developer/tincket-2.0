@extends(backpack_view('layouts.horizontal'))

@section('header')
    <div class="container-fluid mb-3 mt-2">
        <div class="d-flex align-items-center justify-content-between">
            <h2 class="mb-0">{{ trans('backpack::base.dashboard') }}</h2>
            <div class="text-muted small">
                <i class="la la-clock"></i> {{ __('dashboard.last_update') }} {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        {{-- Botones de acceso rápido (solo los que el usuario puede ver) --}}
        <div class="row mb-3">
            <div class="col-12">
                <div class="btn-toolbar" role="toolbar">
                    <div class="btn-group me-2" role="group">
                        @if($permissions['canCreateTicketOffice'])
                            <a href="{{ backpack_url('ticket-office/create') }}" class="btn btn-primary">
                                <i class="la la-cash-register me-1"></i> {{ __('dashboard.box_office') }}
                            </a>
                        @endif
                        
                        @if($permissions['canViewEvents'])
                            <a href="{{ backpack_url('event') }}" class="btn btn-primary">
                                <i class="la la-calendar me-1"></i> {{ __('dashboard.events') }}
                            </a>
                        @endif
                        
                        @if($permissions['canViewSessions'])
                            <a href="{{ backpack_url('session') }}" class="btn btn-primary">
                                <i class="la la-clock me-1"></i> {{ __('dashboard.sessions') }}
                            </a>
                        @endif
                        
                        @if($permissions['canViewSales'])
                            <a href="{{ backpack_url('cart') }}" class="btn btn-primary">
                                <i class="la la-shopping-cart me-1"></i> {{ __('dashboard.carts') }}
                            </a>
                        @endif
                    </div>
                    
                    <div class="btn-group" role="group">
                        @if($permissions['canViewClients'])
                            <a href="{{ backpack_url('client') }}" class="btn btn-outline-primary">
                                <i class="la la-users me-1"></i> {{ __('dashboard.clients') }}
                            </a>
                        @endif
                        
                        @if (get_brand_capability() != 'promoter' && backpack_user()->can('inscriptions.index'))
                            <a href="{{ backpack_url('inscription') }}" class="btn btn-outline-primary">
                                <i class="la la-ticket me-1"></i> {{ __('dashboard.inscriptions') }}
                            </a>
                        @endif
                        
                        @if($permissions['canViewValidations'])
                            <a href="{{ backpack_url('validation') }}" class="btn btn-outline-primary">
                                <i class="la la-check-circle me-1"></i> {{ __('dashboard.validations') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- KPIs Principales (solo si tiene permisos) --}}
        @if(!isset($metrics['kpis']['hideAll']) || !$metrics['kpis']['hideAll'])
            <div class="row g-3 mb-4">
                {{-- Ventas Hoy --}}
                @if($permissions['canViewSales'] && !isset($metrics['kpis']['today']['hidden']))
                    <div class="col-6 col-sm-4 col-lg-2">
                        <div class="card border-start border-primary border-4 h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="small text-muted mb-1">{{ __('dashboard.sales_today') }}</div>
                                        <div class="h5 mb-0">
                                            {{ number_format($metrics['kpis']['today']['revenue'], 2, ',', '.') }}€</div>
                                        <div class="small text-success">
                                            <i class="la la-shopping-cart"></i> {{ $metrics['kpis']['today']['count'] }}
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 ms-2">
                                        <i class="la la-euro-sign text-primary" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Ventas Semana --}}
                @if($permissions['canViewSales'] && !isset($metrics['kpis']['week']['hidden']))
                    <div class="col-6 col-sm-4 col-lg-2">
                        <div class="card border-start border-info border-4 h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="small text-muted mb-1">{{ __('dashboard.this_week') }}</div>
                                        <div class="h5 mb-0">
                                            {{ number_format($metrics['kpis']['week']['revenue'], 2, ',', '.') }}€</div>
                                        <div class="small text-info">
                                            <i class="la la-shopping-cart"></i> {{ $metrics['kpis']['week']['count'] }}
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 ms-2">
                                        <i class="la la-calendar-week text-info" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Ventas Mes --}}
                @if($permissions['canViewSales'] && !isset($metrics['kpis']['month']['hidden']))
                    <div class="col-6 col-sm-4 col-lg-2">
                        <div class="card border-start border-success border-4 h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="small text-muted mb-1">{{ __('dashboard.this_month') }}</div>
                                        <div class="h5 mb-0">
                                            {{ number_format($metrics['kpis']['month']['revenue'], 2, ',', '.') }}€</div>
                                        <div class="small text-success">
                                            <i class="la la-shopping-cart"></i> {{ $metrics['kpis']['month']['count'] }}
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 ms-2">
                                        <i class="la la-calendar text-success" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Conversión (solo con permisos de estadísticas) --}}
                @if($permissions['canViewStatistics'] && !isset($metrics['kpis']['hideAdvancedMetrics']))
                    <div class="col-6 col-sm-4 col-lg-2">
                        <div class="card border-start border-warning border-4 h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="small text-muted mb-1">{{ __('dashboard.conversion') }}</div>
                                        <div class="h5 mb-0">{{ $metrics['kpis']['conversionRate'] }}%</div>
                                        <div class="small text-muted">{{ __('dashboard.this_month') }}</div>
                                    </div>
                                    <div class="flex-shrink-0 ms-2">
                                        <i class="la la-percentage text-warning" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Ticket Medio --}}
                    <div class="col-6 col-sm-4 col-lg-2">
                        <div class="card border-start border-danger border-4 h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="small text-muted mb-1">{{ __('dashboard.average_ticket') }}</div>
                                        <div class="h5 mb-0">
                                            {{ number_format($metrics['kpis']['averageTicket'], 2, ',', '.') }}€</div>
                                        <div class="small text-muted">{{ __('dashboard.this_month') }}</div>
                                    </div>
                                    <div class="flex-shrink-0 ms-2">
                                        <i class="la la-receipt text-danger" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Eventos Activos (solo si puede ver eventos) --}}
                @if($permissions['canViewEvents'] && !isset($metrics['kpis']['hideEvents']))
                    <div class="col-6 col-sm-4 col-lg-2">
                        <div class="card border-start border-secondary border-4 h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="small text-muted mb-1">{{ __('dashboard.events_active_title') }}</div>
                                        <div class="h5 mb-0">{{ $metrics['kpis']['upcomingEvents'] }}</div>
                                        <div class="small text-muted">{{ __('dashboard.active') }}</div>
                                    </div>
                                    <div class="flex-shrink-0 ms-2">
                                        <i class="la la-theater-masks text-secondary" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Gráficas principales (solo con permisos de estadísticas) --}}
        @if($permissions['canViewStatistics'] && !isset($metrics['charts']['hidden']))
            <div class="row g-3 mb-4">
                {{-- Evolución de ventas --}}
                @if($metrics['charts']['salesEvolution']->count() > 0)
                    <div class="col-12 @if(get_brand_capability() != 'promoter' && $metrics['charts']['salesByChannel']->count() > 0) col-xl-8 @endif">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">{{ __('dashboard.sales_evolution_30d') }}</h5>
                                <span class="text-muted">
                                    {{ __('dashboard.total') }}
                                    {{ number_format($metrics['charts']['salesEvolution']->sum('revenue'), 2, ',', '.') }}€
                                </span>
                            </div>
                            <div class="card-body">
                                <canvas id="salesEvolutionChart" style="height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Ventas por canal --}}
                @if(get_brand_capability() != 'promoter' && $metrics['charts']['salesByChannel']->count() > 0)
                    <div class="col-12 col-xl-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">{{ __('dashboard.sales_by_channel') }}</h5>
                            </div>
                            <div class="card-body">
                                <div style="position: relative; height: 250px;">
                                    <canvas id="salesByChannelChart"></canvas>
                                </div>
                                <div class="mt-3">
                                    @foreach($metrics['charts']['salesByChannel'] as $channel => $data)
                                        <div class="d-flex justify-content-between small">
                                            <span>{{ $channel }}:</span>
                                            <strong>{{ trans_choice('dashboard.sales_n', $data->count, ['count' => $data->count]) }}</strong>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Segunda fila - Tablas --}}
        <div class="row g-3 mb-4">
            {{-- Top Eventos (solo si puede ver eventos) --}}
            @if($permissions['canViewEvents'] && $metrics['tables']['topEvents']->count() > 0)
                <div class="col-12 col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">{{ __('dashboard.top_5_events') }}</h5>
                            <a href="{{ backpack_url('event') }}"
                                class="btn btn-sm btn-outline-primary">{{ __('dashboard.see_all_m') }}</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>{{ __('dashboard.event') }}</th>
                                            @if($permissions['canViewStatistics'])
                                                <th class="text-center">{{ __('dashboard.sales') }}</th>
                                                <th class="text-end">{{ __('dashboard.revenue') }}</th>
                                            @else
                                                <th class="text-center">{{ __('dashboard.customers') }}</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($metrics['tables']['topEvents'] as $event)
                                            <tr>
                                                <td>
                                                    <div class="fw-bold">{{ Str::limit($event->name, 35) }}</div>
                                                    <small class="text-muted">
                                                        {{ trans_choice('dashboard.customers_n', $event->unique_buyers, ['count' => $event->unique_buyers]) }}
                                                    </small>
                                                </td>
                                                @if($permissions['canViewStatistics'] && !isset($event->hideFinancials))
                                                    <td class="text-center">
                                                        <span class="badge bg-info">{{ $event->total_sales }}</span>
                                                    </td>
                                                    <td class="text-end">
                                                        <strong>{{ number_format($event->total_revenue, 2, ',', '.') }}€</strong>
                                                    </td>
                                                @else
                                                    <td class="text-center">
                                                        <span class="badge bg-info">{{ $event->unique_buyers }}</span>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Ocupación próximas sesiones (solo si puede ver sesiones) --}}
            @if($permissions['canViewSessions'])
                <div class="col-12 @if($permissions['canViewEvents'] && $metrics['tables']['topEvents']->count() > 0) col-lg-6 @endif">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">{{ __('dashboard.upcoming_sessions') }}</h5>
                            <a href="{{ backpack_url('session') }}"
                                class="btn btn-sm btn-outline-primary">{{ __('dashboard.see_all_f') }}</a>
                        </div>
                        <div class="card-body">
                            @if($metrics['tables']['upcomingSessions']->count() > 0)
                                <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                                    <table class="table table-sm">
                                        <thead class="sticky-top bg-white">
                                            <tr>
                                                <th>{{ __('dashboard.session') }}</th>
                                                <th>{{ __('dashboard.date') }}</th>
                                                <th class="text-center">{{ __('dashboard.occupancy') }}</th>
                                                <th class="text-end">{{ __('dashboard.free') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($metrics['tables']['upcomingSessions'] as $session)
                                                <tr>
                                                    <td>
                                                        <div class="text-truncate" style="max-width: 200px;">
                                                            <strong>{{ $session['name'] }}</strong>
                                                        </div>
                                                        <small class="text-muted">{{ $session['space'] }}</small>
                                                    </td>
                                                    <td>
                                                        <div>{{ $session['date'] }}</div>
                                                        <small class="text-muted">{{ $session['time'] }}</small>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="progress" style="height: 20px; min-width: 100px;">
                                                            <div class="progress-bar 
                                                                    {{ $session['occupancy'] >= 90 ? 'bg-danger' : ($session['occupancy'] >= 70 ? 'bg-warning' : 'bg-success') }}"
                                                                role="progressbar"
                                                                style="width: {{ $session['occupancy'] }}%">
                                                                {{ $session['occupancy'] }}%
                                                            </div>
                                                        </div>
                                                        <small class="text-muted">{{ $session['sold'] }}/{{ $session['capacity'] }}</small>
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="badge 
                                                                {{ $session['available'] <= 10 ? 'bg-danger' : ($session['available'] <= 50 ? 'bg-warning' : 'bg-success') }}">
                                                            {{ $session['available'] }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center text-muted py-5">
                                    <i class="la la-calendar-check" style="font-size: 3rem;"></i>
                                    <p class="mt-2">{{ __('dashboard.no_upcoming_sessions') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Tercera fila - Análisis (solo con permisos de estadísticas) --}}
        @if($permissions['canViewStatistics'] && !isset($metrics['analysis']['hidden']))
            <div class="row g-3 mb-4">
                {{-- Análisis de Clientes --}}
                @if(get_brand_capability() != 'promoter' && $permissions['canViewClients'] && !isset($metrics['analysis']['customers']['hidden']))
                    <div class="col-12 col-md-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">{{ __('dashboard.customers_this_month') }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <div class="text-center">
                                            <div class="display-6 text-success">{{ $metrics['analysis']['customers']['new'] }}</div>
                                            <small class="text-muted">{{ __('dashboard.new') }}</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center">
                                            <div class="display-6 text-info">{{ $metrics['analysis']['customers']['recurring'] }}</div>
                                            <small class="text-muted">{{ __('dashboard.recurring') }}</small>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div style="position: relative; height: 180px;">
                                    <canvas id="customersChart"></canvas>
                                </div>
                                <div class="mt-3 text-center">
                                    <small class="text-muted">{{ __('dashboard.historical_total') }}
                                        <strong>{{ number_format($metrics['analysis']['customers']['total'], 0, ',', '.') }}</strong>
                                        {{ __('dashboard.customers') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Carritos Abandonados --}}
                @if(get_brand_capability() != 'promoter' && $permissions['canViewSales'] && !isset($metrics['analysis']['abandonedCarts']['hidden']))
                    <div class="col-12 col-md-4">
                        <div class="card h-100">
                            <div class="card-header bg-warning bg-opacity-10">
                                <h5 class="mb-0">{{ __('dashboard.abandoned_carts') }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center">
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <div class="display-6 text-warning">{{ $metrics['analysis']['abandonedCarts']['count'] }}</div>
                                            <small class="text-muted">{{ __('dashboard.this_week') }}</small>
                                        </div>
                                        <div class="col-6">
                                            <div class="display-6 text-danger">{{ $metrics['analysis']['abandonedCarts']['thisMonth'] }}</div>
                                            <small class="text-muted">{{ __('dashboard.this_month') }}</small>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="alert alert-warning mb-0">
                                        <h6 class="alert-heading">{{ __('dashboard.lost_value_week') }}</h6>
                                        <div class="h4 mb-0">
                                            {{ number_format($metrics['analysis']['abandonedCarts']['value'], 2, ',', '.') }}€
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Métricas Rápidas --}}
                @if(get_brand_capability() != 'promoter')
                    <div class="col-12 col-md-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">{{ __('dashboard.quick_analysis') }}</h5>
                            </div>
                            <div class="card-body">
                                {{-- Horarios pico --}}
                                @if($metrics['analysis']['peakHours']->count() > 0)
                                    <h6 class="text-muted mb-2">{{ __('dashboard.peak_hours') }}</h6>
                                    <div class="mb-3">
                                        @foreach($metrics['analysis']['peakHours']->take(3) as $hour)
                                            <div class="d-flex justify-content-between small mb-1">
                                                <span><i class="la la-clock"></i> {{ $hour['hour'] }}</span>
                                                <span class="badge bg-primary">{{ trans_choice('dashboard.sales_n', $hour['count'], ['count' => $hour['count']]) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                    <hr>
                                @endif

                                {{-- Tarifas más vendidas (solo si puede ver tarifas) --}}
                                @if(backpack_user()->can('rates.index') && $metrics['analysis']['topRates']->count() > 0)
                                    <h6 class="text-muted mb-2">{{ __('dashboard.popular_rates') }}</h6>
                                    @foreach($metrics['analysis']['topRates']->take(3) as $rate)
                                        <div class="d-flex justify-content-between small mb-1">
                                            <span class="text-truncate me-2">{{ $rate->name }}</span>
                                            <span class="badge bg-success">{{ $rate->times_sold }}x</span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Cuarta fila - Información adicional --}}
        <div class="row g-3">
            {{-- Sesiones Críticas (solo si puede ver sesiones) --}}
            @if($permissions['canViewSessions'] && $metrics['tables']['criticalSessions']->count() > 0)
                <div class="col-12 col-md-6">
                    <div class="card border-danger">
                        <div class="card-header bg-danger bg-opacity-10">
                            <h5 class="mb-0 text-danger">
                                <i class="la la-exclamation-triangle"></i>
                                {{ __('dashboard.critical_sessions_title') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                @foreach($metrics['tables']['criticalSessions'] as $critical)
                                    <div class="list-group-item px-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0">
                                                    {{ $critical['session']->name ?: $critical['session']->event->name }}
                                                </h6>
                                                <small class="text-muted">
                                                    {{ $critical['session']->starts_on->format('d/m/Y H:i') }}
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-danger">
                                                    {{ trans_choice('dashboard.available_n', $critical['available'], ['count' => $critical['available']]) }}
                                                </span>
                                                <div class="small text-muted">
                                                    {{ number_format($critical['percent_available'], 1) }}{{ __('dashboard.percent_free') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Panel de mensaje si el usuario tiene muy pocos permisos --}}
            @if(!$permissions['canViewStatistics'] && !$permissions['canViewEvents'] && !$permissions['canViewSessions'])
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="la la-info-circle" style="font-size: 4rem; color: #6c757d;"></i>
                            <h4 class="mt-3">{{ __('dashboard.limited_access_title') }}</h4>
                            <p class="text-muted">
                                {{ __('dashboard.limited_access_message') }}
                            </p>
                            @if($permissions['canViewClients'])
                                <a href="{{ backpack_url('client') }}" class="btn btn-primary">
                                    <i class="la la-users"></i> {{ __('dashboard.go_to_clients') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Historial (si existe) --}}
        @if(isset($history) && $history->count() > 0)
            <div class="row mt-4">
                <div class="col-12">
                    <h4 class="text-muted mb-3">{{ __('dashboard.updates_history') }}</h4>
                    <div class="accordion" id="historyAccordion">
                        @foreach($history as $feature)
                            <div class="accordion-item">
                                <h2 class="accordion-header bg-info" id="heading-{{ $feature->id }}">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapse-{{ $feature->id }}" aria-expanded="false">
                                        <strong class="text-white">{{ $feature->subject }}</strong>
                                        <span class="ms-2 badge bg-secondary">{{ $feature->version }}</span>
                                    </button>
                                </h2>
                                <div id="collapse-{{ $feature->id }}" class="accordion-collapse collapse"
                                    data-bs-parent="#historyAccordion">
                                    <div class="accordion-body">
                                        {!! $feature->content !!}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-2">
                {{ $history->onEachSide(1)->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection

@section('after_scripts')
    {{-- Solo cargar Chart.js si el usuario tiene permisos de estadísticas --}}
    @if($permissions['canViewStatistics'])
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Configuración global
                Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
                Chart.defaults.plugins.legend.labels.usePointStyle = true;
                Chart.defaults.plugins.legend.labels.padding = 15;

                // 1. Gráfica de evolución de ventas
                @if(isset($metrics['charts']['salesEvolution']) && $metrics['charts']['salesEvolution']->count() > 0)
                    const salesCtx = document.getElementById('salesEvolutionChart');
                    if (salesCtx) {
                        new Chart(salesCtx, {
                            type: 'line',
                            data: {
                                labels: {!! json_encode($metrics['charts']['salesEvolution']->pluck('date')) !!},
                                datasets: [{
                                    label: '{{ __('dashboard.sales_daily') }}',
                                    data: {!! json_encode($metrics['charts']['salesEvolution']->pluck('revenue')) !!},
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
                                                const count = {!! json_encode($metrics['charts']['salesEvolution']->pluck('count')->toArray()) !!}[context.dataIndex];
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

                // 2. Gráfica de ventas por canal
                @if(isset($metrics['charts']['salesByChannel']) && $metrics['charts']['salesByChannel']->count() > 0)
                    const channelCtx = document.getElementById('salesByChannelChart');
                    if (channelCtx) {
                        const channelData = {!! json_encode($metrics['charts']['salesByChannel']) !!};
                        const labels = Object.keys(channelData);
                        const data = labels.map(label => channelData[label].count);

                        new Chart(channelCtx, {
                            type: 'doughnut',
                            data: {
                                labels: labels,
                                datasets: [{
                                    data: data,
                                    backgroundColor: [
                                        'rgba(255, 99, 132, 0.8)',
                                        'rgba(54, 162, 235, 0.8)',
                                        'rgba(255, 206, 86, 0.8)',
                                        'rgba(75, 192, 192, 0.8)'
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
                                        position: 'bottom'
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                                return context.label + ': ' + percentage + '%';
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                @endif

                // 3. Gráfica de clientes
                @if(isset($metrics['analysis']['customers']) && !isset($metrics['analysis']['customers']['hidden']))
                    const customersCtx = document.getElementById('customersChart');
                    if (customersCtx) {
                        const newCustomers = {{ $metrics['analysis']['customers']['new'] }};
                        const recurringCustomers = {{ $metrics['analysis']['customers']['recurring'] }};

                        new Chart(customersCtx, {
                            type: 'pie',
                            data: {
                                labels: ['{{ __('dashboard.new') }}', '{{ __('dashboard.recurring') }}'],
                                datasets: [{
                                    data: [newCustomers, recurringCustomers],
                                    backgroundColor: [
                                        'rgba(40, 167, 69, 0.8)',
                                        'rgba(23, 162, 184, 0.8)'
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
                                        position: 'bottom'
                                    }
                                }
                            }
                        });
                    }
                @endif
            });
        </script>
    @endif