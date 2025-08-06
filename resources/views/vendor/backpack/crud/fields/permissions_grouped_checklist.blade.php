@php
    // Determinar el atributo clave y otras opciones por defecto.
    $keyAttribute = (new ($field['model'])())->getKeyName();
    $field['attribute'] = $field['attribute'] ?? (new ($field['model'])())->identifiableAttribute();
    $field['number_of_columns'] = $field['number_of_columns'] ?? 3;
    $field['show_select_all'] = $field['show_select_all'] ?? false;

    // Obtener las opciones (si no se pasaron en el field, se consultan todas)
    if (!isset($field['options'])) {
        $allOptions = $field['model']::all()->pluck($field['attribute'], $keyAttribute)->toArray();
    } else {
        $allOptions = call_user_func($field['options'], $field['model']::query());
        if (is_a($allOptions, \Illuminate\Contracts\Database\Query\Builder::class, true)) {
            $allOptions = $allOptions->pluck($field['attribute'], $keyAttribute)->toArray();
        }
    }

    // Agrupar las opciones según el prefijo (antes del punto)
    $groupedOptions = [];
    foreach ($allOptions as $key => $option) {
        // Se asume que los permisos siguen el formato: "grupo.accion", por ejemplo "roles.index"
        $parts = explode('.', $option);
        $groupKey = $parts[0] ?? 'otro';
        // Aplicar traducción: si existe una traducción en tus lang para "permissionmanager.roles", se usará.
        $translatedGroup = trans('permissionmanager.' . strtolower($groupKey));
        // Si no hay traducción, se muestra el grupo tal cual.
        if ($translatedGroup === 'permissionmanager.' . strtolower($groupKey)) {
            $translatedGroup = ucfirst($groupKey);
        }
        $groupedOptions[$translatedGroup][$key] = $option;
    }
    ksort($groupedOptions);

    // Obtener el valor actual (las IDs de los permisos seleccionados)
    $field['value'] = old_empty_or_null($field['name'], []) ?? ($field['value'] ?? ($field['default'] ?? []));
    if (!empty($field['value'])) {
        if (is_a($field['value'], \Illuminate\Support\Collection::class)) {
            $field['value'] = $field['value']->pluck($keyAttribute)->toArray();
        } elseif (is_string($field['value'])) {
            $field['value'] = json_decode($field['value'], true);
        }
    }

    // Inicialización de la función JS del field
    $field['wrapper']['data-init-function'] = $field['wrapper']['data-init-function'] ?? 'bpFieldInitGroupedChecklist';
@endphp

@include('crud::fields.inc.wrapper_start')
<label>{!! $field['label'] !!}
    @if ($field['show_select_all'])
        <span class="fs-6 small checklist-select-all-inputs">
            <a href="javascript:void(0)" class="select-all-inputs">{{ trans('backpack::crud.select_all') }}</a>
            <a href="javascript:void(0)" class="unselect-all-inputs d-none">{{ trans('backpack::crud.unselect_all') }}</a>
        </span>
    @endif
</label>
@include('crud::fields.inc.translatable_icon')

<input type="hidden" data-show-select-all="{{ var_export($field['show_select_all']) }}"
    value='@json($field['value'])' name="{{ $field['name'] }}">

<div class="permissions-grouped-checklist">
    @foreach ($groupedOptions as $group => $options)
        <div class="group-container">
            <h4 class="my-2">
                {{ $group }}
            </h4>
            <div class="row checklist-options-container">
                <div class="col-12">
                    <div class="checkbox">
                        <label class="font-weight-normal">
                            <input type="checkbox" class="group-select-all">
                            <i>Marcar todos</i>
                        </label>
                    </div>
                </div>
                @foreach ($options as $key => $option)
                    <div class="col-sm-{{ intval(12 / $field['number_of_columns']) }}">
                        <div class="checkbox">
                            <label class="font-weight-normal">
                                <input type="checkbox" class="permission-checkbox" value="{{ $key }}"
                                    {{ in_array($key, $field['value']) ? 'checked' : '' }}>
                                {{ trans('permissionmanager.' . $option) }}
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>


