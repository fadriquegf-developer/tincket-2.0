{{-- grouped_checklist_dependency.blade.php --}}
@php
    // Configurar el wrapper y atributos por defecto
    $field['wrapper'] = $field['wrapper'] ?? $field['wrapperAttributes'] ?? [];
    $field['wrapper']['class'] = $field['wrapper']['class'] ?? 'form-group col-sm-12';
    $field['wrapper']['class'] .= ' checklist_dependency';
    $field['wrapper']['data-entity'] = $field['wrapper']['data-entity'] ?? $field['field_unique_name'];
    $field['wrapper']['data-init-function'] = $field['wrapper']['init-function'] ?? 'bpFieldInitChecklistDependencyElement';
@endphp

@include('crud::fields.inc.wrapper_start')

<label>{!! $field['label'] !!}</label>
<?php
    // Modelo de la entidad
    $entity_model = $crud->getModel();

    // Variables cortas para los subcampos de la dependencia
    $primary_dependency = $field['subfields']['primary'];
    $secondary_dependency = $field['subfields']['secondary'];

    // Obtener la relación primaria con su entidad secundaria
    $dependencies = $primary_dependency['model']::with($primary_dependency['entity_secondary']);
    if(isset($primary_dependency['options']) && $primary_dependency['options'] instanceof \Closure){
        $dependencies = $primary_dependency['options']($dependencies);
    }
    if ($dependencies instanceof \Illuminate\Contracts\Database\Query\Builder) {
        $dependencies = $dependencies->get();
    }

    // Convertir la relación en una matriz simple: [ primary_id => [secondary_id, ...] ]
    $dependencyArray = [];
    foreach ($dependencies as $primary) {
        $dependencyArray[$primary->id] = [];
        foreach ($primary->{$primary_dependency['entity_secondary']} as $secondary) {
            $dependencyArray[$primary->id][] = $secondary->id;
        }
    }

    $old_primary_dependency = old_empty_or_null($primary_dependency['name'], false) ?? false;
    $old_secondary_dependency = old_empty_or_null($secondary_dependency['name'], false) ?? false;

    // Para el formulario de actualización, obtener estado inicial
    if (isset($id) && $id) {
        $entity_dependencies = $entity_model->with($primary_dependency['entity'])
            ->with($primary_dependency['entity'] . '.' . $primary_dependency['entity_secondary'])
            ->find($id);
        $primary_array = $entity_dependencies->{$primary_dependency['entity']}->toArray();
        $secondary_ids = [];
        if ($old_primary_dependency) {
            foreach ($old_primary_dependency as $primary_item) {
                foreach ($dependencyArray[$primary_item] as $second_item) {
                    $secondary_ids[$second_item] = $second_item;
                }
            }
        } else {
            foreach ($primary_array as $primary_item) {
                foreach ($primary_item[$secondary_dependency['entity']] as $second_item) {
                    $secondary_ids[$second_item['id']] = $second_item['id'];
                }
            }
        }
    }

    // Codificar en JSON la matriz de dependencia
    $dependencyJson = json_encode($dependencyArray);

    // Obtener opciones para el subcampo primario
    $primaryDependencyOptionQuery = $primary_dependency['model']::query();
    if(isset($primary_dependency['options']) && $primary_dependency['options'] instanceof \Closure){
        $primaryDependencyOptionQuery = $primary_dependency['options']($primaryDependencyOptionQuery);
    }
    $primaryDependencyOptions = $primaryDependencyOptionQuery->get();

    // Obtener opciones para el subcampo secondary
    $secondaryDependencyOptionQuery = $secondary_dependency['model']::query();
    if(isset($secondary_dependency['options']) && $secondary_dependency['options'] instanceof \Closure){
        $secondaryDependencyOptionQuery = $secondary_dependency['options']($secondaryDependencyOptionQuery);
    }
    $secondaryDependencyOptions = $secondaryDependencyOptionQuery->get();

    // AGREGAR: Agrupar las opciones del secondary por su prefijo (antes del punto)
    $groupedSecondaryOptions = [];
    foreach ($secondaryDependencyOptions as $permission) {
        // Se asume que el atributo contiene un valor tipo "roles.index"
        $name = $permission->{$secondary_dependency['attribute']};
        $parts = explode('.', $name);
        $groupKey = $parts[0] ?? 'otros';
        // Traducir el grupo. Si no se encuentra la traducción, se usa ucfirst($groupKey)
        $translatedGroup = trans('permissionmanager.' . strtolower($groupKey));
        if ($translatedGroup === 'permissionmanager.' . strtolower($groupKey)) {
            $translatedGroup = ucfirst($groupKey);
        }
        $groupedSecondaryOptions[$translatedGroup][] = $permission;
    }
    ksort($groupedSecondaryOptions);
?>

