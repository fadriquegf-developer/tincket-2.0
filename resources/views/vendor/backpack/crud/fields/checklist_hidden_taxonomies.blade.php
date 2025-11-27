<!-- resources/views/vendor/backpack/crud/fields/checklist_hidden_taxonomies.blade.php -->
<div @include('crud::fields.inc.wrapper_start')>
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    <?php
    // IMPORTANTE: Filtrar por brand
    $brand_id = $field['brand_id'] ?? get_current_brand_id();
    
    // Obtener solo las taxonomías de esta brand
    $taxonomies = $field['model']
        ::where('brand_id', $brand_id)
        ->where('active', true) // Solo activas
        ->orderBy('depth', 'asc')
        ->orderBy('rgt', 'asc')
        ->get();
    
    // Obtener valores actuales
    $currentValues = old($field['name']) ?? ($field['value'] ?? []);
    if (!is_array($currentValues)) {
        $currentValues = json_decode($currentValues, true) ?? [];
    }
    ?>

    @if ($taxonomies->isEmpty())
        <div class="alert alert-info">
            <i class="la la-info-circle"></i>
            {{ __('backend.brand_settings.no_categories_available') }}
        </div>
    @else
        <div class="row">
            @foreach ($taxonomies as $taxonomy)
                <?php
                $isChecked = in_array($taxonomy->getKey(), $currentValues);
                // Construir el path completo de la taxonomía
                $path = '';
                if ($taxonomy->depth > 0) {
                    $ancestors = $taxonomies
                        ->filter(function ($item) use ($taxonomy) {
                            return $item->lft < $taxonomy->lft && $item->rgt > $taxonomy->rgt;
                        })
                        ->sortBy('depth');
                
                    if ($ancestors->count()) {
                        $path = $ancestors->pluck('name')->implode(' > ') . ' > ';
                    }
                }
                ?>

                <div class="col-sm-6 col-md-4">
                    <div class="checkbox">
                        <label class="font-weight-normal">
                            <input type="checkbox" name="{{ $field['name'] }}[]" value="{{ $taxonomy->getKey() }}"
                                @if ($isChecked) checked="checked" @endif>

                            @if ($taxonomy->depth > 0)
                                <span class="text-muted small">{{ $path }}</span>
                            @endif
                            <span @if ($taxonomy->depth == 0) class="font-weight-bold" @endif>
                                {{ $taxonomy->{$field['attribute']} }}
                            </span>

                            @if ($taxonomy->children->count() > 0)
                                <span class="badge badge-secondary badge-sm">
                                    {{ $taxonomy->children->count() }}
                                </span>
                            @endif
                        </label>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Controles adicionales --}}
        <div class="mt-3">
            <button type="button" class="btn btn-sm btn-default" onclick="selectAllCategories(this)">
                <i class="la la-check-square"></i> {{ __('backend.brand_settings.select_all') }}
            </button>
            <button type="button" class="btn btn-sm btn-default" onclick="deselectAllCategories(this)">
                <i class="la la-square-o"></i> {{ __('backend.brand_settings.deselect_all') }}
            </button>
        </div>
    @endif

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
</div>

@push('crud_fields_scripts')
    <script>
        function selectAllCategories(button) {
            $(button).closest('.form-group').find('input[type="checkbox"]').prop('checked', true);
        }

        function deselectAllCategories(button) {
            $(button).closest('.form-group').find('input[type="checkbox"]').prop('checked', false);
        }
    </script>
@endpush
