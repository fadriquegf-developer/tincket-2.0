@php
    // obtener la cadena completa de correos
    $value  = data_get($entry, $column['name']);
    $emails = collect(explode(',', $value))
                ->map(fn ($e) => trim($e))
                ->filter();
@endphp

@if($emails->isNotEmpty())
    <details style="max-width:100%">
        <summary style="cursor:pointer">
            {{ __('backend.mail.show_emails') }} ({{ $emails->count() }})
        </summary>
        <div class="mt-2" style="white-space:normal; word-break:break-all;">
            {!! $emails->implode('<br>') !!}
        </div>
    </details>
@else
    –
@endif
