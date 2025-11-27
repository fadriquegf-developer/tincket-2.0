@php
    $params = [];
    if (request()->filled('session'))    $params['session']    = request('session');        // select2
    if (request()->filled('from_to'))    $params['from_to']    = request('from_to');        // JSON {"from":"YYYY-MM-DD","to":"YYYY-MM-DD"}
@endphp

<a href="{{ route('client.to-mailing', $params) }}" class="btn btn-primary">
    <i class="la la-envelope pe-1"></i> {{ app()->getLocale() === 'en' ? 'Send newsletter' : 'Enviar newsletter' }}
</a>