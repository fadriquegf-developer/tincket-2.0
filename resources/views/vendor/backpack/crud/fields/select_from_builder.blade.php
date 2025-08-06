{{-- select_from_builder --}}
@include('crud::fields.inc.wrapper_start')

<label>{!! $field['label'] !!}</label>
@include('crud::fields.inc.translatable_icon')

@php
    $entity_model = $crud->model;
    $options = [];

    if (isset($field['builder']) && is_callable($field['builder'])) {
        $options = call_user_func($field['builder'])->get();
    }
@endphp

<select
    name="{{ $field['name'] }}"
    @include('crud::fields.inc.attributes')
>
    @if ($entity_model::isColumnNullable($field['name']))
        <option value="">-</option>
    @endif

    @foreach ($options as $entry)
        <option value="{{ $entry->getKey() }}"
            @if (old($field['name'], $field['value'] ?? '') == $entry->getKey()) selected @endif>
            {{ $entry->{$field['attribute']} }}
        </option>
    @endforeach
</select>

@if (isset($field['hint']))
    <p class="help-block">{!! $field['hint'] !!}</p>
@endif

@include('crud::fields.inc.wrapper_end')
