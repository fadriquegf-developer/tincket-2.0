@php
    $props = [
        'locale'  => app()->getLocale(),
        'initial' => old('embedded_entities')
                     ? json_decode(old('embedded_entities'), true)
                     : ($field['value'] ?? []),
    ];
@endphp
<label for="">{{ __('backend.mail.extra_content') }}</label>
<div id="embedded-entities-field-embedded_entities"
     data-props='@json($props)'></div>

<script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="{{ asset('js/vue/embedded-entities-field.js') }}?v={{ filemtime(public_path('js/vue/embedded-entities-field.js')) }}"></script>
