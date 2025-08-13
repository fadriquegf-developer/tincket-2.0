@php
    $t = Lang::get('rules');
    $propsRules = [
        'initial' => old($field['name'], $field['value'] ?? []),
        'name' => $field['name'],
        't' => $t,
    ];
@endphp

<label class="fw-bold">{{ __('backend.pack.rules') }}</label>

<div id="rules-field-{{ $field['name'] }}" data-props='@json($propsRules, JSON_HEX_APOS)'></div>

{{-- Mensaje de error dinámico --}}
@error($field['name'])
    <div class="invalid-feedback d-block">{{ $message }}</div>
@enderror

@push('after_scripts')
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    <script src="{{ asset('js/vue/rules-field.js') }}?v={{ filemtime(public_path('js/vue/rules-field.js')) }}"></script>
@endpush
