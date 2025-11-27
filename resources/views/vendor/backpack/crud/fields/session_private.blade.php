@php
    $value = old($field['name']) ?? $field['value'] ?? $field['default'] ?? false;
    $hintUrl = isset($entry) && $entry->private ? $entry->currentBrandFrontend() : null;
@endphp

<div class="form-group col-sm-12 {{ $field['wrapperAttributes']['class'] ?? '' }}" 
    @if(isset($field['wrapperAttributes']))
        @foreach($field['wrapperAttributes'] as $attribute => $attrValue)
            @if($attribute != 'class')
                {{ $attribute }}="{{ $attrValue }}"
            @endif
        @endforeach
    @endif
>
    <input type="hidden" name="{{ $field['name'] }}" value="0">
    <div class="form-check form-switch d-flex align-items-center">
        <input 
            class="form-check-input session-private-checkbox" 
            type="checkbox" 
            role="switch"
            id="{{ $field['name'] }}"
            name="{{ $field['name'] }}"
            value="1"
            style="width: 2.5rem; height: 1.25rem; margin-right: 0.5rem;"
            @if($value) checked @endif
            @if(isset($field['attributes']))
                @foreach($field['attributes'] as $attribute => $attrValue)
                    {{ $attribute }}="{{ $attrValue }}"
                @endforeach
            @endif
        >
        <label class="form-check-label mb-0" for="{{ $field['name'] }}">
            {!! $field['label'] !!}
        </label>
    </div>

    {{-- Mostrar la URL si la sesión es privada --}}
    @if($hintUrl)
        <div class="alert alert-info session-private-url mt-3" id="session-private-url" style="{{ $value ? '' : 'display: none;' }}">
            <strong>{{ __('backend.session.private_url') }}:</strong><br>
            <a href="{{ $hintUrl }}" target="_blank" class="alert-link">{{ $hintUrl }}</a>
        </div>
    @endif

    {{-- Hint --}}
    @if(isset($field['hint']))
        <p class="form-text text-muted">{!! $field['hint'] !!}</p>
    @endif
</div>

{{-- FIELD JAVASCRIPT - Cargado solo una vez --}}
@if ($crud->fieldTypeNotLoaded($field))
    @php
        $crud->markFieldTypeAsLoaded($field);
    @endphp

    @push('crud_fields_scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkbox = document.querySelector('.session-private-checkbox');
            const urlDiv = document.getElementById('session-private-url');
            
            if (checkbox && urlDiv) {
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        urlDiv.style.display = 'block';
                    } else {
                        urlDiv.style.display = 'none';
                    }
                });
            }
        });
    </script>
    @endpush
@endif