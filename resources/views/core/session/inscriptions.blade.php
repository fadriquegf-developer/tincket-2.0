@extends(backpack_view('layouts.horizontal'))

{{-- ─────────── CABECERA ─────────── --}}
@section('header')
    @php
        $totalValidated = $session->count_validated ?? 0;
        $validatedOut = $session->count_validated_out ?? 0;
        $validatedIn = max(0, $totalValidated - $validatedOut);
    @endphp
    <section class="content-header">
        <h1>{{ $session->event->name }}</h1>
        <ul class="list-unstyled mb-0">
            <li>{{ $session->space->name }}</li>
            <li>{{ \Carbon\Carbon::parse($session->starts_on)->format('H:i - d/m/Y') }}</li>
            <li>{{ \Carbon\Carbon::parse($session->ends_on)->format('H:i - d/m/Y') }}</li>
            <li>{{ __('backend.validation.validated') }}: {{ $stats['validated'] }}/{{ $stats['total'] }}</li>
            <li>{{ __('backend.validation.validated_in') }}: {{ $validatedIn }}/{{ $totalValidated }}</li>
            <li>{{ __('backend.validation.validated_out') }}: {{ $validatedOut }}/{{ $totalValidated }}</li>
            <li>{{ __('backend.statistics.sales.total_ticket_office') }}: {{ $stats['office_entries'] }}
                {{ __('backend.statistics.sales.inscriptions') }} | {{ $stats['office_amount'] }} €
            </li>
            <li>{{ __('backend.statistics.sales.total_web') }}: {{ $stats['web_entries'] }}
                {{ __('backend.statistics.sales.inscriptions') }} | {{ $stats['web_amount'] }} €
            </li>
            <li><strong>{{ __('backend.statistics.sales.total') }}:</strong> {{ $stats['total'] }}
                {{ __('backend.statistics.sales.inscriptions') }} | {{ $stats['total_amount'] }} €
            </li>
        </ul>
    </section>
@endsection

{{-- ─────────── CONTENIDO ─────────── --}}
@section('content')
<div class="container-fluid px-0">
    <div class="card mt-4 mx-0 w-100"><!-- full width -->
        <div class="card-header d-flex align-items-center">
            <h5 class="mb-0 flex-grow-1">{{ __('Llistat de registres de entrades') }}</h5>
            <a href="{{ route('session.inscriptions.exportExcel', $session->id) }}"
                class="btn btn-sm btn-secondary me-2">
                <i class="la la-file-excel"></i> Excel
            </a>
            <a href="{{ route('session.inscriptions.print', $session->id) }}" class="btn btn-sm btn-secondary"
                target="_blank">
                <i class="la la-print"></i> Imprimir
            </a>
        </div>
        <div class="card-body p-2">
            <table id="inscription-table" class="table table-striped table-bordered mb-0 w-100">
                <thead>
                    <tr>
                        <th>{{ __('backend.client.name') }}</th>
                        <th>{{ __('backend.client.surname') }}</th>
                        <th>{{ __('backend.client.email') }}</th>
                        <th>{{ __('backend.client.phone') }}</th>
                        <th>{{ __('backend.cart.confirmationcode') }}</th>
                        <th>{{ __('backend.ticket.payment_platform') }}</th>
                        <th>{{ __('backend.menu.rate') }}</th>
                        <th>{{ __('backend.ticket.slot') }}</th>
                        <th>{{ __('backend.inscription.barcode') }}</th>
                        <th>{{ __('backend.validation.validated') }}</th>
                        <th>DNI</th>
                        <th>{{ __('backend.events.created_at') }}</th>
                        <th>Metadata</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inscriptions as $i)
                    @php($c = optional(optional($i->cart)->client))
                    @php($meta = collect(json_decode($i->metadata, true)))
                    <tr>
                        <td>{{ $c->name }}</td>
                        <td>{{ $c->surname }}</td>
                        <td>{{ $c->email }}</td>
                        <td>{{ $c->phone }}</td>
                        <td>{{ optional($i->cart)->confirmation_code }}</td>
                        <td>{{ optional($i->cart->payment)->gateway }}</td>
                        <td>{{ optional($i->rate)->name }}</td>
                        <td>{{ optional($i->slot)->name ?? 'n/a' }}</td>
                        <td>{{ $i->barcode }}</td>
                        <td>{{ $i->checked_at ? __('backpack::crud.yes') : __('backpack::crud.no') }}</td>
                        <td>{{ $meta->get('dni', 'n/a') }}</td>
                        <td>{{ optional($i->cart)->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $meta->isEmpty() ? 'n/a' : e($meta->implode(' | ')) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

{{-- ─────────── CSS PARA FULL WIDTH & ZEBRA ─────────── --}}
@push('after_styles')
    <!-- DataTables + Buttons CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

    <style>
        .page-body main.container-xl {
            max-width: 80% !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        .page-body main.container-xl .card {
            margin-left: 0 !important;
            margin-right: 0 !important;
            width: 100%;
        }

        .table-striped>tbody>tr:nth-of-type(odd) {
            --bs-table-accent-bg: var(--bs-body-bg);
            background-color: rgba(var(--bs-body-bg-rgb), .05);
        }
        

        .dt-buttons .btn {
            margin-right: .5rem;
        }

        .page-body main.container-xl>.container-fluid {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        
    </style>
@endpush

{{-- ─────────── JS DATATABLES & BUTTONS ─────────── --}}
@push('after_scripts')
    <script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dt = $('#inscription-table').DataTable({
                pageLength: 25,
                scrollX: true,
                dom:
                    "<'row mb-2'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 text-end'B>>" +
                    "rt" +
                    "<'row mt-2'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                buttons: [
                    { extend: 'excelHtml5', className: 'btn btn-sm btn-secondary', text: 'Excel', title: '{{ $session->event->name }}' },
                    { extend: 'print', className: 'btn btn-sm btn-secondary', text: 'Imprimir', title: '{{ $session->event->name }}' }
                ],
                language: {
                    lengthMenu: "Mostrar _MENU_ entrades",
                    search: "Cercar:",
                    info: "Mostrant _START_-_END_ de _TOTAL_",
                    infoEmpty: "Mostrant 0-0 de 0",
                    zeroRecords: "No s'han trobat resultats",
                    paginate: { first: "Primer", previous: "Anterior", next: "Següent", last: "Darrer" }
                }
            });
        });
    </script>
@endpush