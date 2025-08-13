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
    ];
@endphp


<div id="sessions-field-{{ $field['name'] }}" data-props='@json($props)'></div>

<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="{{ asset('js/vue/sessions-field.js') }}"></script>
