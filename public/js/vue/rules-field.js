(function () {
    const {
        createApp,
        reactive,
        watch,
        computed
    } = Vue;

    document.querySelectorAll('[id^="rules-field-"]').forEach((el) => {
        const props = JSON.parse(el.dataset.props);
        const t = props.t || {};

        const initialRules = Array.isArray(props.initial) ? props.initial.map(rule => ({
            number_sessions: rule.number_sessions ?? 1,
            percent_pack: rule.percent_pack ?? 0,
            price_pack: rule.price_pack ?? 0,
            all_remaining_sessions: rule.all_remaining_sessions ?? false,
        })) : [{
            number_sessions: 1,
            percent_pack: 0,
            price_pack: 0,
            all_remaining_sessions: false
        }, ];

        const state = reactive({
            rules: initialRules,
        });

        function updateHiddenInput() {
            const input = el.querySelector('input[type="hidden"]');
            if (input) {
                input.value = JSON.stringify(state.rules);
            }
        }

        const App = {
            template: `
      <div>
        <div class="table-responsive">
          <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
              <tr>
                <th scope="col" class="text-nowrap">{{ t.sessions }}</th>
                <th scope="col" class="text-nowrap">{{ t.discount_percent }}</th>
                <th scope="col" class="text-nowrap">{{ t.price }}</th>
                <th scope="col" class="text-center" style="width: 120px;">{{ t.actions }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(rule, index) in rules" :key="index">
                <td>
                  <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text">
                      <i class="la la-calendar-check-o"></i>
                    </span>
                    <input 
                      type="number" 
                      v-model.number="rule.number_sessions" 
                      class="form-control form-control-sm"
                      min="1"
                    />
                  </div>
                  <div class="form-check form-switch">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      role="switch"
                      v-model="rule.all_remaining_sessions"
                      :id="'all-remaining-' + index"
                    />
                    <label class="form-check-label small" :for="'all-remaining-' + index">
                      {{ t.all_remaining }}
                    </label>
                  </div>
                </td>
                <td class="align-top">
                  <div class="input-group input-group-sm">
                    <input 
                      type="number" 
                      v-model.number="rule.percent_pack" 
                      class="form-control form-control-sm" 
                      step="0.5"
                      min="0"
                      max="100"
                      placeholder="0.00"
                    />
                    <span class="input-group-text">%</span>
                  </div>
                </td>
                <td class="align-top">
                  <div class="input-group input-group-sm">
                    <input 
                      type="number" 
                      v-model.number="rule.price_pack" 
                      class="form-control form-control-sm" 
                      step="0.10"
                      min="0"
                      placeholder="0.00"
                    />
                    <span class="input-group-text">€</span>
                  </div>
                </td>
                <td class="text-center align-top">
                  <button
                    type="button"
                    class="btn btn-danger btn-sm"
                    @click="removeRule(index)"
                    :disabled="rules.length === 1"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    :title="rules.length === 1 ? '' : 'Eliminar regla'"
                  >
                    <i class="la la-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mt-3">
          <button
            type="button"
            class="btn btn-outline-primary btn-sm"
            @click="addRule"
          >
            <i class="la la-plus me-1"></i>
            {{ t.add_rule || 'Añadir regla' }}
          </button>
          
          <small class="text-muted">
            <i class="la la-info-circle"></i>
            Total de reglas: {{ rules.length }}
          </small>
        </div>

        <input type="hidden" :name="name" :value="jsonRules" />
      </div>
      `,
            setup() {
                function addRule() {
                    state.rules.push({
                        number_sessions: 1,
                        percent_pack: 0,
                        price_pack: 0,
                        all_remaining_sessions: false,
                    });
                    updateHiddenInput();

                    // Reinicializar tooltips después de añadir
                    nextTick(() => {
                        initTooltips();
                    });
                }

                function removeRule(i) {
                    if (state.rules.length > 1) {
                        state.rules.splice(i, 1);
                        updateHiddenInput();
                    }
                }

                function initTooltips() {
                    // Inicializar tooltips de Bootstrap 5 si está disponible
                    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
                    }
                }

                const jsonRules = computed(() => JSON.stringify(state.rules));

                // Importar nextTick si está disponible
                const {
                    nextTick
                } = Vue;

                // Inicializar tooltips al montar
                nextTick(() => {
                    initTooltips();
                });

                return {
                    ...state,
                    addRule,
                    removeRule,
                    jsonRules,
                    name: props.name,
                    t
                };
            },
        };

        createApp(App).mount(el);

        watch(
            () => state.rules,
            () => {
                updateHiddenInput();
            }, {
                deep: true
            }
        );
    });
})();
