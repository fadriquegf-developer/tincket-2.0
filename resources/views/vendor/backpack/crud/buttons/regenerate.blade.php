<a href="#"
   class="btn btn-sm btn-link"
   onclick="event.preventDefault();
            if (confirm('{{ __('backend.session.confirm_regenerate')}}')) {
              window.location.href='{{ url($crud->route.'/'.$entry->getKey().'/regenerate') }}';
            }">
  <i class="la la-sync mx-1"></i> {{ __('backend.session.regenerate') }}
</a>
