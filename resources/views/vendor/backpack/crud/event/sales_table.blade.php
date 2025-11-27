{{-- resources/views/vendor/backpack/crud/event/sales_table.blade.php --}}
@php $sales = $entry->salesFromStats(); @endphp

@if ($sales->count())
  <div class="card shadow-xs border-xs">
    <div class="card-header border-xs">
      <h3 class="mb-0">{{ __('backend.events.sales') }}</h3>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table id="session-sales-table" class="table table-sm table-striped table-hover mb-0 align-middle">
          <thead class="border-secondary">
            <tr>
              <th>#</th>
              <th>{{ __('menu.client') }}</th>
              <th>{{ __('backend.client.phone') }}</th>
              <th>{{ __('backend.events.cart') }}</th>
              <th>TARIFA</th>
              <th>CANAL</th>
              <th>{{ __('backend.events.start_on') }}</th>
              <th>{{ __('backend.events.created_at') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($sales as $sale)
              @php
                // Parse tarifa (JSON traducible)
                $rateData = $sale->rate_name ? json_decode($sale->rate_name, true) : null;
                $rateName = $rateData[app()->getLocale()] ?? $rateData['ca'] ?? $rateData['es'] ?? '-';

                // Determinar canal - Pot ser App\User, App\Application, App\Models\User o App\Models\Application
                $sellerType = $sale->seller_type ?? '';

                if (str_contains($sellerType, 'User')) {
                  $channel = 'taquilla';
                } elseif (str_contains($sellerType, 'Application')) {
                  $channel = 'web';
                }
              @endphp
              <tr>
                <td>{{ $sale->id }}</td>
                <td>{{ $sale->email ?? '-' }}</td>
                <td>{{ $sale->phone ?? '-' }}</td>
                <td>{{ $sale->cart_confirmation_code ?? '-' }}</td>
                <td>
                  {{ $rateName }}
                  @if($sale->price_sold)
                    <br><small class="text-muted">{{ number_format($sale->price_sold, 2) }}€</small>
                  @endif
                </td>
                <td>
                  @if($channel == 'web')
                    <span class="badge bg-primary">
                      <i class="las la-globe"></i> Web
                    </span>
                  @else($channel == 'taquilla')
                    <span class="badge bg-success">
                      <i class="las la-store"></i> Taquilla
                    </span>
                  @endif
                </td>
                <td>{{ $sale->session_start ? \Carbon\Carbon::parse($sale->session_start)->format('d/m/Y H:i') : '-' }}</td>
                <td>{{ $sale->created_at ? \Carbon\Carbon::parse($sale->created_at)->format('d/m/Y H:i') : '-' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
@else
  <p class="text-muted mt-4">{{ __('No hi ha vendes per aquest esdeveniment.') }}</p>
@endif

@push('after_styles')
  @once
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
    <style>
      #session-sales-table {
        table-layout: auto
      }

      #session-sales-table th,
      #session-sales-table td {
        vertical-align: middle;
        word-break: break-word
      }

      #session-sales-table tbody td:first-child {
        padding-left: .75rem
      }

      #session-sales-table thead th:first-child {
        padding-left: .75rem
      }

      #session-sales-table tbody td {
        padding-top: 1.25rem;
        padding-bottom: 1.25rem
      }

      .dataTables_wrapper .row>div {
        padding: .5rem .75rem
      }

      .dataTables_filter input {
        border-radius: .25rem;
        margin-right: 1rem
      }

      .dataTables_length {
        margin-left: 1rem
      }

      .dataTables_info {
        margin-left: 1rem
      }

      #session-sales-table_paginate {
        margin-right: 1rem;
        margin-bottom: 1rem
      }
    </style>
  @endonce
@endpush

@push('after_scripts')
  @once
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdn.datatables.net/plug-ins/1.12.1/sorting/datetime-moment.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
  @endonce
  <script>
    $(function () {
      $.fn.dataTable.moment('DD/MM/YYYY HH:mm');
      $('#session-sales-table').DataTable({
        pageLength: 10,
        responsive: true,
        autoWidth: false,
        ordering: true,
        order: [[6, 'desc']],
        processing: true,
        deferRender: true,
        dom: '<"row mb-2"<"col-sm-6"l><"col-sm-6"f>>rt<"row mt-2"<"col-sm-6"i><"col-sm-6"p>>',
        language: { url: '//cdn.datatables.net/plug-ins/1.12.1/i18n/{{ app()->getLocale() }}.json' }
      });

      $('#event-session-table').DataTable({
        pageLength: 10,
        responsive: true,
        autoWidth: false,
        ordering: true,
        order: [[4, 'desc']],
        processing: true,
        deferRender: true,
        dom: '<"row mb-2"<"col-sm-6"l><"col-sm-6"f>>rt<"row mt-2"<"col-sm-6"i><"col-sm-6"p>>',
        language: { url: '//cdn.datatables.net/plug-ins/1.12.1/i18n/{{ app()->getLocale() }}.json' }
      });
    });
  </script>
@endpush