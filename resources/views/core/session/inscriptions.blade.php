@extends(backpack_view('blank'))

@php
    $defaultBreadcrumbs = [
        trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
        __('backend.session.sessions') => url('session'),
        $session->event->name => false,
    ];

    $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;

    // Calcular estadísticas
    $totalValidated = $stats['validated'] ?? 0;
    $validatedOut = $stats['validated_out'] ?? 0;
    $validatedIn = $stats['validated_in'] ?? 0;
@endphp

@section('header')
    <section class="header-operation container-fluid animated fadeIn d-flex mb-2 align-items-baseline d-print-none"
        bp-section="page-header">
        <h1 class="text-capitalize mb-0" bp-section="page-heading">
            {{ $session->event->name }}@if ($session->name)
                - {{ $session->name }}
            @endif
        </h1>
        <p class="ms-2 ml-2 mb-0" bp-section="page-subheading">{{ __('backend.inscription.list_title') }}</p>
    </section>

    {{-- Información de la sesión --}}
    <section class="content-header container-fluid mb-3 d-print-none">
        <div class="card shadow-xs border-xs">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">{{ __('backend.session.session_info') }}</h6>
                        <ul class="list-unstyled mb-0">
                            <li><strong>{{ __('backend.session.space') }}:</strong> {{ $session->space->name }}</li>
                            <li><strong>{{ __('backend.events.start_on') }}:</strong>
                                {{ \Carbon\Carbon::parse($session->starts_on)->format('H:i - d/m/Y') }}</li>
                            <li><strong>{{ __('backend.validation.ends_on') }}:</strong>
                                {{ \Carbon\Carbon::parse($session->ends_on)->format('H:i - d/m/Y') }}</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">{{ __('backend.statistics.statistics') }}</h6>
                        <ul class="list-unstyled mb-0">
                            <li><strong>{{ __('backend.validation.validated') }}:</strong>
                                {{ $stats['validated'] }}/{{ $stats['total'] }}</li>
                            <li><strong>{{ __('backend.validation.validated_in') }}:</strong>
                                {{ $validatedIn }}/{{ $totalValidated }}</li>
                            <li><strong>{{ __('backend.validation.validated_out') }}:</strong>
                                {{ $validatedOut }}/{{ $totalValidated }}</li>
                            <li><strong>{{ __('backend.statistics.sales.total_ticket_office') }}:</strong>
                                {{ $stats['office_entries'] }} {{ __('backend.statistics.sales.inscriptions') }} |
                                {{ $stats['office_amount'] }} €
                            </li>
                            <li><strong>{{ __('backend.statistics.sales.total_web') }}:</strong>
                                {{ $stats['web_entries'] }} {{ __('backend.statistics.sales.inscriptions') }} |
                                {{ $stats['web_amount'] }} €
                            </li>
                            <li><strong>{{ __('backend.statistics.sales.total') }}:</strong> {{ $stats['total'] }}
                                {{ __('backend.statistics.sales.inscriptions') }} | {{ $stats['total_amount'] }} €
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('content')
    <div class="row" bp-section="crud-operation-list">
        <div class="col-md-12">

            {{-- Botones de acción --}}
            <div class="row mb-2 align-items-center">
                <div class="col-sm-9">
                    <div class="d-print-none">
                        <a href="{{ route('session.inscriptions.exportExcel', $session->id) }}"
                            class="btn btn-sm btn-secondary me-2">
                            <i class="la la-file-excel"></i> Excel
                        </a>
                        <a href="{{ route('session.inscriptions.print', $session->id) }}" class="btn btn-sm btn-secondary"
                            target="_blank">
                            <i class="la la-print"></i> {{ __('Imprimir') }}
                        </a>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div id="datatable_search_stack" class="mt-sm-0 mt-2 d-print-none">
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"></path>
                                    <path d="M21 21l-6 -6"></path>
                                </svg>
                            </span>
                            <input type="search" class="form-control"
                                placeholder="{{ trans('backpack::crud.search') }}..." />
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabla principal --}}
            <div class="{{ backpack_theme_config('classes.tableWrapper') ?? 'table-responsive' }}">
                <table id="inscription-table"
                    class="{{ backpack_theme_config('classes.table') ?? 'table table-striped table-hover nowrap rounded card-table table-vcenter card d-table shadow-xs border-xs' }}"
                    cellspacing="0">
                    <thead>
                        <tr>
                            <th data-orderable="true" data-priority="11">{{ __('backend.client.name') }}</th>
                            <th data-orderable="true" data-priority="12">{{ __('backend.client.surname') }}</th>
                            <th data-orderable="true" data-priority="8">{{ __('backend.client.email') }}</th>
                            <th data-orderable="true" data-priority="7">{{ __('backend.client.phone') }}</th>
                            <th data-orderable="true" data-priority="1">{{ __('backend.cart.confirmationcode') }}</th>
                            <th data-orderable="true" data-priority="3">{{ __('backend.ticket.payment_platform') }}</th>
                            <th data-orderable="true" data-priority="5">{{ __('menu.rate') }}</th>
                            <th data-orderable="true" data-priority="4">{{ __('backend.rate.price') }}</th>
                            <th data-orderable="true" data-priority="6">{{ __('backend.ticket.slot') }}</th>
                            <th data-orderable="true" data-priority="10">{{ __('backend.inscription.barcode') }}</th>
                            <th data-orderable="true" data-priority="9">{{ __('backend.validation.validated') }}</th>
                            <th data-orderable="true" data-priority="13">DNI</th>
                            <th data-orderable="true" data-priority="4">{{ __('backend.events.created_at') }}</th>
                            <th data-orderable="false" data-priority="14">Metadata</th>
                            <th data-orderable="false" data-priority="2">{{ __('backend.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($inscriptions as $i)
                            @php
                                $c = optional(optional($i->cart)->client);
                                $meta = collect(
                                    is_array($i->metadata) ? $i->metadata : json_decode($i->metadata, true),
                                );
                            @endphp
                            <tr>
                                <td>{{ $c->name }}</td>
                                <td>{{ $c->surname }}</td>
                                <td>{{ $c->email }}</td>
                                <td>{{ $c->phone }}</td>
                                <td>{{ optional($i->cart)->confirmation_code }}</td>
                                <td>
                                    @php
                                        $gateway = optional($i->cart->payment)->gateway;
                                        $paymentMethod = optional($i->cart->payment)->gateway_payment_type;
                                    @endphp


                                    @if ($gateway === 'TicketOffice')
                                        {{ $gateway }}
                                        ({{ match ($paymentMethod) {
                                            'cash' => __('backend.cart.cash'),
                                            'card' => __('backend.cart.card_simple'),
                                            default => 'n/a',
                                        } }})
                                    @else
                                        {{ $gateway === 'Free' ? 'Free (web)' : $gateway }}
                                    @endif
                                </td>
                                <td>{{ optional($i->rate)->name }}</td>
                                <td>{{ number_format($i->price_sold, 2) }} €</td>
                                <td>{{ optional($i->slot)->name ?? 'n/a' }}</td>
                                <td><code>{{ $i->barcode }}</code></td>
                                <td>
                                    @if ($i->checked_at)
                                        <span class="badge bg-success">{{ __('backpack::crud.yes') }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ __('backpack::crud.no') }}</span>
                                    @endif
                                </td>
                                <td>{{ $meta->get('dni', 'n/a') }}</td>
                                <td>{{ optional($i->cart)->created_at?->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if ($meta->isNotEmpty())
                                        <small class="text-muted">{{ $meta->implode(' | ') }}</small>
                                    @else
                                        <span class="text-muted">n/a</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="dropdown">
                                        @can('carts.index')
                                            <button class="btn btn-sm btn-outline-primary dropdown-toggle pe-2" type="button"
                                                id="dropdownActions{{ $i->id }}" data-bs-toggle="dropdown"
                                                data-bs-auto-close="outside" aria-expanded="false"><i
                                                    class="la la-download me-1"></i>
                                                {{ __('backend.cart.download') }}
                                            </button>
                                        @endcan

                                        <ul class="dropdown-menu" aria-labelledby="dropdownActions{{ $i->id }}">
                                            @if (Route::has('inscription.generate'))
                                                <li>
                                                    <a class="dropdown-item"
                                                        href="{{ route('inscription.generate', ['inscription' => $i->id, 'web' => 1]) }}"
                                                        target="_blank">
                                                        <i class="la la-file-pdf-o me-1"></i>
                                                        {{ __('backend.cart.inc.download') }}
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item"
                                                        href="{{ route('inscription.generate', ['inscription' => $i->id, 'ticket-office' => 1]) }}"
                                                        target="_blank">
                                                        <i class="la la-file-pdf-o me-1"></i>
                                                        {{ __('backend.cart.inc.download_ticket_office') }}
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>{{ __('backend.client.name') }}</th>
                            <th>{{ __('backend.client.surname') }}</th>
                            <th>{{ __('backend.client.email') }}</th>
                            <th>{{ __('backend.client.phone') }}</th>
                            <th>{{ __('backend.cart.confirmationcode') }}</th>
                            <th>{{ __('backend.ticket.payment_platform') }}</th>
                            <th>{{ __('menu.rate') }}</th>
                            <th>{{ __('backend.rate.price') }}</th>
                            <th>{{ __('backend.ticket.slot') }}</th>
                            <th>{{ __('backend.inscription.barcode') }}</th>
                            <th>{{ __('backend.validation.validated') }}</th>
                            <th>DNI</th>
                            <th>{{ __('backend.events.created_at') }}</th>
                            <th>Metadata</th>
                            <th>{{ __('backend.actions') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Información adicional del datatable --}}
            <div id="datatable_info_stack" class="mt-2"></div>
            <div id="datatable_button_stack" class="float-end text-right hidden-xs d-print-none"></div>

        </div>
    </div>
@endsection

@section('after_styles')
    {{-- DATA TABLES --}}
    @basset('https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css')
    @basset('https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css')
    @basset('https://cdn.datatables.net/responsive/2.4.0/css/responsive.dataTables.min.css')
    @basset('https://cdn.datatables.net/fixedheader/3.3.1/css/fixedHeader.dataTables.min.css')

    <style>
        /* Mejoras específicas para esta vista */
        .input-icon {
            position: relative;
        }

        .input-icon-addon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2;
        }

        .input-icon .form-control {
            padding-left: 2.5rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .card-body .row .col-md-6:first-child {
                margin-bottom: 1rem;
            }
        }

        /* Mejorar el aspecto del código de barras */
        code {
            font-size: 0.875em;
            background-color: var(--bs-gray-100);
            padding: 0.125rem 0.25rem;
            border-radius: 0.25rem;
        }
    </style>
@endsection

@section('after_scripts')
    {{-- 1. jQuery --}}
    @basset('https://code.jquery.com/jquery-3.7.1.min.js')

    {{-- 2. DataTables core (must be BEFORE the plugin) --}}
    @basset('https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js')

    {{-- 3. Moment + DataTables datetime plugin (must be AFTER DataTables) --}}
    @basset('https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js')
    @basset('https://cdn.datatables.net/plug-ins/1.13.8/sorting/datetime-moment.js')

    {{-- 4. All the rest (buttons, bootstrap, responsive, etc.) --}}
    @basset('https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js')
    @basset('https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js')
    @basset('https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js')
    @basset('https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js')
    @basset('https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js')
    @basset('https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js')
    @basset('https://cdn.datatables.net/responsive/2.4.0/js/dataTables.responsive.min.js')


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $.fn.dataTable.moment('DD/MM/YYYY HH:mm');

            // Configuración del DataTable siguiendo el patrón de Backpack 6
            const dt = $('#inscription-table').DataTable({
                pageLength: 25,
                responsive: true,
                autoWidth: false,
                processing: true,
                deferRender: true,
                ordering: true,
                stateSave: true,
                stateDuration: 60 * 60 * 24, // 24 horas
                dom: "<'row mb-2'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 text-end'B>>" +
                    "rt" +
                    "<'row mt-2'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                buttons: [{
                        extend: 'excelHtml5',
                        className: 'btn btn-sm btn-secondary me-1',
                        text: '<i class="la la-file-excel"></i> Excel',
                        title: '{{ $session->event->name }} - Inscripciones',
                        filename: '{{ Str::slug($session->event->name) }}_inscripciones_{{ date('Y-m-d') }}'
                    },
                    {
                        extend: 'print',
                        className: 'btn btn-sm btn-secondary',
                        text: '<i class="la la-print"></i> Imprimir',
                        title: '{{ $session->event->name }} - Inscripciones'
                    }
                ],
                language: {
                    @if (app()->getLocale() === 'ca')
                        "sProcessing": "Processant...",
                        "sLengthMenu": "Mostra _MENU_ registres",
                        "sZeroRecords": "No s'han trobat registres",
                        "sEmptyTable": "No hi ha dades disponibles en aquesta taula",
                        "sInfo": "Mostrant registres del _START_ al _END_ d'un total de _TOTAL_ registres",
                        "sInfoEmpty": "Mostrant registres del 0 al 0 d'un total de 0 registres",
                        "sInfoFiltered": "(filtrat d'un total de _MAX_ registres)",
                        "sInfoPostFix": "",
                        "sSearch": "Cercar:",
                        "sUrl": "",
                        "sInfoThousands": ".",
                        "sLoadingRecords": "Carregant...",
                        "oPaginate": {
                            "sFirst": "Primer",
                            "sLast": "Últim",
                            "sNext": "Següent",
                            "sPrevious": "Anterior"
                        },
                        "oAria": {
                            "sSortAscending": ": Activar per ordenar la columna de manera ascendent",
                            "sSortDescending": ": Activar per ordenar la columna de manera descendent"
                        },
                        "buttons": {
                            "copy": "Copiar",
                            "colvis": "Visibilitat"
                        }
                    @elseif (app()->getLocale() === 'gl')
                        "sProcessing": "Procesando...",
                        "sLengthMenu": "Amosar _MENU_ rexistros",
                        "sZeroRecords": "Non se atoparon resultados",
                        "sEmptyTable": "Ningún dato dispoñible nesta táboa",
                        "sInfo": "Amosando rexistros do _START_ ao _END_ dun total de _TOTAL_ rexistros",
                        "sInfoEmpty": "Amosando rexistros do 0 ao 0 dun total de 0 rexistros",
                        "sInfoFiltered": "(filtrado dun total de _MAX_ rexistros)",
                        "sInfoPostFix": "",
                        "sSearch": "Buscar:",
                        "sUrl": "",
                        "sInfoThousands": ".",
                        "sLoadingRecords": "Cargando...",
                        "oPaginate": {
                            "sFirst": "Primeiro",
                            "sLast": "Último",
                            "sNext": "Seguinte",
                            "sPrevious": "Anterior"
                        },
                        "oAria": {
                            "sSortAscending": ": Activar para ordenar a columna de xeito ascendente",
                            "sSortDescending": ": Activar para ordenar a columna de xeito descendente"
                        },
                        "buttons": {
                            "copy": "Copiar",
                            "colvis": "Visibilidade"
                        }
                    @else
                        "sProcessing": "Procesando...",
                        "sLengthMenu": "Mostrar _MENU_ registros",
                        "sZeroRecords": "No se encontraron resultados",
                        "sEmptyTable": "Ningún dato disponible en esta tabla",
                        "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                        "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
                        "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
                        "sInfoPostFix": "",
                        "sUrl": "",
                        "sInfoThousands": ",",
                        "sLoadingRecords": "Cargando...",
                        "oPaginate": {
                            "sFirst": "Primero",
                            "sLast": "Último",
                            "sNext": "Siguiente",
                            "sPrevious": "Anterior"
                        },
                    @endif
                },
                columnDefs: [{
                        targets: [10],
                        orderable: true,
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return data;
                            }
                            return data.includes('Sí') || data.includes('Yes') ? 1 : 0;
                        }
                    },
                    {
                        targets: [13],
                        orderable: false
                    },
                ],
                initComplete: function() {
                    // Conectar el campo de búsqueda personalizado
                    $('#datatable_search_stack input').on('keyup', function() {
                        dt.search(this.value).draw();
                    });

                    // Mover los botones al contenedor personalizado si existe
                    if ($('#datatable_button_stack').length) {
                        $('.dt-buttons').appendTo('#datatable_button_stack');
                    }

                    // Mover la información al contenedor personalizado si existe
                    if ($('#datatable_info_stack').length) {
                        $('.dataTables_info, .dataTables_paginate').appendTo('#datatable_info_stack');
                    }
                }
            });

            // Sincronizar el campo de búsqueda con el DataTable
            $('#datatable_search_stack input').val(dt.search());
        });
    </script>
@endsection
