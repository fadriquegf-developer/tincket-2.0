{{-- resources/views/core/pack/list-inscriptions.blade.php --}}
@extends(backpack_view('layouts.horizontal'))

@php
    $t = [
        'export'         => __('backpack::crud.export.export'),
        'copy'           => __('backpack::crud.export.copy'),
        'excel'          => __('backpack::crud.export.excel'),
        'csv'            => __('backpack::crud.export.csv'),
        'pdf'            => __('backpack::crud.export.pdf'),
        'print'          => __('backpack::crud.export.print'),
        'col_visibility' => __('backpack::crud.export.column_visibility'),
        'search'         => __('backpack::crud.search'),
    ];
@endphp

@section('content')
<h1 class="my-4">{{ $pack->name }}</h1>
    <div class="card">
        {{-- CABECERA --}}
        <div class="card-header d-flex justify-content-between align-items-center py-3">
            <div class="d-flex align-items-center flex-wrap gap-2">
                
                <small >{{ __('backend.inscription.list_title') }}</small><span>-</span>
                <small id="datatable_info_stack"></small>
            </div>

            {{-- buscador: se rellena con JS --}}
            <div id="datatable_search_stack" style="min-width:220px"></div>
        </div>

        {{-- TABLA --}}
        <div class="card-body p-0">
            <table id="inscription-table" class="crudTable table align-middle m-0 nowrap w-100">
                <thead>
                <tr>
                    <th>{{ __('backend.client.name') }}</th>
                    <th>{{ __('backend.client.surname') }}</th>
                    <th>{{ __('backend.client.email') }}</th>
                    <th>{{ __('backend.cart.confirmationcode') }}</th>
                    <th>{{ __('backend.statistics.sales.sold_at') }}</th>
                    <th>{{ __('backend.client.created_at') }}</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection



@push('after_styles')
    {{-- skins Bootstrap-5 --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

    <style>
        /* Backpack oculta .dt-buttons → la mostramos */
        .dataTables_wrapper .dt-buttons{display:inline-block!important;margin-bottom:0}

        /* bordes finos nativos */
        #inscription-table thead th{
            border-bottom:1px solid rgba(255,255,255,.04)!important;
        }
        #inscription-table tbody tr:not(:last-child) td{
            border-bottom:1px solid rgba(255,255,255,.04)!important;
        }

        /* -------------- ZEBRA -------------- */
        /* tema oscuro */
        html[data-bs-theme="dark"] #inscription-table tbody tr:nth-child(even) td{
            background:rgba(255,255,255,.005)!important;
        }
        /* tema claro */
        html[data-bs-theme="light"] #inscription-table tbody tr:nth-child(even) td{
            background:rgba(0,0,0,.02)!important;
        }
        /* Hover (igual en ambos temas) */
        #inscription-table tbody tr:hover td{
            background:rgba(255,255,255,.04)!important;
        }

        /* -------------- BUSCADOR -------------- */
        /* oscuro */
        html[data-bs-theme="dark"] #datatable_search_stack .form-control,
        html[data-bs-theme="dark"] #datatable_search_stack .input-group-text{
            background:#1e1e23;border-color:#343a40;color:#fff;
        }
        /* claro */
        html[data-bs-theme="light"] #datatable_search_stack .form-control,
        html[data-bs-theme="light"] #datatable_search_stack .input-group-text{
            background:#f8f9fa;border-color:#dee2e6;color:#495057;
        }

        /* margen al bloque inferior (length + botones + paginador) */
        .dataTables_wrapper .row.mt-2{margin:1rem 0!important}

        /* ocultar buscador interno DT */
        .dataTables_filter{display:none!important}
    </style>
@endpush



@push('after_scripts')
    {{-- scripts DataTables + extensiones (…igual que antes…) --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>

    <script>
        $(function () {

            /* ---------- VARS ---------- */
            const rows  = @json($data);
            const lang  = @json($t);
            const title = @json($pack->name);

            /* ---------- DATATABLE ---------- */
            let table = $('#inscription-table').DataTable({
                data       : rows,
                responsive : true,
                pageLength : 10,

                columns: [
                    {data: 'name'}, {data: 'surname'}, {data: 'email'},
                    {data: 'code'}, {data: 'seller_type'}, {data: 'created_at'},
                ],

                dom: "rt<'row mt-2'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4 text-end'p>>",

                buttons: [
                    {
                        extend   : 'collection',
                        text     : `<i class="la la-download me-1"></i>${lang.export}`,
                        className: 'btn btn-sm btn-outline-secondary',
                        buttons  : [
                            {extend:'copy',  text:lang.copy },
                            {extend:'excel', text:lang.excel , title:title},
                            {extend:'csv',   text:lang.csv  },
                            {extend:'pdf',   text:lang.pdf  },
                            {extend:'print', text:lang.print, title:title},
                        ]
                    },
                    {
                        extend   : 'colvis',
                        text     : `<i class="la la-columns"></i> ${lang.col_visibility}`,
                        className: 'btn btn-sm btn-outline-secondary'
                    }
                ],

                language: {
                    url:  '{{ asset('vendor/datatables/i18n/ca.json') }}',
                    searchPlaceholder: lang.search
                },

                stripeClasses: [] // zebra manual
            });

            /* ---------- BUSCADOR EXTERNO ---------- */
            $('#datatable_search_stack').html(`
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" placeholder="${lang.search}">
                    <span class="input-group-text"><i class="la la-search"></i></span>
                </div>`
            );
            $('#datatable_search_stack input').on('keyup', function (){
                table.search(this.value).draw();
            });

            /* ---------- TEXTO “Mostrando …” EN CABECERA ---------- */
            const infoTarget = $('#datatable_info_stack');
            const refreshInfo = () => {
                let i = table.page.info();
                infoTarget.text(` Mostrant del ${i.start + 1} al ${i.end} d’un total de ${i.recordsTotal} registres`);
            };
            table.on('draw', refreshInfo);
            refreshInfo();
        });
    </script>
@endpush