<div class="container">
  <!-- Sección primaria -->
  <div class="row">
      <div class="col-sm-12">
          <label>{!! $primary_dependency['label'] !!}</label>
          @include('crud::fields.inc.translatable_icon', ['field' => $primary_dependency])
      </div>
  </div>

  <div class="row">
      <div class="hidden_fields_primary" data-name="{{ $primary_dependency['name'] }}">
          <input type="hidden" bp-field-name="{{ $primary_dependency['name'] }}" name="{{ $primary_dependency['name'] }}" value="" />
          @if(isset($field['value']))
              @if($old_primary_dependency)
                  @foreach($old_primary_dependency as $item)
                      <input type="hidden" class="primary_hidden" name="{{ $primary_dependency['name'] }}[]" value="{{ $item }}">
                  @endforeach
              @else
                  @foreach( $field['value'][0]->pluck('id', 'id')->toArray() as $item)
                      <input type="hidden" class="primary_hidden" name="{{ $primary_dependency['name'] }}[]" value="{{ $item }}">
                  @endforeach
              @endif
          @endif
      </div>

      @foreach ($primaryDependencyOptions as $connected_entity_entry)
          <div class="col-sm-{{ isset($primary_dependency['number_columns']) ? intval(12 / $primary_dependency['number_columns']) : '4' }}">
              <div class="checkbox">
                  <label class="font-weight-normal">
                      <input type="checkbox"
                          data-id="{{ $connected_entity_entry->id }}"
                          class="primary_list"
                          @foreach ($primary_dependency as $attribute => $value)
                              @if (is_string($attribute) && $attribute != 'value')
                                  @if ($attribute=='name')
                                      {{ $attribute }}="{{ $value }}_show[]"
                                  @elseif(! $value instanceof \Closure)
                                      {{ $attribute }}="{{ $value }}"
                                  @endif
                              @endif
                          @endforeach
                          value="{{ $connected_entity_entry->id }}"
                          @if( ( isset($field['value']) && is_array($field['value']) &&
                                  in_array($connected_entity_entry->id, $field['value'][0]->pluck('id', 'id')->toArray())
                              ) || ($old_primary_dependency && in_array($connected_entity_entry->id, $old_primary_dependency)) )
                              checked="checked"
                          @endif >
                      {{ $connected_entity_entry->{$primary_dependency['attribute']} }}
                  </label>
              </div>
          </div>
      @endforeach
  </div>

  <!-- Sección secondary agrupada -->
  <div class="row">
      <div class="col-sm-12">
          <label>{!! $secondary_dependency['label'] !!}</label>
          @include('crud::fields.inc.translatable_icon', ['field' => $secondary_dependency])
      </div>
  </div>

  <div class="row">
      <div class="hidden_fields_secondary" data-name="{{ $secondary_dependency['name'] }}">
          <input type="hidden" bp-field-name="{{ $secondary_dependency['name'] }}" name="{{ $secondary_dependency['name'] }}" value="" />
          @if(isset($field['value']))
              @if($old_secondary_dependency)
                  @foreach($old_secondary_dependency as $item)
                      <input type="hidden" class="secondary_hidden" name="{{ $secondary_dependency['name'] }}[]" value="{{ $item }}">
                  @endforeach
              @else
                  @foreach( $field['value'][1]->pluck('id', 'id')->toArray() as $item)
                      <input type="hidden" class="secondary_hidden" name="{{ $secondary_dependency['name'] }}[]" value="{{ $item }}">
                  @endforeach
              @endif
          @endif
      </div>

      @foreach ($groupedSecondaryOptions as $group => $permissions)
          <div class="row">
              <div class="col-sm-12">
                  <h4 class="my-3">{{ $group }}</h4>
              </div>
          </div>
          <div class="row">
              @foreach ($permissions as $permission)
                  <div class="col-sm-{{ isset($secondary_dependency['number_columns']) ? intval(12 / $secondary_dependency['number_columns']) : '4' }}">
                      <div class="checkbox">
                          <label class="font-weight-normal">
                              <input type="checkbox"
                                  class="secondary_list"
                                  data-id="{{ $permission->id }}"
                                  @foreach ($secondary_dependency as $attribute => $value)
                                      @if (is_string($attribute) && $attribute != 'value')
                                          @if ($attribute=='name')
                                              {{ $attribute }}="{{ $value }}_show[]"
                                          @elseif(! $value instanceof \Closure)
                                              {{ $attribute }}="{{ $value }}"
                                          @endif
                                      @endif
                                  @endforeach
                                  value="{{ $permission->id }}"
                                  @if( ( isset($field['value']) && is_array($field['value']) && ( 
                                          in_array($permission->id, $field['value'][1]->pluck('id', 'id')->toArray()) ||
                                          isset( $secondary_ids[$permission->id])
                                      ) ) || ($old_secondary_dependency && in_array($permission->id, $old_secondary_dependency)) )
                                      checked="checked"
                                      @if(isset( $secondary_ids[$permission->id]))
                                          disabled="disabled"
                                      @endif
                                  @endif >
                              {{ trans('permissionmanager.' . $permission->{$secondary_dependency['attribute']}) }}
                          </label>
                      </div>
                  </div>
              @endforeach
          </div>
      @endforeach
  </div>
