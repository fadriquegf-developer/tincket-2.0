@php
    if (old('embedded_entities')) {
        $initial = json_decode(old('embedded_entities'), true);
    } else {
        // Si existe entry y tiene extra_content, decodificar
        $extra = $entry->extra_content ?? null;
        if (is_string($extra)) {
            $extra = json_decode($extra, true);
        }

        $initial = $extra['embedded_entities'] ?? [];
    }

    $props = [
        'locale' => app()->getLocale(),
        'initial' => $initial,
        'translations' => [
            'type' => __('backend.mail.type'),
            'entity' => __('backend.mail.entity'),
            'add' => __('backend.multi_session.btn_add'),
            'select' => __('backend.mail.select'),
            'event' => __('menu.event'),
            'page' => __('menu.page'),
            'post' => __('menu.post'),
        ],
    ];
@endphp
<label for="">{{ __('backend.mail.extra_content') }}</label>
<div id="embedded-entities-field-embedded_entities" data-props='@json($props)'></div>

<script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script
    src="{{ asset('js/vue/embedded-entities-field.js') }}?v={{ filemtime(public_path('js/vue/embedded-entities-field.js')) }}"></script>