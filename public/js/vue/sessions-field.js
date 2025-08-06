/* sessions-field.js – parche final */
(function () {
  const { createApp, reactive, computed } = Vue;

  document.querySelectorAll('[id^="sessions-field-"]').forEach((el) => {
    const props = JSON.parse(el.dataset.props || '{}');

    const state = reactive({
      available: [...(props.sessions || [])],
      selected : [...(props.initial  || [])],
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
      if (!state.selected.some(sel => String(sel.id) === String(session.id))) {
        state.selected.push(session);
      }
      const idx = state.available.findIndex(av => String(av.id) === String(session.id));
      if (idx > -1) state.available.splice(idx, 1);   // <-- aquí el cambio
      syncHidden();
    };

    const removeSession = (session) => {
      if (!state.available.some(av => String(av.id) === String(session.id))) {
        state.available.push(session);
      }
      const idx = state.selected.findIndex(sel => String(sel.id) === String(session.id));
      if (idx > -1) state.selected.splice(idx, 1);
      syncHidden();
    };

    const addAll = () => {
      state.available.forEach(av => {
        if (!state.selected.some(sel => String(sel.id) === String(av.id))) {
          state.selected.push(av);
        }
      });
      state.available.splice(0, state.available.length); // vacía con splice
      syncHidden();
    };

    const removeAll = () => {
      state.selected.forEach(sel => {
        if (!state.available.some(av => String(av.id) === String(sel.id))) {
          state.available.push(sel);
        }
      });
      state.selected.splice(0, state.selected.length);
      syncHidden();
    };

    /* ---------- COMPONENT ---------- */
    const App = {
      template: `
      <div class="row gy-2">
        <div class="col-md-6">
          <label class="form-label fw-bold mb-1">List of sessions</label>
          <div v-if="!available.length" class="text-muted">No sessions available</div>

          <div v-for="s in available" :key="'av-' + s.id" class="d-flex align-items-center mb-1">
            <div class="flex-grow-1">{{ s.name }}</div>
            <button class="btn btn-outline-secondary btn-sm ms-2"
                    type="button" @click="addSession(s)">
              <i class="la la-angle-right"></i>
            </button>
          </div>

          <button v-if="available.length > 1"
                  class="btn btn-success btn-sm mt-2"
                  type="button" @click="addAll">
            Add all
          </button>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-bold mb-1">Session in pack</label>
          <div v-if="!selected.length" class="text-muted">No sessions selected</div>

          <div v-for="s in selected" :key="'sel-' + s.id" class="d-flex align-items-center mb-1">
            <button class="btn btn-outline-secondary btn-sm me-2"
                    type="button" @click="removeSession(s)">
              <i class="la la-angle-left"></i>
            </button>
            <div class="flex-grow-1">{{ s.name }}</div>
          </div>

          <button v-if="selected.length > 1"
                  class="btn btn-danger btn-sm mt-2"
                  type="button" @click="removeAll">
            Remove all
          </button>
        </div>

        <input type="hidden" :name="name" :value="jsonSelected" />
      </div>
      `,
      setup() {
        return {
          ...state,
          addSession,
          removeSession,
          addAll,
          removeAll,
          jsonSelected,
          name: props.name || 'sessions',
        };
      },
    };

    createApp(App).mount(el);
  });
})();
