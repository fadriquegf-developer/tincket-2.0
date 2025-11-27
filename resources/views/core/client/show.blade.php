@extends(backpack_view('layouts.horizontal'))

@section('header')
    <div class="container-fluid d-flex justify-content-between my-3">
        <section class="header-operation animated fadeIn d-flex mb-2 align-items-baseline d-print-none">
            <h1 class="text-capitalize mb-0">{{ __('menu.clients') }}</h1>
            <p class="ms-2 ml-2 mb-0">
                {{ mb_ucfirst(trans('backpack::crud.preview')) }} {{ __('menu.client') }}
            </p>
            <p class="ms-2 ml-2 mb-0">
                <small><a href="{{ url('/client') }}" class="font-sm"><i class="la la-angle-double-left"></i>
                        {{ trans('backpack::crud.back_to_all') }}
                        <span>{{ __('menu.clients') }}</span></a></small>
            </p>
        </section>
        <a href="javascript: window.print();" class="btn btn-secondary float-right"><i class="la la-print"></i></a>
    </div>
@endsection

@section('content')
    <div class="container-fluid animated fadeIn">
        <div class="row">
            <div class="col-md-12">

                {{-- Información Principal del Cliente --}}
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">{{ __('backend.client.info') ?? 'Información del Cliente' }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            {{-- Columna Izquierda --}}
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-4">{{ __('menu.client') }} ID:</dt>
                                    <dd class="col-sm-8">{{ $crud->entry->id }}</dd>

                                    <dt class="col-sm-4">{{ __('backend.client.name') }}:</dt>
                                    <dd class="col-sm-8">{{ $crud->entry->name }}</dd>

                                    <dt class="col-sm-4">{{ __('backend.client.surname') }}:</dt>
                                    <dd class="col-sm-8">{{ $crud->entry->surname }}</dd>

                                    <dt class="col-sm-4">{{ __('backend.client.email') }}:</dt>
                                    <dd class="col-sm-8">
                                        <a href="mailto:{{ $crud->entry->email }}">{{ $crud->entry->email }}</a>
                                    </dd>

                                    <dt class="col-sm-4">{{ __('backend.client.phone') }}:</dt>
                                    <dd class="col-sm-8">{{ $crud->entry->phone ?? '-' }}</dd>

                                    <dt class="col-sm-4">{{ __('backend.client.mobile_phone') }}:</dt>
                                    <dd class="col-sm-8">{{ $crud->entry->mobile_phone ?? '-' }}</dd>

                                    <dt class="col-sm-4">DNI/NIE:</dt>
                                    <dd class="col-sm-8">{{ $crud->entry->dni ?? '-' }}</dd>

                                    <dt class="col-sm-4">{{ __('backend.client.date_birth') }}:</dt>
                                    <dd class="col-sm-8">
                                        {{ $crud->entry->date_birth ? $crud->entry->date_birth->format('d/m/Y') : '-' }}
                                    </dd>
                                </dl>
                            </div>

                            {{-- Columna Derecha --}}
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-4">{{ __('backend.client.address') }}:</dt>
                                    <dd class="col-sm-8">{{ $crud->entry->address ?? '-' }}</dd>

                                    <dt class="col-sm-4">{{ __('backend.client.postal_code') }}:</dt>
                                    <dd class="col-sm-8">{{ $crud->entry->postal_code ?? '-' }}</dd>

                                    <dt class="col-sm-4">{{ __('backend.client.city') }}:</dt>
                                    <dd class="col-sm-8">{{ $crud->entry->city ?? '-' }}</dd>

                                    <dt class="col-sm-4">{{ __('backend.client.province') }}:</dt>
                                    <dd class="col-sm-8">{{ $crud->entry->province ?? '-' }}</dd>

                                    <dt class="col-sm-4">{{ __('backend.client.locale') }}:</dt>
                                    <dd class="col-sm-8">
                                        @php
                                            $locales = ['es' => 'Español', 'ca' => 'Català', 'gl' => 'Galego'];
                                        @endphp
                                        {{ $locales[$crud->entry->locale] ?? $crud->entry->locale }}
                                    </dd>

                                    <dt class="col-sm-4">{{ __('backend.client.newsletter') }}:</dt>
                                    <dd class="col-sm-8">
                                        @if ($crud->entry->newsletter)
                                            <span class="badge badge-success">{{ __('backend.yes') ?? 'Sí' }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ __('backend.no') ?? 'No' }}</span>
                                        @endif
                                    </dd>

                                    <dt class="col-sm-4">{{ __('backend.client.num_session') }}:</dt>
                                    <dd class="col-sm-8">
                                        <span class="badge badge-info">{{ $crud->entry->getNumSessions() }}</span>
                                    </dd>

                                    <dt class="col-sm-4">{{ __('backend.client.created_at') }}:</dt>
                                    <dd class="col-sm-8">{{ $crud->entry->created_at->format('d/m/Y H:i') }}</dd>

                                    @if (get_brand_capability() === 'engine')
                                        <dt class="col-sm-4">Brand:</dt>
                                        <dd class="col-sm-8">{{ $crud->entry->brand->name ?? $crud->entry->brand_id }}</dd>
                                    @endif
                                </dl>
                            </div>
                        </div>

                        {{-- Campos Personalizados (FormFields) --}}
                        @php
                            $formFields = \App\Models\FormField::where('brand_id', $crud->entry->brand_id)
                                ->whereNull('deleted_at')
                                ->orderBy('weight')
                                ->get();
                            $answers = $crud->entry->answers->keyBy('field_id');
                        @endphp

                        @if ($formFields->count() > 0)
                            <hr>
                            <h5 class="mb-3">{{ __('backend.client.custom_fields') ?? 'Campos Adicionales' }}</h5>
                            <div class="row">
                                @foreach ($formFields as $field)
                                    @php
                                        $answer = $answers->get($field->id);
                                        $label = is_array($field->label)
                                            ? $field->label[app()->getLocale()] ?? array_values($field->label)[0]
                                            : $field->label;
                                    @endphp
                                    <div class="col-md-6">
                                        <dl class="row">
                                            <dt class="col-sm-4">{{ $label }}:</dt>
                                            <dd class="col-sm-8">
                                                @if ($answer && $answer->answer)
                                                    @switch($field->type)
                                                        @case('date')
                                                            {{ \Carbon\Carbon::parse($answer->answer)->format('d/m/Y') }}
                                                        @break

                                                        @case('boolean')
                                                            @if ($answer->answer)
                                                                <span
                                                                    class="badge badge-success">{{ __('backend.yes') ?? 'Sí' }}</span>
                                                            @else
                                                                <span
                                                                    class="badge badge-secondary">{{ __('backend.no') ?? 'No' }}</span>
                                                            @endif
                                                        @break

                                                        @default
                                                            {{ $answer->answer }}
                                                    @endswitch
                                                @else
                                                    -
                                                @endif
                                            </dd>
                                        </dl>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Botón de Editar --}}
                        <div class="mt-3">
                            <a href="{{ backpack_url('client/' . $crud->entry->id . '/edit') }}" class="btn btn-primary">
                                <i class="la la-edit"></i> {{ __('backend.edit') ?? 'Editar' }}
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Carritos (Carts) --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('backend.client.carts') ?? 'Carritos' }}</h5>
                    </div>
                    <div class="card-body">
                        @if ($crud->entry->carts && $crud->entry->carts->count())
                            <div class="table-responsive">
                                <table class="table table-hover table-sm">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('backend.cart.confirmationcode') ?? 'Código Confirmación' }}</th>
                                            <th>{{ __('backend.cart.status') ?? 'Estado' }}</th>
                                            <th>{{ __('backend.cart.total') ?? 'Total' }}</th>
                                            <th>{{ __('backend.created_at') ?? 'Creado' }}</th>
                                            <th>{{ __('backend.actions') ?? 'Acciones' }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($crud->entry->carts->take(10) as $i => $cart)
                                            <tr>
                                                <td>{{ $i + 1 }}</td>
                                                <td>
                                                    @if ($cart->confirmation_code)
                                                        <code>{{ $cart->confirmation_code }}</code>
                                                    @else
                                                        <span
                                                            class="text-muted">{{ __('backend.cart.pending') ?? 'Pendiente' }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($cart->confirmation_code)
                                                        <span
                                                            class="badge badge-success">{{ __('backend.cart.confirmed') ?? 'Confirmado' }}</span>
                                                    @elseif($cart->deleted_at)
                                                        <span
                                                            class="badge badge-danger">{{ __('backend.cart.deleted') ?? 'Eliminado' }}</span>
                                                    @else
                                                        <span
                                                            class="badge badge-warning">{{ __('backend.cart.pending') ?? 'Pendiente' }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($cart->price_sold > 0)
                                                        {{ number_format($cart->price_sold, 2, ',', '.') }} €
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>{{ $cart->created_at->format('d/m/Y H:i') }}</td>
                                                <td>
                                                    <a href="{{ backpack_url('cart/' . $cart->id . '/show') }}"
                                                        class="btn btn-sm btn-link">
                                                        <i class="la la-eye"></i> {{ __('backend.view') ?? 'Ver' }}
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if ($crud->entry->carts->count() > 10)
                                <p class="text-muted small mt-2">
                                    {{ __('backend.showing_first_n', ['n' => 10]) ?? 'Mostrando los primeros 10 de' }}
                                    {{ $crud->entry->carts->count() }} {{ __('backend.carts') ?? 'carritos' }}.
                                </p>
                            @endif
                        @else
                            <p class="text-muted mb-0">
                                {{ __('backend.client.no_carts') ?? 'Este cliente no tiene carritos.' }}</p>
                        @endif
                    </div>
                </div>

                {{-- Inscripciones/Sesiones --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('backend.client.inscriptions') ?? 'Inscripciones' }}</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $inscriptions = $crud->entry
                                ->inscriptions()
                                ->with(['session.event', 'cart'])
                                ->whereHas('cart', function ($q) {
                                    $q->whereNotNull('confirmation_code');
                                })
                                ->orderBy('created_at', 'desc')
                                ->take(10)
                                ->get();
                        @endphp

                        @if ($inscriptions->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover table-sm">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('backend.events.event') ?? 'Evento' }}</th>
                                            <th>{{ __('backend.session.session') ?? 'Sesión' }}</th>
                                            <th>{{ __('backend.session.date') ?? 'Fecha' }}</th>
                                            <th>{{ __('backend.cart.confirmationcode') ?? 'Código' }}</th>
                                            <th>{{ __('backend.actions') ?? 'Acciones' }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($inscriptions as $i => $inscription)
                                            <tr>
                                                <td>{{ $i + 1 }}</td>
                                                <td>{{ $inscription->session->event->name ?? '-' }}</td>
                                                <td>{{ $inscription->session->name ?? '-' }}</td>
                                                <td>
                                                    @if ($inscription->session && $inscription->session->starts_on)
                                                        {{ $inscription->session->starts_on->format('d/m/Y H:i') }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td><code>{{ $inscription->cart->confirmation_code }}</code></td>
                                                <td>
                                                    <a href="{{ backpack_url('cart/' . $inscription->cart_id . '/show') }}"
                                                        class="btn btn-sm btn-link">
                                                        <i class="la la-eye"></i> {{ __('backend.view') ?? 'Ver' }}
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted mb-0">
                                {{ __('backend.client.no_inscriptions') ?? 'Este cliente no tiene inscripciones confirmadas.' }}
                            </p>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('after_styles')
    <style>
        dl.row dt {
            text-align: right;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .card-header h4,
        .card-header h5 {
            margin-bottom: 0;
        }

        .table-sm td,
        .table-sm th {
            padding: 0.3rem;
        }
    </style>
@endsection
