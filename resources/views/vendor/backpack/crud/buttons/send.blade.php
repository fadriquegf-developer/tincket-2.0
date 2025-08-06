@if ($crud->hasAccess('send') && !$entry->is_sent)
    <a href="{{ url($crud->route . '/' . $entry->getKey() . '/send') }}" class="btn btn-sm btn-link" data-button-type="send"><i
            class="la la-envelope-o"></i> {{ __('backend.mail.send') }}</a>
@endif