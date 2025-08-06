<div @foreach($field['wrapperAttributes'] ?? [] as $attribute => $value) {{ $attribute }}="{{ $value }}" @endforeach>
    <label>{!! $field['label'] !!}</label>

    @if (!empty($field['translatable']))
        @include('crud::fields.inc.translatable_icon')
    @endif

    @php
        $entry = $crud->getCurrentEntry();
        $builder = $field['builder'] ?? fn() => \App\Models\FormField::query();
        $connected_entities = is_callable($builder) ? $builder() : collect();
        $selected_keys = old($field['name']) ?? ($entry ? $entry->{$field['entity']}->pluck('id')->toArray() : []);
    @endphp

    <div class="row">
        @foreach ($connected_entities as $connected_entity_entry)
            @php
                $key = $connected_entity_entry->getKey();
                $checked = in_array($key, $selected_keys ?? []);
            @endphp

            <div class="col-12">
                <div class="form-check mb-2">
                    <input type="checkbox"
                        name="{{ $field['name'] }}[]"
                        value="{{ $key }}"
                        id="checkbox_{{ $field['name'] }}_{{ $key }}"
                        class="form-check-input"
                        @if($checked) checked @endif
                    >
                    <label class="form-check-label" for="checkbox_{{ $field['name'] }}_{{ $key }}">
                        {{ $connected_entity_entry->{$field['attribute']} }}
                    </label>
                </div>
            </div>
        @endforeach
    </div>

    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
</div>