</div>

@if (isset($field['hint']))
    <p class="help-block">{!! $field['hint'] !!}</p>
@endif

@include('crud::fields.inc.wrapper_end')

@push('crud_fields_scripts')
    <script>
        var {{ $field['field_unique_name'] }} = {!! $dependencyJson !!};
    </script>

    @bassetBlock('backpack/crud/fields/checklist-dependency-field.js')
        <script>
            function bpFieldInitChecklistDependencyElement(element) {
                var unique_name = element.data('entity');
                var dependencyJson = window[unique_name];
                var thisField = element;
                var handleCheckInput = function(el, field, dependencyJson) {
                    let idCurrent = el.data('id');
                    // Añadir campo oculto para este valor
                    let nameInput = field.find('.hidden_fields_primary').data('name');
                    if (field.find('input.primary_hidden[value="'+idCurrent+'"]').length === 0) {
                        let inputToAdd = $('<input type="hidden" class="primary_hidden" name="'+nameInput+'[]" value="'+idCurrent+'">');
                        field.find('.hidden_fields_primary').append(inputToAdd);
                        field.find('.hidden_fields_primary').find('input.primary_hidden[value="'+idCurrent+'"]').trigger('change');
                    }
                    $.each(dependencyJson[idCurrent], function(key, value) {
                        field.find('input.secondary_list[value="'+value+'"]').prop("checked", true);
                        field.find('input.secondary_list[value="'+value+'"]').prop("disabled", true);
                        field.find('input.secondary_list[value="'+value+'"]').attr('forced-select', 'true');
                        var hidden = field.find('input.secondary_hidden[value="'+value+'"]');
                        if(hidden) hidden.remove();
                    });
                };

                thisField.find('div.hidden_fields_primary').children('input').first().on('CrudField:disable', function(e) {
                    let input = $(e.target);
                    input.parent().parent().find('input[type=checkbox]').attr('disabled', 'disabled');
                    input.siblings('input').attr('disabled', 'disabled');
                });
                thisField.find('div.hidden_fields_primary').children('input').first().on('CrudField:enable', function(e) {
                    let input = $(e.target);
                    input.parent().parent().find('input[type=checkbox]').not('[forced-select]').removeAttr('disabled');
                    input.siblings('input').removeAttr('disabled');
                });
                thisField.find('div.hidden_fields_secondary').children('input').first().on('CrudField:disable', function(e) {
                    let input = $(e.target);
                    input.parent().parent().find('input[type=checkbox]').attr('disabled', 'disabled');
                    input.siblings('input').attr('disabled', 'disabled');
                });
                thisField.find('div.hidden_fields_secondary').children('input').first().on('CrudField:enable', function(e) {
                    let input = $(e.target);
                    input.parent().parent().find('input[type=checkbox]').not('[forced-select]').removeAttr('disabled');
                    input.siblings('input').removeAttr('disabled');
                });

                thisField.find('.primary_list').each(function() {
                    var checkbox = $(this);
                    if(checkbox.is(':checked')){
                       handleCheckInput(checkbox, thisField, dependencyJson);
                    }
                    checkbox.change(function(){
                      if(checkbox.is(':checked')){
                        handleCheckInput(checkbox, thisField, dependencyJson);
                      } else {
                        let idCurrent = checkbox.data('id');
                        thisField.find('input.primary_hidden[value="'+idCurrent+'"]').remove();
                        var secondary = dependencyJson[idCurrent];
                        var selected = [];
                        thisField.find('input.primary_hidden').each(function(index, input){
                          selected.push($(this).val());
                        });
                        $.each(secondary, function(index, secondaryItem){
                          var ok = 1;
                          $.each(selected, function(index2, selectedItem){
                            if( dependencyJson[selectedItem].indexOf(secondaryItem) != -1 ){
                              ok = 0;
                            }
                          });
                          if(ok){
                            thisField.find('input.secondary_list[value="'+secondaryItem+'"]').prop('checked', false);
                            thisField.find('input.secondary_list[value="'+secondaryItem+'"]').prop('disabled', false);
                            thisField.find('input.secondary_list[value="'+secondaryItem+'"]').removeAttr('forced-select');
                          }
                        });
                      }
                    });
                });

                thisField.find('.secondary_list').click(function(){
                    var idCurrent = $(this).data('id');
                    if($(this).is(':checked')){
                      var nameInput = thisField.find('.hidden_fields_secondary').data('name');
                      var inputToAdd = $('<input type="hidden" class="secondary_hidden" name="'+nameInput+'[]" value="'+idCurrent+'">');
                      thisField.find('.hidden_fields_secondary').append(inputToAdd);
                    } else {
                      thisField.find('input.secondary_hidden[value="'+idCurrent+'"]').remove();
                    }
                });
            }
        </script>
    @endBassetBlock
@endpush
