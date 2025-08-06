@php
    $btnClass = $entry->liquidation ? 'btn-success' : 'btn-danger';
@endphp

<a href="#" class="dropdown-item {{ $btnClass }}"
    onclick="event.preventDefault(); 
            if (confirm('{{ $entry->liquidation ? __('backend.session.confirm_unliquidate') : __('backend.session.confirm_liquidate') }}')) {
              window.location.href='{{ url($crud->route . '/' . $entry->getKey() . '/liquidation') }}';
            }">
    <i class="la la-euro me-2 text-primary"></i> {{ __('backend.session.liquidation') }}
</a>
