/* eslint-disable no-console */
(function () {
  const { createApp, reactive, computed, onMounted, nextTick, ref } = Vue;

  document.querySelectorAll('[id^="embedded-entities-field-"]').forEach((el) => {
    const props  = JSON.parse(el.dataset.props);
    const locale = props.locale || 'es';

    /* ---------- helpers ---------- */
    let uid = 0;
    const makeUid = () => `e_${Date.now()}_${uid++}`;

    const makeLabel = (opt) => {
      const pick = (v) =>
        typeof v === 'object' && v
          ? v[locale] ?? Object.values(v)[0]
          : v;
      return pick(opt.name) || pick(opt.title) || opt.label || opt.slug || opt.id;
    };

    const fetchOptions = async (type) => {
      try {
        const url = '/api/entity?type=' + encodeURIComponent(type);
        const r   = await fetch(url);
        if (!r.ok) {
          console.error('[EmbeddedEntities] '+r.status+' '+url);
          return [];
        }
        return await r.json();
      } catch (e) {
        console.error('[EmbeddedEntities] error', e);
        return [];
      }
    };

    /* ---------- state ---------- */
    const state = reactive({
      entities: (props.initial || []).map(i => ({
        uid         : makeUid(),
        embeded_type: i.embeded_type,
        embeded_id  : i.embeded_id,
        options     : [],
        loading     : true,
      })),
    });

    /* ---------- Vue component ---------- */
    const App = {
      template: `
        <div class="array-container">
          <table class="table table-bordered table-striped w-100" style="table-layout:fixed;">
            <colgroup>
              <col style="width:30%">
              <col style="width:60%">
              <col style="width:10%">
            </colgroup>
            <thead>
              <tr><th>Tipo</th><th>Entidad</th><th class="text-center"></th></tr>
            </thead>

            <tbody ref="tbody">
              <tr v-for="(item,i) in entities" :key="item.uid">
                <!-- Tipo -->
                <td>
                  <select v-model="item.embeded_type"
                          class="form-control"
                          style="width:100%"
                          @change.stop="changeType(item)">
                    <option disabled value="">Selecciona</option>
                    <option value="App\\Models\\Event">Event</option>
                    <option value="App\\Models\\Page">Page</option>
                    <option value="App\\Models\\Post">Post</option>
                  </select>
                </td>

                <!-- Entidad -->
                <td>
                  <select v-if="!item.loading"
                          v-model="item.embeded_id"
                          class="form-control"
                          style="width:100%"
                          @change.stop
                          @input.stop>
                    <option disabled :value="null">Selecciona</option>
                    <option v-for="o in item.options" :key="o.id" :value="o.id">
                      {{ o.label }}
                    </option>
                  </select>
                  <span v-else class="fa fa-spinner fa-spin"></span>
                </td>

                <!-- Handle + Delete -->
                <td class="text-center align-middle">
                   <div class="d-flex flex-row h-100 w-100">
                      <button type="button"
                            class="drag-handle btn btn-light flex-fill d-flex align-items-center justify-content-center rounded-0 me-2"
                            style="cursor:move">
                            <i class="la la-sort"></i>
                      </button>
                      <button type="button"
                            class="btn btn-danger flex-fill d-flex align-items-center justify-content-center rounded-0"
                            @click.stop.prevent="remove(i)">
                            <i class="la la-trash"></i>
                      </button>
                    </div>
                </td>
              </tr>
            </tbody>
          </table>

          <button type="button"
                  class="btn btn-sm btn-primary mt-2"
                  @click.stop.prevent="add">
            <i class="la la-plus"></i> Añadir
          </button>

          <input type="hidden"
                 name="embedded_entities"
                 :value="jsonValue">
        </div>
      `,

      setup() {
        /* ref al tbody para Sortable */
        const tbody = ref(null);

        /* JSON que viaja al backend */
        const jsonValue = computed(() =>
          JSON.stringify(
            state.entities
              .filter(e => e.embeded_type && e.embeded_id)
              .map(e => ({ embeded_type: e.embeded_type, embeded_id: e.embeded_id }))
          )
        );

        /* carga de opciones */
        const loadOptions = async (item) => {
          item.loading = true;
          item.options = (await fetchOptions(item.embeded_type))
                          .map(o => ({ ...o, label: makeLabel(o) }));
          item.loading = false;
        };

        /* montar Sortable */
        onMounted(() => {
          state.entities.forEach(loadOptions);

          nextTick(() => {
            Sortable.create(tbody.value, {
              handle    : '.drag-handle',
              animation : 150,
              onEnd(evt) {
                if (evt.oldIndex === evt.newIndex) return;
                const moved = state.entities.splice(evt.oldIndex, 1)[0];
                state.entities.splice(evt.newIndex, 0, moved);
              },
            });
          });
        });

        /* acciones */
        const add = () =>
          state.entities.push({
            uid         : makeUid(),
            embeded_type: '',
            embeded_id  : null,
            options     : [],
            loading     : false,
          });

        const remove     = i    => state.entities.splice(i, 1);
        const changeType = item => { item.embeded_id = null; loadOptions(item); };

        return { tbody, entities: state.entities, jsonValue, add, remove, changeType };
      },
    };

    createApp(App).mount(el);
  });
})();
