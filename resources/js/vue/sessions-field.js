/* sessions-field.js - Bootstrap 5 version */
(function () {
  const { createApp, reactive, computed } = Vue;

  document.querySelectorAll('[id^="sessions-field-"]').forEach((el) => {
    const props = JSON.parse(el.dataset.props || '{}');

    const state = reactive({
      available: [...(props.sessions || [])],
      selected : [...(props.initial  || [])]
    });

    /* quita duplicados iniciales */
    state.available = state.available.filter(
      av => !state.selected.some(sel => String(sel.id) === String(av.id))
    );

    const jsonSelected = computed(() => JSON.stringify(state.selected));
    const syncHidden = () => {
      const input = el.querySelector('input[type="hidden"]');
      if (input) input.value = jsonSelected.value;
    };
    syncHidden();

    /* ---------- MUTATIONS ---------- */

    const addSession = (session) => {
      // Ocultar tooltip antes de mover el elemento
      hideTooltip();
      
      if (!state.selected.some(sel => String(sel.id) === String(session.id))) {
        state.selected.push(session);
      }
      const idx = state.available.findIndex(av => String(av.id) === String(session.id));
      if (idx > -1) state.available.splice(idx, 1);
      syncHidden();
    };

    const removeSession = (session) => {
      // Ocultar tooltip antes de mover el elemento
      hideTooltip();
      
      if (!state.available.some(av => String(av.id) === String(session.id))) {
        state.available.push(session);
      }
      const idx = state.selected.findIndex(sel => String(sel.id) === String(session.id));
      if (idx > -1) state.selected.splice(idx, 1);
      syncHidden();
    };
    
    const hideTooltip = () => {
      // Ocultar todos los tooltips activos
      if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltips = document.querySelectorAll('.tooltip.show');
        tooltips.forEach(tooltip => tooltip.remove());
      }
    };

    const addAll = () => {
      // Copiar array antes de iterar para evitar problemas con splice durante iteración
      const toAdd = [...state.available];
      toAdd.forEach(av => {
        if (!state.selected.some(sel => String(sel.id) === String(av.id))) {
          state.selected.push(av);
        }
      });
      // Eliminar todos los elementos añadidos de available
      state.available = [];
      syncHidden();
    };

    const removeAll = () => {
      // Copiar array antes de iterar para evitar problemas con splice durante iteración
      const toRemove = [...state.selected];
      toRemove.forEach(sel => {
        if (!state.available.some(av => String(av.id) === String(sel.id))) {
          state.available.push(sel);
        }
      });
      // Vaciar selected
      state.selected = [];
      syncHidden();
    };

    /* ---------- COMPONENT ---------- */
    const App = {
      template: `
      <div class="row g-3">
        <!-- Columna Disponibles -->
        <div class="col-md-5">
          <div class="card h-100">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center">
                <i class="la la-list-ul me-2"></i>
                <strong>{{ translations.available_sessions }}</strong>
                <span class="badge bg-secondary ms-2">{{ available.length }}</span>
              </div>
            </div>
            
            <div class="card-body">
              <!-- Lista de sesiones -->
              <div class="sessions-list" style="max-height: 400px; overflow-y: auto;">
                <div v-if="!available.length" class="text-center text-muted py-3">
                  <i class="la la-inbox la-2x d-block mb-2"></i>
                  {{ translations.no_available }}
                </div>

                <div v-for="s in available" 
                    :key="'av-' + s.id" 
                    class="session-item d-flex align-items-center p-2 mb-1 border rounded hover-bg">
                  <div class="flex-grow-1 text-truncate" :title="s.name">
                    <i class="la la-calendar-check-o text-muted me-1"></i>
                    {{ s.name }}
                  </div>
                  <button 
                    class="btn btn-sm btn-outline-primary ms-2"
                    type="button" 
                    @click="addSession(s)"
                    @mouseenter="showTooltip"
                    @mouseleave="hideTooltip"
                    data-bs-toggle="tooltip"
                    data-bs-placement="left"
                    :title="translations.add_to_pack"
                  >
                    <i class="la la-plus"></i>
                  </button>
                </div>
              </div>

              <!-- Botón añadir todos -->
              <div v-if="available.length > 1" class="mt-3 d-grid">
                <button 
                  class="btn btn-success btn-sm"
                  type="button" 
                  @click="addAll"
                >
                  <i class="la la-angle-double-right me-1"></i>
                  {{ translations.add_all }} ({{ available.length }})
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Columna Central con flechas -->
        <div class="col-md-2 d-flex align-items-center justify-content-center">
          <div class="text-center">
            <div class="mb-3">
              <i class="la la-exchange la-2x text-muted"></i>
            </div>
            <small class="text-muted" v-html="translations.drag_or_buttons"></small>
          </div>
        </div>

        <!-- Columna Seleccionadas -->
        <div class="col-md-5">
          <div class="card h-100">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center">
                <i class="la la-check-square me-2"></i>
                <strong>{{ translations.selected_sessions }}</strong>
                <span class="badge bg-white text-primary ms-2">{{ selected.length }}</span>
              </div>
            </div>
            
            <div class="card-body">
              <!-- Lista de sesiones -->
              <div class="sessions-list" style="max-height: 400px; overflow-y: auto;">
                <div v-if="!selected.length" class="text-center text-muted py-3">
                  <i class="la la-info-circle la-2x d-block mb-2"></i>
                  {{ translations.no_selected }}
                </div>

                <div v-for="s in selected" 
                    :key="'sel-' + s.id" 
                    class="session-item d-flex align-items-center p-2 mb-1 border rounded hover-bg bg-light">
                  <button 
                    class="btn btn-sm btn-outline-danger me-2"
                    type="button" 
                    @click="removeSession(s)"
                    @mouseenter="showTooltip"
                    @mouseleave="hideTooltip"
                    data-bs-toggle="tooltip"
                    data-bs-placement="right"
                    :title="translations.remove_from_pack"
                  >
                    <i class="la la-minus"></i>
                  </button>
                  <div class="flex-grow-1 text-truncate" :title="s.name">
                    <i class="la la-calendar-check-o text-primary me-1"></i>
                    {{ s.name }}
                  </div>
                </div>
              </div>

              <!-- Botón quitar todos -->
              <div v-if="selected.length > 1" class="mt-3 d-grid">
                <button 
                  class="btn btn-danger btn-sm"
                  type="button" 
                  @click="removeAll"
                >
                  <i class="la la-angle-double-left me-1"></i>
                  {{ translations.remove_all }} ({{ selected.length }})
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Hidden input -->
        <input type="hidden" :name="name" :value="jsonSelected" />
      </div>

      <style>
        .hover-bg:hover {
          background-color: rgba(0, 123, 255, 0.05) !important;
          cursor: pointer;
        }
        .session-item {
          transition: all 0.2s ease;
        }
        .session-item:hover {
          transform: translateX(2px);
        }
        .sessions-list::-webkit-scrollbar {
          width: 6px;
        }
        .sessions-list::-webkit-scrollbar-track {
          background: #f1f1f1;
          border-radius: 3px;
        }
        .sessions-list::-webkit-scrollbar-thumb {
          background: #888;
          border-radius: 3px;
        }
        .sessions-list::-webkit-scrollbar-thumb:hover {
          background: #555;
        }
      </style>
      `,
      setup() {
        // Inicializar tooltips al montar
        const { onMounted, onUnmounted, nextTick } = Vue;
        
        const translations = props.translations || {};
        let tooltipInstances = [];
        
        const initTooltips = () => {
          if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            // Destruir tooltips existentes
            tooltipInstances.forEach(t => t.dispose());
            tooltipInstances = [];
            
            // Crear nuevos tooltips
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltipInstances = [...tooltipTriggerList].map(tooltipTriggerEl => 
              new bootstrap.Tooltip(tooltipTriggerEl, {
                trigger: 'hover'
              })
            );
          }
        };
        
        const showTooltip = (event) => {
          const tooltip = bootstrap.Tooltip.getInstance(event.target);
          if (tooltip) tooltip.show();
        };
        
        const hideTooltip = (event) => {
          // Si event es undefined, ocultar todos los tooltips
          if (!event) {
            if (typeof bootstrap !== 'undefined') {
              const tooltips = document.querySelectorAll('.tooltip.show');
              tooltips.forEach(tooltip => tooltip.remove());
            }
          } else {
            const tooltip = bootstrap.Tooltip.getInstance(event.target);
            if (tooltip) tooltip.hide();
          }
        };
        
        onMounted(() => {
          nextTick(() => {
            initTooltips();
          });
        });
        
        onUnmounted(() => {
          // Limpiar tooltips al desmontar
          tooltipInstances.forEach(t => t.dispose());
          const tooltips = document.querySelectorAll('.tooltip.show');
          tooltips.forEach(tooltip => tooltip.remove());
        });

        return {
          ...state,
          addSession,
          removeSession,
          addAll,
          removeAll,
          jsonSelected,
          showTooltip,
          hideTooltip,
          name: props.name || 'sessions',
          translations
        };
      },
    };

    createApp(App).mount(el);
  });
})();