{{-- resources/views/vendor/backpack/crud/event/sessions_table.blade.php --}}
<div class="card shadow-xs border-xs">
  <div class="card-header border-xs">
    <h3 class="mb-0">{{ __('backend.events.sessions') }}</h3>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table id="event-session-table" class="table table-sm table-striped table-hover mb-0 align-middle">
        <thead class="border-secondary">
          <tr>
            <th>#</th>
            <th>{{ __('backend.events.start_on') }}</th>
            <th>{{ __('backend.session.maximumplaces') }}</th>
            <th>{{ __('backend.session.occupiedplaces') }}</th>
            <th>{{ __('backend.events.updated_at') }}</th>
            <th>{{ __('backend.events.deleted_at') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($entry->sessions_order as $session)
            <tr>
              <td>{{ $session->id }}</td>
              <td>{{ $session->starts_on ? \Carbon\Carbon::parse($session->starts_on)->format('d/m/Y H:i') : '-' }}</td>
              <td>{{ $session->max_places }}</td>
              <td>{{ $session->inscriptions()->withoutGlobalScope(\App\Scopes\BrandScope::class)->whereHas('cart', function ($q) {
                                    $q->whereNotNull('confirmation_code');
                                })->count() }}
              </td>
              <td>{{ $session->updated_at }}</td>
              <td>{{ $session->deleted_at }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

@push('after_styles')
@once
<link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
<style>
  #event-session-table{table-layout:auto}
  #event-session-table th,#event-session-table td{vertical-align:middle;word-break:break-word}
  #event-session-table tbody td:first-child{padding-left:.75rem}
  #event-session-table thead th:first-child{padding-left:.75rem}
  #event-session-table tbody td{padding-top:1.25rem;padding-bottom:1.25rem}
</style>
@endonce
@endpush
