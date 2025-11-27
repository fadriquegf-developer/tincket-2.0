(function () {
    const el = document.getElementById("multiSessionApp");
    if (!el) return;

    /* importamos watch y computed */
    const { createApp, reactive, ref, computed, watch, onMounted, nextTick } =
        Vue;

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
            const isSaving = ref(false); // Estado de carga

            // ✅ NUEVO: Modo de creación
            const creationMode = ref('season'); // 'season' o 'specific_dates'
            const specificDates = ref([{ date: '', title: '', start: '', end: '' }]);

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

            /* ======= Cargar Select2 dinámicamente si no está disponible ======= */
            let select2LoadAttempts = 0;
            const maxLoadAttempts = 30;

            function loadSelect2Resources() {
                return new Promise((resolve, reject) => {
                    // Si jQuery no está disponible, cargar jQuery primero
                    if (
                        typeof jQuery === "undefined" &&
                        typeof $ === "undefined"
                    ) {
                        const jqueryScript = document.createElement("script");
                        jqueryScript.src =
                            "https://code.jquery.com/jquery-3.6.0.min.js";
                        jqueryScript.onload = () => {
                            window.jQuery = window.$;
                            loadSelect2();
                        };
                        jqueryScript.onerror = () =>
                            reject("Error cargando jQuery");
                        document.head.appendChild(jqueryScript);
                    } else {
                        // jQuery ya está disponible
                        if (typeof jQuery === "undefined") {
                            window.jQuery = window.$;
                        }
                        loadSelect2();
                    }

                    function loadSelect2() {
                        // Verificar si Select2 ya está cargado
                        if (typeof jQuery.fn.select2 !== "undefined") {
                            resolve();
                            return;
                        }

                        // Cargar CSS de Select2
                        const cssLink = document.createElement("link");
                        cssLink.rel = "stylesheet";
                        cssLink.href =
                            "https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css";
                        document.head.appendChild(cssLink);

                        // Cargar tema Bootstrap compatible con Backpack
                        const themeCssLink = document.createElement("link");
                        themeCssLink.rel = "stylesheet";
                        themeCssLink.href =
                            "https://cdn.jsdelivr.net/npm/select2-bootstrap-theme@0.1.0-beta.10/dist/select2-bootstrap.min.css";
                        document.head.appendChild(themeCssLink);

                        // Agregar estilos personalizados para modo oscuro
                        const darkModeStyles = document.createElement("style");
                        darkModeStyles.innerHTML = `
                            /* Estilos para Select2 en modo oscuro */
                            [data-bs-theme="dark"] .select2-container--bootstrap .select2-selection,
                            [data-bs-theme="dark"] .select2-container--bootstrap.select2-container--focus .select2-selection {
                                background-color: #2d3748;
                                border-color: #4a5568;
                                color: #e2e8f0;
                            }
                            
                            [data-bs-theme="dark"] .select2-container--bootstrap .select2-selection--single .select2-selection__rendered {
                                color: #e2e8f0;
                            }
                            
                            [data-bs-theme="dark"] .select2-container--bootstrap .select2-selection--single .select2-selection__placeholder {
                                color: #a0aec0;
                            }
                            
                            [data-bs-theme="dark"] .select2-dropdown {
                                background-color: #2d3748;
                                border-color: #4a5568;
                            }
                            
                            [data-bs-theme="dark"] .select2-container--bootstrap .select2-results__option {
                                color: #e2e8f0;
                            }
                            
                            [data-bs-theme="dark"] .select2-container--bootstrap .select2-results__option--highlighted {
                                background-color: #4299e1;
                                color: #ffffff;
                            }
                            
                            [data-bs-theme="dark"] .select2-container--bootstrap .select2-results__option[aria-selected=true] {
                                background-color: #4a5568;
                                color: #e2e8f0;
                            }
                            
                            [data-bs-theme="dark"] .select2-search--dropdown .select2-search__field {
                                background-color: #1a202c;
                                border-color: #4a5568;
                                color: #e2e8f0;
                            }
                            
                            [data-bs-theme="dark"] .select2-container--bootstrap .select2-selection__arrow b {
                                border-color: #e2e8f0 transparent transparent transparent;
                            }
                            
                            [data-bs-theme="dark"] .select2-container--bootstrap.select2-container--open .select2-selection__arrow b {
                                border-color: transparent transparent #e2e8f0 transparent;
                            }

                            /* Estilos adicionales para disabled */
                            [data-bs-theme="dark"] .select2-container--bootstrap .select2-selection--single.select2-selection--disabled {
                                background-color: #1a202c;
                                color: #718096;
                            }
                        `;
                        document.head.appendChild(darkModeStyles);

                        // Cargar JS de Select2
                        const script = document.createElement("script");
                        script.src =
                            "https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.full.min.js";
                        script.onload = () => resolve();
                        script.onerror = () => reject("Error cargando Select2");
                        document.head.appendChild(script);
                    }
                });
            }

            function waitForSelect2() {
                return new Promise((resolve, reject) => {
                    const $ = window.jQuery || window.$;

                    if (!$) {
                        if (select2LoadAttempts < maxLoadAttempts) {
                            select2LoadAttempts++;
                            setTimeout(() => {
                                waitForSelect2().then(resolve).catch(reject);
                            }, 100);
                        } else {
                            reject(
                                "jQuery no disponible después de múltiples intentos"
                            );
                        }
                        return;
                    }

                    if (typeof $.fn.select2 !== "undefined") {
                        resolve($);
                        return;
                    }

                    if (select2LoadAttempts < maxLoadAttempts) {
                        select2LoadAttempts++;
                        setTimeout(() => {
                            waitForSelect2().then(resolve).catch(reject);
                        }, 100);
                    } else {
                        reject(
                            "Select2 no disponible después de múltiples intentos"
                        );
                    }
                });
            }

            async function initializeSelect2() {
                try {
                    await loadSelect2Resources();
                    const $ = await waitForSelect2();

                    nextTick(() => {
                        $("#event-select, #space-select, #tpv-select").select2({
                            theme: "bootstrap",
                            width: "100%",
                        });

                        $("#event-select").on("change", function () {
                            basic.event_id = $(this).val();
                        });

                        $("#space-select").on("change", function () {
                            basic.space_id = $(this).val();
                        });

                        $("#tpv-select").on("change", function () {
                            basic.tpv_id = $(this).val();
                        });
                    });
                } catch (error) {
                    console.warn(
                        "Select2 no se pudo cargar correctamente:",
                        error
                    );
                }
            }

            /* ============= ZONAS ============= */
            const selectedSpace = computed(() => {
                return spaces.find((s) => s.id == basic.space_id) || null;
            });

            const availableZones = computed(() => {
                return selectedSpace.value ? selectedSpace.value.zones : [];
            });

            const showZoneColumn = computed(() => {
                return basic.is_numbered == 1;
            });

            /* ---------- watchers ---------- */
            watch(
                () => basic.space_id,
                (newSpaceId) => {
                    if (!newSpaceId) {
                        basic.max_places = "";
                        return;
                    }
                    const sp = spaces.find((s) => s.id == newSpaceId);
                    if (sp && sp.capacity) {
                        basic.max_places = sp.capacity;
                    }
                }
            );

            watch(
                () => basic.is_numbered,
                (isNumbered) => {
                    nextTick(() => {
                        const $ = window.jQuery || window.$;
                        if ($ && typeof $.fn.select2 !== "undefined") {
                            if (isNumbered == 1) {
                                $(".zone-select").select2({
                                    theme: "bootstrap",
                                    width: "100%",
                                });

                                $(".zone-select").on("change", function () {
                                    const idx = $(this).data("index");
                                    rates.value[idx].zone_id = $(this).val();
                                });
                            } else {
                                $(".zone-select").select2("destroy");
                            }
                        }
                    });
                },
                { immediate: false }
            );

            /* ---------- funciones ---------- */
            function addTemplate() {
                templates.value.push({ title: "", start: "", end: "" });
            }

            function removeTemplate(index) {
                templates.value.splice(index, 1);
            }

            function addRate() {
                const newRate = {
                    zone_id: "",
                    rate_id: "",
                    price: "",
                    max_on_sale: "",
                    max_per_order: "",
                    is_public: 0,
                };
                rates.value.push(newRate);

                nextTick(() => {
                    const $ = window.jQuery || window.$;
                    if ($ && typeof $.fn.select2 !== "undefined") {
                        const idx = rates.value.length - 1;

                        if (basic.is_numbered == 1) {
                            $(`.zone-select[data-index="${idx}"]`).select2({
                                theme: "bootstrap",
                                width: "100%",
                            });
                            $(`.zone-select[data-index="${idx}"]`).on(
                                "change",
                                function () {
                                    rates.value[idx].zone_id = $(this).val();
                                }
                            );
                        }

                        $(`.rate-select[data-index="${idx}"]`).select2({
                            theme: "bootstrap",
                            width: "100%",
                        });
                        $(`.rate-select[data-index="${idx}"]`).on(
                            "change",
                            function () {
                                rates.value[idx].rate_id = $(this).val();
                            }
                        );
                    }
                });
            }

            function removeRate(index) {
                rates.value.splice(index, 1);
            }

            // ✅ NUEVO: Funciones para fechas específicas
            function addSpecificDate() {
                specificDates.value.push({ date: '', title: '', start: '', end: '' });
            }

            function removeSpecificDate(index) {
                if (specificDates.value.length > 1) {
                    specificDates.value.splice(index, 1);
                }
            }

            async function save() {
                isSaving.value = true;

                try {
                    const payload = {
                        creation_mode: creationMode.value, // ✅ NUEVO
                        event_id: basic.event_id,
                        space_id: basic.space_id,
                        tpv_id: basic.tpv_id,
                        max_places: basic.max_places,
                        is_numbered: basic.is_numbered,
                        inscription_start: basic.inscription_start,
                        rates: rates.value,
                    };

                    // ✅ NUEVO: Datos según modo
                    if (creationMode.value === 'season') {
                        payload.season_start = basic.season_start;
                        payload.season_end = basic.season_end;
                        payload.weekdays = basic.weekdays;
                        payload.templates = templates.value;
                    } else {
                        payload.specific_dates = specificDates.value;
                    }

                    const res = await axios.post(storeUrl, payload);

                    if (res.data && res.data.success !== false) {
                        window.location.href = indexUrl;
                    } else {
                        alert(res.data.message || "Error al guardar");
                    }
                } catch (err) {
                    console.error(err);
                    if (err.response && err.response.data) {
                        const errors = err.response.data.errors || {};
                        let msg = "Errores de validación:\n";
                        for (const k in errors) {
                            msg += `- ${errors[k].join(", ")}\n`;
                        }
                        alert(msg);
                    } else {
                        alert("Error al crear las sesiones");
                    }
                } finally {
                    isSaving.value = false;
                }
            }

            onMounted(() => {
                initializeSelect2();
            });

            return {
                events,
                spaces,
                tpvs,
                ratesCatalog,
                basic,
                templates,
                rates,
                creationMode,        // ✅ NUEVO
                specificDates,       // ✅ NUEVO
                selectedSpace,
                availableZones,
                showZoneColumn,
                isSaving,
                addTemplate,
                removeTemplate,
                addRate,
                removeRate,
                addSpecificDate,     // ✅ NUEVO
                removeSpecificDate,  // ✅ NUEVO
                save,
                trans,
            };
        },

        template: /*html*/ `
<div class="space-y-1">

  <!-- ========== SELECTOR DE MODO ========== -->
  <div class="card p-4 mb-3">
    <h5 class="mb-3">{{ trans.creation_mode || 'Modo de creación' }}</h5>
    <div class="d-flex gap-4">
      <label class="form-check">
        <input class="form-check-input" type="radio" value="season" v-model="creationMode">
        <span class="form-check-label">
          <strong>{{ trans.mode_season || 'Temporada' }}</strong>
          <small class="d-block text-muted">{{ trans.mode_season_desc || 'Rango + días semana' }}</small>
        </span>
      </label>
      <label class="form-check">
        <input class="form-check-input" type="radio" value="specific_dates" v-model="creationMode">
        <span class="form-check-label">
          <strong>{{ trans.mode_specific || 'Fechas específicas' }}</strong>
          <small class="d-block text-muted">{{ trans.mode_specific_desc || 'Días concretos individuales' }}</small>
        </span>
      </label>
    </div>
  </div>

  <!-- ========== BÁSICOS ========== -->
  <div class="card p-4">
    <div class="row g-3">

      <!-- ────── Fila 1 ────── -->
      <div class="col-md-4">
        <label class="form-label mb-1 required">{{ trans.event }}</label>
        <select v-model="basic.event_id" id="event-select" class="form-control" required>
          <option value="" disabled>-</option>
          <option v-for="e in events" :key="e.id" :value="e.id">{{ e.name }}</option>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label mb-1 required">{{ trans.space }}</label>
        <select v-model="basic.space_id" id="space-select" class="form-control" required>
          <option value="" disabled>-</option>
          <option v-for="s in spaces" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label mb-1">TPV</label>
        <select v-model="basic.tpv_id" id="tpv-select" class="form-control">
          <option value="">—</option>
          <option v-for="t in tpvs" :key="t.id" :value="t.id">{{ t.name }}</option>
        </select>
      </div>

      <!-- ────── Fila 2 ────── -->
      <div class="col-md-4">
        <label class="form-label mb-1 required">{{ trans.sale_start }}</label>
        <input v-model="basic.inscription_start"
               type="datetime-local"
               class="form-control">
      </div>

      <div class="col-md-3">
        <label class="form-label mb-1">{{ trans.max_places }}</label>
        <input v-model="basic.max_places" type="number" min="1" class="form-control">
      </div>

      <div class="col-md-1">
        <label class="form-label d-block">{{ trans.numbered }}</label>
        <div class="form-check form-switch d-flex align-items-center">
          <input class="form-check-input"
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

    </div>
  </div>

  <!-- ========== MODO TEMPORADA ========== -->
  <div v-if="creationMode === 'season'" class="card p-4">
    <h5 class="mb-3">{{ trans.season_config || 'Configuración de temporada' }}</h5>
    <div class="row g-3">
      
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

      <div class="col-md-12">
        <label class="form-label mb-2 d-block">{{ trans.weekdays }}</label>
        <div class="d-flex flex-wrap gap-3">
          <label v-for="(wd,idx) in trans.weekday_short" :key="idx" class="form-check">
            <input class="form-check-input" type="checkbox" :value="idx+1" v-model="basic.weekdays"> {{ wd }}
          </label>
        </div>
      </div>

    </div>

    <!-- Plantillas para modo temporada -->
    <div class="mt-4">
      <div class="d-flex justify-content-between mb-3">
        <h6 class="mb-0">{{ trans.sessions_title }}</h6>
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
            <td><button class="btn btn-outline-danger py-1 px-2" @click="removeTemplate(i)"><i class="la la-times"></i></button></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ========== MODO FECHAS ESPECÍFICAS ========== -->
  <div v-if="creationMode === 'specific_dates'" class="card p-4">
    <div class="d-flex justify-content-between mb-3">
      <h5 class="mb-0">{{ trans.specific_dates_title || 'Sesiones individuales' }}</h5>
      <button class="btn btn-outline-primary btn-sm" @click="addSpecificDate">
        <i class="la la-plus"></i> {{ trans.btn_add }}
      </button>
    </div>
    <table class="table table-sm align-middle">
      <thead>
        <tr>
          <th style="width:20%">{{ trans.date || 'Fecha' }}</th>
          <th style="width:30%">{{ trans.title || 'Título' }}</th>
          <th style="width:20%">{{ trans.start_time }}</th>
          <th style="width:20%">{{ trans.end_time }}</th>
          <th style="width:60px"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(sd,i) in specificDates" :key="i">
          <td><input v-model="sd.date" type="date" class="form-control form-control-sm" required></td>
          <td><input v-model="sd.title" type="text" class="form-control form-control-sm" :placeholder="trans.session_name || 'Nombre sesión'"></td>
          <td><input v-model="sd.start" type="time" class="form-control form-control-sm" required></td>
          <td><input v-model="sd.end" type="time" class="form-control form-control-sm" required></td>
          <td>
            <button v-if="specificDates.length > 1" class="btn btn-outline-danger py-1 px-2" @click="removeSpecificDate(i)">
              <i class="la la-times"></i>
            </button>
          </td>
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
          <th v-if="showZoneColumn">{{ trans.zone }}</th><th>{{ trans.rate }}</th><th class="w-15">{{ trans.price }}</th>
          <th class="w-15">{{ trans.max_on_sale }}</th><th class="w-15">{{ trans.max_per_inscription }}</th>
          <th>Web</th><th style="width:60px"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(r,i) in rates" :key="i">
          <td v-if="showZoneColumn">
            <select v-model="r.zone_id" 
                    class="form-select form-select-sm zone-select" 
                    :data-index="i"
                    :disabled="!availableZones.length">
              <option value="" disabled>{{ trans.zone }}</option>
              <option v-for="z in availableZones" :key="z.id" :value="z.id">{{ z.name }}</option>
            </select>
          </td>
          <td>
            <select v-model="r.rate_id" 
                    class="form-select form-select-sm rate-select"
                    :data-index="i">
              <option value="" disabled>{{ trans.rates_title }}</option>
              <option v-for="rt in ratesCatalog" :key="rt.id" :value="rt.id">{{ rt.name }}</option>
            </select>
          </td>
          <td><input v-model="r.price" type="number" step="0.01" class="form-control form-control-sm"></td>
          <td><input v-model="r.max_on_sale" type="number" class="form-control form-control-sm"></td>
          <td><input v-model="r.max_per_order" type="number" class="form-control form-control-sm"></td>
          <td class="text-center"><input v-model="r.is_public" type="checkbox" :true-value="1" :false-value="0" class="form-check-input mt-0 d-flex align-items-center"></td>
          <td><button class="btn btn-outline-danger py-1 px-2" @click="removeRate(i)"><i class="la la-times"></i></button></td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- ========== GUARDAR ========== -->
  <div class="text-end">
    <button class="btn btn-primary px-4 mt-2" @click="save" :disabled="isSaving">
      <span v-if="isSaving">
        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
        {{ trans.btn_saving || 'Guardando...' }}
      </span>
      <span v-else>
        <i class="la la-save me-1"></i> {{ trans.btn_save }}
      </span>
    </button>
  </div>
</div>`,
    }).mount("#multiSessionApp");
})();