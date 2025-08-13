{{-- resources/views/core/ticket-office/create.blade.php --}}
@extends(backpack_view('blank'))

@php
    $defaultBreadcrumbs = [
        trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
        trans('ticket-office.tickets_office') => route('ticket-office.create'),
        trans('ticket-office.create') => false,
    ];
    $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
    {{-- Título de la página --}}
    {{ trans('ticket-office.tickets_office') }}
    {{ Request::get('show_expired') ? trans('ticket-office.all_sessions') : '' }}
    {{ trans('ticket-office.create') }}
@endsection

@section('content')
    {{-- Mantén los partials para valores antiguos y errores de validación --}}
    @include('core.ticket-office.inc.form_old_values')
    @include('core.ticket-office.inc.form_errors')

    {{-- Token CSRF --}}
    {{ csrf_field() }}

    {{-- Contenedor donde Vue montará la aplicación de taquilla --}}
    <div id="ticketOfficeApp">
        <ticket-office-app></ticket-office-app>
    </div>

    {{-- Sección de datos del cliente (se mantiene en Blade) --}}
    @include('core.ticket-office.inc.client')

    {{-- Sección de pago (se mantiene en Blade) --}}
    @include('core.ticket-office.inc.payment')

    {{-- Loader opcional --}}
    @if ($sessions->isNotEmpty())
        @include('core.ticket-office.inc.loading')
    @endif
@endsection

@section('after_scripts')
    @parent

    {{-- Evita enviar el formulario con Enter --}}
    <script>
        document.addEventListener('keypress', function(e) {
            if (e.target.form && e.which === 13) {
                e.preventDefault();
            }
        });
        document.addEventListener('submit', function(e) {
            if (e.target.matches('form')) {
                e.target.querySelector('.btn-confirm')?.setAttribute('disabled', true);
            }
        });
    </script>

    {{-- Exportamos las sesiones a una variable global para que Vue pueda leerlas --}}
    @if ($json_sessions->count() > 0)
        <script>
            window.sessions_list = {!! $json_sessions->toJson() !!};
        </script>
    @else
        <script>
            window.sessions_list = [{ space: { layout: '' } }];
        </script>
    @endif

    {{-- Cargamos el bundle de Vue generado por Vite --}}
    @vite('resources/js/ticket-office/ticket-office.js')
@endsection
