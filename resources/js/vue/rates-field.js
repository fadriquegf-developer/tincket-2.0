// public/js/vue/rates-field.js
(function () {
    const { createApp, reactive, watch, nextTick } = Vue;

    document.querySelectorAll('[id^="rates-field-"]').forEach((el) => {
        const props = JSON.parse(el.dataset.props);
        let uidCounter = 0;
        function makeUid() {
            return "r_" + Date.now() + "_" + uidCounter++;
        }

        // Estado inicial
        const state = reactive({
            zones: props.zones || [],
            defined: props.definedRates || [],
            numbered: !!props.isNumbered,
            dirty: false,
            modalIndex: null,

            rates: (props.initial || []).map((r) => {
                const originalAttr = r.rate?.validator_class_attr || {};

                const rateDef =
                    r.rate && props.definedRates
                        ? props.definedRates.find((d) => d.id === r.rate.id)
                        : null;

                return {
                    uid: makeUid(),
                    assignated_rate_id: r.assignated_rate_id ?? null,
                    rate: rateDef, // así Vue puede mostrar el nombre
                    max_on_sale: r.max_on_sale ?? 0,
                    max_per_order: r.max_per_order ?? 0,
                    price: r.price ?? 0,
                    is_public: r.is_public ?? false,
                    is_private: r.is_private ?? false,
                    max_per_code: r.max_per_code ?? 0,
                    available_since: r.available_since ?? "",
                    available_until: r.available_until ?? "",
                    code: originalAttr.code || "",
                    max_per_user: originalAttr.max_per_user || "",
                };
            }),
        });

        const App = {
            template: `
        <div>
          <table ref="table" class="table table-bordered table-striped">
            <thead>
              <tr>
                <th v-if="zones.length && numbered">{{ translations.zone }}</th>
                <th>{{ translations.rate }}</th>
                <th>{{ translations.total }}</th>
                <th>{{ translations.per_insc }}</th>
                <th>{{ translations.price }}</th>
                <th>Web</th>
                <th>Limitada</th>
                <th>{{ translations.actions }}</th>
              </tr>
            </thead>
            <tbody ref="tbody">
              <tr v-for="(r,i) in rates" :key="r.uid" class="draggable">
                <td v-if="zones.length && numbered">
                  <select v-model="r.assignated_rate_id" @change="onZoneChange(i)" class="form-control">
                    <option :value="null" disabled>Selecciona zona</option>
                    <option v-for="z in zones" :key="z.id" :value="z.id">{{ z.name }}</option>
                  </select>
                </td>
                <td>
                  <select v-model="r.rate" @change="onRateChange(i)" class="form-control">
                    <option :value="null" disabled>Selecciona tarifa</option>
                    <option v-for="d in filteredRates(r)" :key="d.id" :value="d">{{ d.name }}</option>
                  </select>
                  <div v-if="r.rate && r.rate.needs_code===1" class="mt-2">
                    <input class="form-control mb-1"
                          :placeholder=" translations.discount_code"
                          v-model="r.code">
                    <input type="number" class="form-control"
                          :placeholder="translations.max_per_user"
                          v-model.number="r.max_per_user">
                  </div>
                </td>
                <td><input class="form-control" v-model.number="r.max_on_sale"></td>
                <td><input class="form-control" v-model.number="r.max_per_order"></td>
                <td><input class="form-control" v-model.number="r.price" step="0.1"></td>
                <td class="text-center"><input type="checkbox" v-model="r.is_public"></td>
                <td class="text-center"><input type="checkbox" v-model="r.is_private" @change="onPrivateChange(i)"></td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm">
                      <button
                        v-if="r.is_private"
                        type="button"
                        class="btn btn-secondary"
                        @click.prevent="openModal(i)"
                        title="Importar códigos"
                      ><i class="la la-cog"></i></button>
                      <button
                        type="button"
                        class="btn btn-danger"
                        @click.prevent="remove(i)"
                        title="Eliminar tarifa"
                      ><i class="la la-trash"></i></button>
                      <button
                        type="button"
                        class="btn btn-light sort-handle"
                        title="Reordenar filas"
                      ><i class="la la-sort"></i></button>
                    </div>
                  </td>
              </tr>
            </tbody>
          </table>
          <button type="button" class="btn btn-sm btn-primary" @click.prevent="add">
            <i class="la la-plus"></i> {{ translations.add_rate }}
          </button>       
      </div>
      `,
            setup() {
                const filteredRates = (row) =>
                    state.defined.filter((d) => {
                        // 1) Siempre dejamos la tarifa ya seleccionada en esta fila
                        if (row.rate?.id === d.id) return true;

                        // 2) Si hay sesión numerada con zonas
                        if (state.numbered && state.zones.length > 0) {
                            // Excluimos si hay otra fila con el mismo rate + misma zona
                            const conflict = state.rates.some(
                                (r2) =>
                                    r2 !== row &&
                                    r2.assignated_rate_id ===
                                        row.assignated_rate_id &&
                                    r2.rate?.id === d.id
                            );
                            return !conflict;
                        } else {
                            // 3) Sin zonas: excluimos si la tarifa ya está asignada en cualquier otra fila
                            const alreadyUsed = state.rates.some(
                                (r2) => r2 !== row && r2.rate?.id === d.id
                            );
                            return !alreadyUsed;
                        }
                    });
                function add() {
                    state.rates.push({
                        uid: makeUid(),
                        assignated_rate_id: null,
                        rate: null,
                        max_on_sale: 0,
                        max_per_order: 0,
                        price: 0,
                        is_public: true,
                        is_private: false,
                        max_per_code: 0,
                        available_since: "",
                        available_until: "",
                        code: "",
                        max_per_user: "",
                    });
                    state.dirty = true;
                }
                function remove(i) {
                    state.rates.splice(i, 1);
                    state.dirty = true;
                }
                function onPrivateChange(i) {
                    if (state.rates[i].is_private) openModal(i);
                    state.dirty = true;
                }
                function openModal(i) {
                    const r = state.rates[i];
                    $("#modal_max_per_code").val(r.max_per_code);
                    $("#modal_available_since").val(r.available_since);
                    $("#modal_available_until").val(r.available_until);
                    state.modalIndex = i;
                    $("#importCodesModal").modal("show");
                }
                function onRateChange(i) {
                    state.dirty = true;
                }
                function onZoneChange(i) {
                  const r = state.rates[i];
                  // Si la combinación zona+tarifa ya existe en otra fila, limpiamos la tarifa
                  if (r.rate && r.assignated_rate_id) {
                      const conflict = state.rates.some((r2, idx) =>
                          idx !== i &&
                          r2.assignated_rate_id === r.assignated_rate_id &&
                          r2.rate?.id === r.rate.id
                      );
                      if (conflict) {
                          r.rate = null;
                      }
                  }
                  state.dirty = true;
                }

                return {
                    ...state,
                    translations: props.translations,
                    filteredRates,
                    add,
                    remove,
                    onPrivateChange,
                    openModal,
                    onRateChange,
                    onZoneChange,
                };
            },
            mounted() {
                nextTick(() => {
                    Sortable.create(this.$refs.tbody, {
                        handle: ".sort-handle",
                        animation: 150,
                        onEnd(evt) {
                            const arr = state.rates.splice(evt.oldIndex, 1);
                            state.rates.splice(evt.newIndex, 0, arr[0]);
                            state.dirty = true;
                        },
                    });
                });
            },
        };

        createApp(App).mount(el);

        // modal save
        $("#modal_save_codes").on("click", () => {
            const i = state.modalIndex;
            if (i === null) return;
            const r = state.rates[i];
            r.max_per_code = Number($("#modal_max_per_code").val());
            r.available_since = $("#modal_available_since").val();
            r.available_until = $("#modal_available_until").val();
            state.dirty = true;
            $("#importCodesModal").modal("hide");
        });

        // watch → hidden inputs
        watch(
            () => state.rates,
            () => {
                const payload = state.rates.map((r) => {
                    const e = {
                        uid: r.uid,
                        assignated_rate_id: r.assignated_rate_id,
                        zone_id: r.assignated_rate_id,
                        rate: r.rate,
                        price: r.price,
                        max_on_sale: r.max_on_sale,
                        max_per_order: r.max_per_order,
                        is_public: r.is_public,
                        is_private: r.is_private,
                        max_per_code: r.max_per_code,
                        available_since: r.available_since || null,
                        available_until: r.available_until || null,
                    };
                    if (r.rate && r.rate.needs_code === 1) {
                        e.validator_class = {
                            class: r.rate.validator_class,
                            attr: {
                                code: r.code,
                                max_per_user: r.max_per_user,
                            },
                        };
                    }
                    return e;
                });
                document.getElementById("rates-json-" + props.name).value =
                    JSON.stringify(payload);
                document.getElementById("rates-dirty-" + props.name).value = 1;
            },
            { deep: true }
        );
    });
})();
