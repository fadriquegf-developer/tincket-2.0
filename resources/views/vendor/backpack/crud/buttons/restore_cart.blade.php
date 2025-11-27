<a href="{{ route('cart.restore', ['id' => $entry->getKey()]) }}" class="btn btn-sm btn-link">
    <i class="la la-undo"></i> {{ app()->getLocale() === 'en' ? 'Restore' : 'Recuperar' }}
</a>
