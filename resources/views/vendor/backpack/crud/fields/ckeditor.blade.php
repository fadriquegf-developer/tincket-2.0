{{-- resources/views/fields/ckeditor4.blade.php --}}
@php
  $id = 'ckeditor4-' . \Illuminate\Support\Str::random(6);
@endphp

@include('crud::fields.inc.wrapper_start')
<label>{!! $field['label'] !!}</label>
@include('crud::fields.inc.translatable_icon')

{{-- 2) Tu textarea --}}
<textarea id="{{ $id }}" name="{{ $field['name'] }}" @include('crud::fields.inc.attributes', ['default_class' => 'form-control ckeditor '])>{{ old($field['name'], $field['value'] ?? '') }}</textarea>
{{-- 3) Cierra el wrapper --}}
@include('crud::fields.inc.wrapper_end')

@push('after_styles')
  <style>
    html[data-theme="dark"],
    html[data-bs-theme="dark"] {
    /* solo el selector para scope */
    }

    html[data-theme="dark"] .cke_button_icon,
    html[data-bs-theme="dark"] .cke_button_icon {
    filter: brightness(0) invert(1) !important;
    }

    html[data-theme="dark"] .cke_button_label,
    html[data-bs-theme="dark"] .cke_button_label,
    html[data-theme="dark"] .cke_combo_text,
    html[data-bs-theme="dark"] .cke_combo_text {
    color: #fff !important;
    }

    html[data-theme="dark"] .cke_button,
    html[data-bs-theme="dark"] .cke_button {
    background: transparent !important;
    }


    html[data-theme="dark"] .cke_toolbar,
    html[data-bs-theme="dark"] .cke_toolbar {
    border-color: #555 !important;
    }
  </style>
@endpush


@push('after_scripts')
  <script src="https://cdn.ckeditor.com/4.16.2/full-all/ckeditor.js"></script>
  <script>
    // desactiva el aviso de versión
    CKEDITOR.config.versionCheck = false;
    CKEDITOR.config.height = 100;

  </script>
@endpush