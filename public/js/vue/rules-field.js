(function () {
  const { createApp, reactive, watch, computed } = Vue;

  document.querySelectorAll('[id^="rules-field-"]').forEach((el) => {
    const props = JSON.parse(el.dataset.props);
    const t     = props.t || {};

    const initialRules = Array.isArray(props.initial) ? props.initial.map(rule => ({
      number_sessions: rule.number_sessions ?? 1,
      percent_pack: rule.percent_pack ?? 0,
      price_pack: rule.price_pack ?? 0,
      all_remaining_sessions: rule.all_remaining_sessions ?? false,
    })) : [
      { number_sessions: 1, percent_pack: 0, price_pack: 0, all_remaining_sessions: false },
    ];

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
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>{{ t.sessions }}</th>
              <th>{{ t.discount_percent }}</th>
              <th>{{ t.price  }}</th>
              <th>{{ t.actions  }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(rule, index) in rules" :key="index">
              <td>
                <input type="number" v-model.number="rule.number_sessions" class="form-control" />
                <div class="form-check mt-1">
                  <input
                    class="form-check-input mt-2"
                    type="checkbox"
                    v-model="rule.all_remaining_sessions"
                    :id="'all-remaining-' + index"
                  />
                  <label class="form-check-label" :for="'all-remaining-' + index">
                    {{ t.all_remaining }}
                  </label>
                </div>
              </td>
              <td><input type="number" v-model.number="rule.percent_pack" class="form-control" step="0.01" /></td>
              <td><input type="number" v-model.number="rule.price_pack" class="form-control" step="0.01" /></td>
              <td>
                <button
                  type="button"
                  class="btn btn-danger btn-sm mt-1"
                  @click="removeRule(index)"
                ><i class="la la-trash me-1"></i>
                  Eliminar
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <button
          type="button"
          class="btn btn-primary btn-sm mt-2"
          @click="addRule"
        >
          {{ t.add_rule }}
        </button>

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
        }
        function removeRule(i) {
          state.rules.splice(i, 1);
          updateHiddenInput();
        }

        const jsonRules = computed(() => JSON.stringify(state.rules));

        return { ...state, addRule, removeRule, jsonRules, name: props.name, t };
      },
    };

    createApp(App).mount(el);

    watch(
      () => state.rules,
      () => {
        updateHiddenInput();
      },
      { deep: true }
    );
  });
})();
