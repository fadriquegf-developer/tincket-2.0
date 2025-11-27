@php

    $allSessions = collect($sessions ?? [])
        ->map(fn($s) => [
            'id' => $s->id,
            'name' => sprintf(
                '%s - %s (%s %s)',
                $s->event->name,
                $s->name,
                $s->starts_on->format('d/m/Y'),
                $s->starts_on->format('H:i')
            ),
        ])
        ->values();


    $selected = old('sessions');

    if (!$selected && isset($crud->entry)) {
        $selected = $crud->entry->sessions()
            ->orderBy('starts_on')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'name' => sprintf(
                    '%s - %s (%s %s)',
                    $s->event->name,
                    $s->name,
                    $s->starts_on->format('d/m/Y'),
                    $s->starts_on->format('H:i')
                ),
            ])
            ->values();
    }

    if (is_string($selected)) {
        $selected = collect(json_decode($selected, true));
    }

    $selected = collect($selected)->filter()->values();

    $props = [
        'sessions' => $allSessions,
        'initial' => $selected,
        'name' => $field['name'],
        'translations' => [
            'available_sessions' => __('backend.pack.available_sessions'),
            'no_available' => __('backend.pack.no_available'),
            'add_to_pack' => __('backend.pack.add_to_pack'),
            'add_all' => __('backend.pack.add_all'),
            'drag_or_buttons' => __('backend.pack.drag_or_buttons'),
            'selected_sessions' => __('backend.pack.selected_sessions'),
            'no_selected' => __('backend.pack.no_selected'),
            'remove_from_pack' => __('backend.pack.remove_from_pack'),
            'remove_all' => __('backend.pack.remove_all'),
        ],
    ];
@endphp


<div id="sessions-field-{{ $field['name'] }}" data-props='@json($props)'></div>

<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="{{ asset('js/vue/sessions-field.js') }}?v={{ filemtime(public_path('js/vue/sessions-field.js')) }}"></script>