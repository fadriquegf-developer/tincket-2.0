/* =========================================================================
 *  Balance (Vue 3 + Axios) – traducciones vía props.t
 * ========================================================================= */
document.addEventListener("DOMContentLoaded", () => {
    mountResults(); // listener primero
    mountFilters(); // luego filtros (emite evento inicial)
});

/* -------------------------------------------------------------------------
 * 1. FILTROS
 * -------------------------------------------------------------------------*/
function mountFilters() {
    const el = document.getElementById("balance-filters");
    if (!el) return;

    /* recibimos filtros y traducciones en props */
    const { filters: initial, t } = JSON.parse(el.dataset.props || "{}");
    const today = new Date().toISOString().slice(0, 10);

    const App = {
        template: `
      <nav class="navbar navbar-expand-lg bp-filters-navbar p-0 rounded border-xs shadow-xs">
        <div class="container-fluid flex-nowrap">
          <ul class="navbar-nav flex-row flex-nowrap align-items-center w-100">

            <!-- Icono funnel -->
            <li class="nav-item me-3">
              <i class="la la-filter fs-5 text-primary"></i>
            </li>

            <!-- Desde -->
            <li class="nav-item d-flex flex-row align-items-center me-3">
              <span class="text-muted small me-1">{{ t.from }}:</span>
              <input type="date"
                     class="form-control form-control-sm w-auto"
                     v-model="filters.from">
            </li>

            <!-- Hasta -->
            <li class="nav-item d-flex flex-row align-items-center me-3">
              <span class="text-muted small me-1">{{ t.to }}:</span>
              <input type="date"
                     class="form-control form-control-sm w-auto"
                     v-model="filters.to">
            </li>

            <!-- Desglosar por -->
            <li class="nav-item d-flex flex-row align-items-center me-3">
              <span class="text-muted small me-1">{{ t.group_by }}:</span>
              <select class="form-select form-select-sm w-auto"
                      v-model="filters.breakdown">
                <option value="U">{{ t.user }}</option>
                <option value="E">{{ t.event }}</option>
                <option value="P">{{ t.promoter }}</option>
              </select>
            </li>

            <!-- Espaciador -->
            <li class="nav-item flex-grow-1"></li>

            <!-- Limpiar -->
            <li class="nav-item d-inline-flex" v-if="hasFilters">
              <a href="#" class="nav-link px-2 remove_filters_button"
                 @click.prevent="reset">{{ t.clear }}</a>
            </li>

            <!-- Generar -->
            <li class="nav-item d-inline-flex">
              <button class="btn btn-sm btn-primary" @click="generate">
                {{ t.generate }}
              </button>
            </li>
          </ul>
        </div>
      </nav>
    `,
        setup() {
            const filters = Vue.reactive({
                from: initial.from || today,
                to: initial.to || today,
                breakdown: initial.breakdown || "U",
            });

            const hasFilters = Vue.computed(
                () =>
                    filters.from !== today ||
                    filters.to !== today ||
                    filters.breakdown !== "U"
            );

            const fmt = (d) => (d ? d.replace(/-/g, "") : null);

            function generate() {
                window.dispatchEvent(
                    new CustomEvent("balance:generate", {
                        detail: {
                            from: fmt(filters.from),
                            to: fmt(filters.to),
                            breakdown: filters.breakdown,
                        },
                    })
                );
            }

            function reset() {
                filters.from = today;
                filters.to = today;
                filters.breakdown = "U";
                generate();
            }

            Vue.onMounted(generate);
            return { filters, hasFilters, generate, reset, t };
        },
    };

    Vue.createApp(App).mount(el);
}

/* -------------------------------------------------------------------------
 * 2. RESULTADOS
 * -------------------------------------------------------------------------*/
