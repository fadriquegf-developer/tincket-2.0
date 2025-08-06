@extends(backpack_view('layouts.horizontal'))

@section('header')
    <div class="container-fluid mb-3">
        <h2 class="mb-0">{{ trans('backpack::base.dashboard') }}</h2>
    </div>
@endsection

@section('content')

    <div class="container-fluid">
        <div class="row">

            @if (get_brand_capability() != 'promoter')
                {{-- Estadísticas básicas --}}
                <div class="col-12">
                    <h3 class="text-muted mb-3">{{ __('backend.dashboard.basic_statics') }}</h3>
                    <div class="row">
                        {{-- Clientes --}}
                        <div class="col-sm-6 col-lg-4 col-xl-3">
                            <div class="card mb-3">
                                <div class="card-header bg-primary text-white">{{ __('backend.dashboard.clients') }}</div>
                                <div class="card-body">
                                    <table class="table table-sm text-end mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>{{ __('backend.dashboard.register_last_month') }}</th>
                                                <th>{{ __('backend.dashboard.totals') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    {{ \App\Models\Client::whereBrandId(get_current_brand()->id)->where('created_at', '>=', \Carbon\Carbon::now()->subMonth())->count() }}
                                                </td>
                                                <td>
                                                    {{ \App\Models\Client::whereBrandId(get_current_brand()->id)->count() }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        {{-- Ventas --}}
                        <div class="col-sm-6 col-lg-4 col-xl-3">
                            <div class="card">
                                <div class="card-header bg-info text-white">{{ __('backend.dashboard.sell') }}</div>
                                <div class="card-body">
                                    <table class="table table-sm text-end mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>{{ __('backend.dashboard.today') }}</th>
                                                <th>{{ __('backend.dashboard.last_month') }}</th>
                                                <th>{{ __('backend.dashboard.last_year') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    {{ \App\Models\Cart::whereBrandId(get_current_brand()->id)->confirmed()->where('created_at', '>=', \Carbon\Carbon::today())->count() }}
                                                </td>
                                                <td>
                                                    {{ \App\Models\Cart::whereBrandId(get_current_brand()->id)->confirmed()->where('created_at', '>=', \Carbon\Carbon::now()->subMonth())->count() }}
                                                </td>
                                                <td>
                                                    {{ \App\Models\Cart::whereBrandId(get_current_brand()->id)->confirmed()->where('created_at', '>=', \Carbon\Carbon::now()->subYear())->count() }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            {{-- Notificaciones --}}
            <div class="col-12">
                @if ($history->count())
                    <h3 class="text-muted mb-3">{{ __('backend.dashboard.history_update') }}</h3>

                    {{-- Accordion contenedor --}}
                    <div class="accordion" id="historyAccordion">
                        @foreach ($history as $feature)
                            @php
                                // id único para enlazar título ↔ contenido
                                $uid = 'feature-' . $feature->id;
                            @endphp

                            <div class="accordion-item">
                                <h2 class="accordion-header bg-primary" id="heading-{{ $uid }}">
                                    <button class="accordion-button collapsed text-white" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#collapse-{{ $uid }}"
                                        aria-expanded="false" aria-controls="collapse-{{ $uid }}">
                                        <strong>{{ $feature->subject }}</strong>&nbsp;|&nbsp;{{ $feature->version }}
                                    </button>
                                </h2>

                                <div id="collapse-{{ $uid }}" class="accordion-collapse collapse"
                                    aria-labelledby="heading-{{ $uid }}" data-bs-parent="#historyAccordion">
                                    <div class="accordion-body">
                                        {!! $feature->content !!}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>


@endsection
