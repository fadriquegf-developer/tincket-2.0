(function () {
    const el = document.getElementById("multiSessionApp");
    if (!el) return;

    /* importamos watch y computed */
    const { createApp, reactive, ref, computed, watch } = Vue;

    createApp({
        setup() {
            const events = JSON.parse(el.dataset.events);
            const spaces = JSON.parse(el.dataset.spaces); // ← trae zones y capacity
            const tpvs = JSON.parse(el.dataset.tpvs);
            const ratesCatalog = JSON.parse(el.dataset.rates);

            const storeUrl = el.dataset.storeUrl;
            const indexUrl = el.dataset.indexUrl;

            const trans = JSON.parse(el.dataset.trans);

            /* ---------- estado ---------- */
            const basic = reactive({
                event_id: "",
                space_id: "",
                tpv_id: "",
                max_places: "",
                is_numbered: 0,
                season_start: "",
                season_end: "",
                weekdays: [],
                inscription_start: "",
            });

            const templates = ref([]); // [{title,start,end}]
            const rates = ref([]); // [{zone_id, rate_id, …}]

            /* ======= AUTO-rellenar plazas y reset zonas ======= */
            watch(
                () => basic.space_id,
                (newId) => {
                    const sp = spaces.find((s) => s.id == newId);
                    if (sp) {
                        basic.max_places = sp.capacity ?? "";
                        rates.value.forEach((r) => (r.zone_id = ""));
                    }
                }
            );

            /* ======= zonas disponibles según espacio ======= */
            const availableZones = computed(() => {
                const sp = spaces.find((s) => s.id == basic.space_id);
                return sp ? sp.zones : [];
            });

            /* ------- helpers para tablas -------- */
            function addTemplate(row = {}) {
                templates.value.push({ title: "", start: "", end: "", ...row });
            }
            function removeTemplate(i) {
                templates.value.splice(i, 1);
            }

            function addRate(row = {}) {
                rates.value.push({
                    zone_id: "", // 👈 nuevo
                    rate_id: "",
                    price: "",
                    max_on_sale: "",
                    max_per_order: "",
                    is_public: 0,
                    ...row,
                });
            }
            function removeRate(i) {
                rates.value.splice(i, 1);
            }

            /* ---------- guardar ---------- */
            async function save() {
                try {
                    await axios.post(storeUrl, {
                        ...basic,
                        templates: templates.value,
                        rates: rates.value,
                    });
                    window.location = indexUrl;
                } catch (e) {
                    if (e.response && e.response.status === 422) {
                        const errs = e.response.data.errors || {};
                        Object.values(errs).forEach((msgs) => {
                            new Noty({
                                type: "error",
                                layout: "topRight",
                                timeout: 4000,
                                text: msgs[0], // primer mensaje del campo
                            }).show();
                        });

                        /* 2. Cualquier otro error */
                    } else {
                        new Noty({
                            type: "error",
                            layout: "topRight",
                            timeout: 4000,
                            text:
                                e.response?.data?.message ||
                                e.message ||
                                "Error desconocido",
                        }).show();
                    }
                }
            }

            return {
                events,
                spaces,
                tpvs,
                ratesCatalog,
                basic,
                templates,
                rates,
                availableZones,
                addTemplate,
                removeTemplate,
                addRate,
                removeRate,
                save,
                trans,
            };
        },

        template: /*html*/ `
<div class="space-y-1">

  <!-- ========== BÁSICOS + TEMPORADA ========== -->
  <div class="card p-4">
    <div class="row g-3">

      <!-- ────── Fila 1 ────── -->
      <div class="col-md-4">
        <label class="form-label mb-1 required">{{ trans.event }}</label>
        <select v-model="basic.event_id" class="form-control" required>
          <option value="" disabled>-</option>
          <option v-for="e in events" :value="e.id">{{ e.name }}</option>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label mb-1 required">{{ trans.space }}</label>
        <select v-model="basic.space_id" class="form-control" required>
          <option value="" disabled>-</option>
          <option v-for="s in spaces" :value="s.id">{{ s.name }}</option>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label mb-1">TPV</label>
        <select v-model="basic.tpv_id" class="form-control">
          <option value="">—</option>
          <option v-for="t in tpvs" :value="t.id">{{ t.name }}</option>
        </select>
      </div>

      <!-- ────── Fila 2  (fechas) ────── -->
      <div class="col-md-4">
        <label class="form-label mb-1 required">{{ trans.sale_start }}</label>
        <input v-model="basic.inscription_start"
               type="datetime-local"
               class="form-control">
      </div>
      
      <div class="col-md-4">
        <label class="form-label mb-1 required">{{ trans.season_start }}</label>
        <input v-model="basic.season_start" type="date" class="form-control">
      </div>

      <div class="col-md-4">
        <label class="form-label mb-1 required">{{ trans.season_end }}</label>
        <input v-model="basic.season_end" type="date" class="form-control">
      </div>

      

      <div class="col-md-4">
  <div class="alert alert-info py-2 mb-0">
    {{ trans.alert_autoclose }}
  </div>
</div>

      <!-- ────── Fila 3  (días + plazas + numerado) ────── -->
        <div class="col-md-4">
            <label class="form-label mb-2 d-block">{{ trans.weekdays }}</label>
            <div class="d-flex flex-wrap gap-3">
                <label v-for="(wd,idx) in trans.weekday_short" :key="idx" class="form-check">
                    <input class="form-check-input" type="checkbox" :value="idx+1" v-model="basic.weekdays"> {{ wd }}
                </label>
            </div>
        </div>

        <div class="col-md-3">
        <label class="form-label mb-1">{{ trans.max_places }}</label>
        <input v-model="basic.max_places" type="number" min="1" class="form-control">
        </div>

        <div class="col-md-1">
        <label class="form-label d-block">{{ trans.numbered }}</label>
        <div class="form-check form-switch d-flex align-items-center">
            <input  class="form-check-input"
                    type="checkbox"
                    id="is-numbered"
                    v-model="basic.is_numbered"
                    :true-value="1"
                    :false-value="0">
            <label class="form-check-label ms-2" for="is-numbered">
            {{ basic.is_numbered ? 'Si' : 'No' }}
            </label>
        </div>
        </div>

  <!-- ========== SESIONES ========= -->
  <div class="card p-4">
    <div class="d-flex justify-content-between mb-3">
      <h5 class="mb-0 ">{{ trans.sessions_title }}</h5>
      <button class="btn btn-outline-primary btn-sm" @click="addTemplate">
        <i class="la la-plus"></i> {{ trans.btn_add }}
      </button>
    </div>
    <table class="table table-sm align-middle">
      <thead>
        <tr><th>Título</th><th class="w-25">{{ trans.start_time }}</th><th class="w-25">{{ trans.end_time }}</th><th style="width:60px"></th></tr>
      </thead>
      <tbody>
        <tr v-for="(t,i) in templates" :key="i">
          <td><input v-model="t.title" class="form-control form-control-sm"></td>
          <td><input v-model="t.start" type="time" class="form-control form-control-sm"></td>
          <td><input v-model="t.end" type="time" class="form-control form-control-sm"></td>
          <td><button class="btn btn-outline-danger py-1 px-2 " @click="removeTemplate(i)"><i class="la la-times"></i></button></td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- ========== TARIFAS ========== -->
  <div class="card p-4">
    <div class="d-flex justify-content-between mb-3">
      <h5 class="mb-0">{{ trans.rates_title }}</h5>
      <button class="btn btn-outline-primary btn-sm" @click="addRate"><i class="la la-plus"></i> {{ trans.btn_add }}</button>
    </div>
    <table class="table table-sm align-middle">
      <thead>
        <tr>
          <th>{{ trans.zone }}</th><th>{{ trans.rate }}</th><th class="w-15">{{ trans.price }}</th>
          <th class="w-15">{{ trans.max_on_sale }}</th><th class="w-15">{{ trans.max_per_inscription }}</th>
          <th>Web</th><th style="width:60px"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(r,i) in rates" :key="i">
          <td>
            <select v-model="r.zone_id" class="form-select form-select-sm" :disabled="!availableZones.length">
              <option value="" disabled>{{ trans.zone }}</option>
              <option v-for="z in availableZones" :value="z.id">{{ z.name }}</option>
            </select>
          </td>
          <td>
            <select v-model="r.rate_id" class="form-select form-select-sm">
              <option value="" disabled>{{ trans.rates_title }}</option>
              <option v-for="rt in ratesCatalog" :value="rt.id">{{ rt.name }}</option>
            </select>
          </td>
          <td><input v-model="r.price" type="number" step="0.01" class="form-control form-control-sm"></td>
          <td><input v-model="r.max_on_sale" type="number" class="form-control form-control-sm"></td>
          <td><input v-model="r.max_per_order" type="number" class="form-control form-control-sm"></td>
          <td class="text-center"><input v-model="r.is_public" type="checkbox" class="form-check-input mt-0 d-flex align-items-center"></td>
          <td><button class="btn btn-outline-danger py-1 px-2 " @click="removeRate(i)"><i class="la la-times"></i></button></td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- ========== GUARDAR ========== -->
  <div class="text-end">
    <button class="btn btn-primary px-4 mt-2" @click="save">
      <i class="la la-save me-1"></i> {{ trans.btn_save }}
    </button>
  </div>
</div>`,
    }).mount("#multiSessionApp");
})();