function mountResults() {
    const el = document.getElementById("balance-results");
    if (!el) return;

    /* sólo recibimos las traducciones */
    const { t } = JSON.parse(el.dataset.props || "{}");

    const results = Vue.ref([]);
    const breakdown = Vue.ref("U");
    const loading = Vue.ref(false);

    const columnMap = {
        U: [
            { label: t.seller, field: "name" },
            { label: t.inscriptions ?? "Inscripciones", field: "count" },
            {
                label: t.cash ?? "Pago en efectivo",
                field: "totalCash",
                format: money,
            },
            {
                label: t.card ?? "Pago con tarjeta",
                field: "totalCard",
                format: money,
            },
            { label: t.total ?? "Total", field: "sum", format: money },
        ],
        E: [
            { label: t.event, field: "name" },
            { label: t.inscriptions ?? "Inscripciones", field: "count" },
            { label: t.total ?? "Total", field: "sum", format: money },
        ],
        P: [
            { label: t.promoter, field: "name" },
            { label: t.inscriptions ?? "Inscripciones", field: "count" },
            { label: t.total ?? "Total", field: "sum", format: money },
        ],
    };

    function money(value) {
        return new Intl.NumberFormat("es-ES", {
            style: "currency",
            currency: "EUR",
        }).format(value ?? 0);
    }

    async function fetchResults(params) {
        loading.value = true;
        breakdown.value = params.breakdown;
        results.value = [];

        try {
            const { data } = await axios.get("/api/statistics/balance", {
                params,
            });
            results.value = data;
        } catch (e) {
            console.error(e);
            alert("Error al obtener datos");
        } finally {
            loading.value = false;
        }
    }

    const App = {
        template: `
      <div class="card rounded border-xs shadow-xs">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
           <h3 class="card-title mb-0">{{ t.results }}</h3>
            <button class="btn btn-outline-secondary btn-sm pe-2"
                   @click="exportCsv"
                   :disabled="!results.length">
                <i class="la la-download me-1"></i> CSV
            </button>
        </div>

        <div v-if="loading" class="py-5 text-center">
          <span class="spinner-border spinner-border-sm me-2"></span> {{ t.loading }}
        </div>

        <div v-else class="table-responsive">
          <table class="table table-striped table-hover mb-0">
            <thead>
              <tr><th v-for="c in columns" :key="c.field">{{ c.label }}</th></tr>
            </thead>
            <tbody>
              <tr v-for="(row,i) in results" :key="i" v-if="results.length">
                <td v-for="c in columns" :key="c.field">
                  {{ c.format ? c.format(row[c.field]) : row[c.field] }}
                </td>
              </tr>
              <tr v-else>
                <td v-for="c in columns" :key="c.field">
                  {{ c.format ? c.format(0) : '—' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    `,
        setup() {
            const columns = Vue.computed(
                () => columnMap[breakdown.value] ?? []
            );
            function exportCsv() {
                if (!results.value.length) return;

                // 1) Cabeceras según columnas visibles
                const header = columns.value.map((c) => c.label);

                // 2) Datos: cada fila en el mismo orden de columnas
                const rows = results.value.map((row) =>
                    columns.value.map((c) =>
                        c.format ? c.format(row[c.field]) : row[c.field]
                    )
                );

                // 3) Construimos el CSV
                const csvLines = [header, ...rows]
                    .map(
                        (r) =>
                            r
                                .map(
                                    (v) => `"${String(v).replace(/"/g, '""')}"` // escapado básico
                                )
                                .join(";") // separador punto-y-coma
                    )
                    .join("\n");

                // 4) Descarga
                const blob = new Blob([csvLines], {
                    type: "text/csv;charset=utf-8;",
                });
                const link = document.createElement("a");
                link.href = URL.createObjectURL(blob);
                link.download = `balance_${breakdown.value}_${Date.now()}.csv`;
                link.click();
                URL.revokeObjectURL(link.href);
            }
            return { results, columns, loading, t, exportCsv };
        },
    };

    Vue.createApp(App).mount(el);
    window.addEventListener("balance:generate", (e) => fetchResults(e.detail));
}
