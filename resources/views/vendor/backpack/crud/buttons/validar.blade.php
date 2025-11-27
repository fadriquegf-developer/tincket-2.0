@php
    $url = backpack_url('validation/' . $entry->getKey() . '/show');
@endphp

<a href="{{ $url }}" class="btn btn-sm btn-link">
    <i class="la la-barcode me-1"></i> {{ app()->getLocale() === 'en' ? 'Validate' : 'Validar' }}
</a>
