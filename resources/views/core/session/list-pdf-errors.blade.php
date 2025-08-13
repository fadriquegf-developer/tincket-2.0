@extends(backpack_view('layouts.horizontal'))

@section('content')
    @php $data = []; @endphp
    @foreach ($inscriptions as $inscription)
        @php
            $data[] = [
                'card_id'   => $inscription->cart->id,
                'name'      => $inscription->cart->client->name    ?? '',
                'surname'   => $inscription->cart->client->surname ?? '',
                'email'     => $inscription->cart->client->email   ?? '',
                'phone'     => $inscription->cart->client->phone   ?? '',
                'code'      => $inscription->cart->confirmation_code,
                'rate'      => $inscription->getRateName(),
                'slot'      => $inscription->slot->name ?? 'n/a',
                'bar_code'  => $inscription->barcode,
                'actions'   =>
                    '<div class="d-grid gap-1">'.
                        '<a href="'.url('cart/'.$inscription->cart->id.'/regenerate').'" target="_blank" class="btn btn-sm btn-outline-secondary">'.
                            '<i class="la la-file-pdf-o me-1"></i>'.__('backend.cart.regenerate_tickets').
                        '</a>'.
                        '<a href="'.url('cart/'.$inscription->cart->id.'/regenerate?send=true').'" target="_blank" class="btn btn-sm btn-outline-secondary">'.
                            '<i class="la la-envelope me-1"></i>'.__('backend.cart.regenerate_send_tickets').
                        '</a>'.
                    '</div>',
            ];
        @endphp
    @endforeach

    <div class="container-fluid mt-4"  >
        <div class="card shadow-xs border-xs ">
            <div class="card-header border-xs">
                <h3 class="mb-0">{{ __('backend.session.listPDF') }}</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="inscription-table" class="table table-sm table-striped table-hover mb-0 align-middle">
                        <thead class="border-secondary">
                            <tr>
                                <th>{{ __('backend.client.name') }}</th>
                                <th>{{ __('backend.client.surname') }}</th>
                                <th>{{ __('backend.client.email') }}</th>
                                <th>{{ __('backend.client.phone') }}</th>
                                <th>{{ __('backend.cart.confirmationcode') }}</th>
                                <th>{{ __('backend.menu.rate') }}</th>
                                <th>{{ __('backend.ticket.slot') }}</th>
                                <th>{{ __('backend.inscription.barcode') }}</th>
                                <th>{{ __('Acciones') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('after_styles')
    <link rel="stylesheet" href="{{ asset('vendor/backpack/crud/css/crud.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/backpack/crud/css/list.css') }}">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
    <style>
        #inscription-table { table-layout: auto; }
        #inscription-table th, #inscription-table td {
            vertical-align: middle;
            word-break: break-word;
        }
        #inscription-table td:last-child {
            white-space: nowrap;
            text-align: center;
        }
        #inscription-table td:last-child .btn {
            display: block;
            margin-bottom: 0.25rem;
        }
        #inscription-table th,
        #inscription-table td {
          padding: 0.75rem 1rem !important;
        }

        .dataTables_wrapper .row > div { padding: .5rem .75rem; }
        .dataTables_filter input { border-radius: .25rem; margin-right: 1rem }
        .dataTables_length { margin-left: 1rem; }
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
        .dataTables_info {
            margin-left: 1rem;
        }
        #inscription-table_paginate {
            margin-right: 1rem;
            margin-bottom: 1rem;
        }

    </style>
@endsection

@section('after_scripts')
    <script src="{{ asset('vendor/backpack/crud/js/crud.js') }}"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>

    <script>
        const data = @json($data);
        $(function () {
            $('#inscription-table').DataTable({
                data,
                pageLength: 10,
                responsive: true,
                autoWidth: false,
                ordering: true,
                columns: [
                    { data: 'name' },
                    { data: 'surname' },
                    { data: 'email' },
                    { data: 'phone' },
                    {
                        data: 'code',
                        render: function (data, type, row) {
                            return '<a href="/cart/'+row.card_id+'" target="_blank">'+data+'</a>';
                        }
                    },
                    { data: 'rate' },
                    { data: 'slot' },
                    { data: 'bar_code' },
                    { data: 'actions', orderable: false, searchable: false }
                ],
                dom: '<"row mb-2"<"col-sm-6"l><"col-sm-6"f>>rt<"row mt-2"<"col-sm-6"i><"col-sm-6"p>>',
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.12.1/i18n/{{ app()->getLocale() }}.json'
                }
            });
        });
    </script>
@endsection