@if (isset($field['hint']))
    <p class="help-block">{!! $field['hint'] !!}</p>
@endif
@include('crud::fields.inc.wrapper_end')


@push('crud_fields_scripts')
<script>
    function bpFieldInitGroupedChecklist(element) {
        let hiddenInput = element.find('input[type=hidden]');
        let selectedOptions = JSON.parse(hiddenInput.val() || '[]');
        let permissionCheckboxes = element.find('.permission-checkbox'); // Sólo checkboxes de permisos

        // Establece el estado inicial de cada checkbox de permiso
        permissionCheckboxes.each(function() {
            const id = $(this).val();
            if (selectedOptions.map(String).includes(id)) {
                $(this).prop('checked', true);
            } else {
                $(this).prop('checked', false);
            }
        });

        // Al hacer clic en cualquier checkbox de permiso, se actualiza el hidden input
        permissionCheckboxes.on('click', function() {
            updateHiddenInput();

            // Opcional: actualizar el checkbox de grupo si todos los permisos del grupo están marcados
            let groupContainer = $(this).closest('.group-container');
            let groupCheckbox = groupContainer.find('.group-select-all');
            let total = groupContainer.find('.permission-checkbox').length;
            let checked = groupContainer.find('.permission-checkbox:checked').length;
            groupCheckbox.prop('checked', (total === checked));
        });

        // Lógica para el checkbox global "select all" (si está habilitado)
        if (hiddenInput.data('show-select-all')) {
            let selectAllAnchor = element.find('a.select-all-inputs');
            let unselectAllAnchor = element.find('a.unselect-all-inputs');
            const toggleSelectAnchors = function() {
                if (permissionCheckboxes.length === permissionCheckboxes.filter(':checked').length) {
                    selectAllAnchor.addClass('d-none');
                    unselectAllAnchor.removeClass('d-none');
                } else {
                    selectAllAnchor.removeClass('d-none');
                    unselectAllAnchor.addClass('d-none');
                }
            };

            selectAllAnchor.on('click', function() {
                permissionCheckboxes.prop('checked', true);
                updateHiddenInput();
                toggleSelectAnchors();
                
                // Además, marcar todos los checkbox de grupo
                element.find('.group-select-all').prop('checked', true);
            });
            unselectAllAnchor.on('click', function() {
                permissionCheckboxes.prop('checked', false);
                updateHiddenInput();
                toggleSelectAnchors();
                
                // Desmarcar los checkbox de grupo
                element.find('.group-select-all').prop('checked', false);
            });

            toggleSelectAnchors();
        }

        // Lógica para el checkbox de selección de grupo
        element.find('.group-select-all').on('click', function() {
            let groupCheckbox = $(this);
            let groupContainer = groupCheckbox.closest('.group-container');
            let groupPermissionCheckboxes = groupContainer.find('.permission-checkbox');

            // Marcar o desmarcar todos los permisos del grupo
            groupPermissionCheckboxes.prop('checked', groupCheckbox.prop('checked'));

            updateHiddenInput();
        });

        // Función para actualizar el hidden input con las opciones de permiso seleccionadas
        function updateHiddenInput() {
            let newValue = [];
            permissionCheckboxes.each(function() {
                if ($(this).is(':checked')) {
                    newValue.push($(this).val());
                }
            });
            hiddenInput.val(JSON.stringify(newValue)).trigger('change');
        }

        // Permitir deshabilitar/habilitar el field (opcional)
        hiddenInput.on('CrudField:disable', function() {
            permissionCheckboxes.prop('disabled', true);
            element.find('.group-select-all').prop('disabled', true);
        });
        hiddenInput.on('CrudField:enable', function() {
            permissionCheckboxes.prop('disabled', false);
            element.find('.group-select-all').prop('disabled', false);
        });
    }
</script>
@endpush
