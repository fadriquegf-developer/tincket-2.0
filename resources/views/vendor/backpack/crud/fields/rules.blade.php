@php
  $t = Lang::get('rules');   // ahora incluye las nuevas claves
  $propsRules = [
    'initial' => old($field['name'], $field['value'] ?? []),
    'name' => $field['name'],
    't' => $t,
  ];
@endphp

<label class="fw-bold">{{ __('backend.pack.discounts') }}</label>

<div id="rules-field-{{ $field['name'] }}" data-props='@json($propsRules, JSON_HEX_APOS)'></div>

@push('after_scripts')
  <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
  <script src="{{ asset('js/vue/rules-field.js') }}?v={{ filemtime(public_path('js/vue/rules-field.js')) }}"></script>
@endpush