/* ========================================================================
 *  Estadísticas de VENTAS  —  Backpack 6 · Vue 3 · Axios
 * ====================================================================== */
document.addEventListener("DOMContentLoaded", () => {
    mountResults(); // primero el listener
    mountFilters(); // luego los filtros
});

/* ─ utilidades ─────────────────────────────────────────────────────────── */
const money = (v) =>
    new Intl.NumberFormat("es-ES", {
        style: "currency",
        currency: "EUR",
    }).format(Number(v) || 0);

function tLang(v) {
    /* string | {es:'', ca:''} */
    if (v == null) return "";
    if (typeof v === "string") return v;
    const lang = window.appLocale || "es";
    return v[lang] ?? Object.values(v)[0] ?? "";
}

function fmtDateTime(iso) {
    if (!iso) return "";
    const s =
        typeof iso === "string" && iso.includes(" ") && !iso.includes("T")
            ? iso.replace(" ", "T")
            : iso;
    const d = new Date(s);
    if (Number.isNaN(d.getTime())) return iso; // fallback
    return d
        .toLocaleString("es-ES", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        })
        .replace(",", "");
}

/* Quick helper para TicketOffice ---------------------------------------- */
function ticketPaymentType(r) {
    if (
        r.payment_method !== "TicketOffice" &&
        r.cart?.confirmed_payment?.gateway !== "TicketOffice"
    )
        return "";

    // Preferimos el campo plano del backend; si no, intentamos parsear legacy
    const type =
        r.ticket_payment_type ||
        (() => {
            try {
                const obj = JSON.parse(
                    r.cart?.confirmed_payment?.gateway_response || "{}"
                );
                return obj.payment_type || "";
            } catch {
                return "";
            }
        })();

    if (type === "cash")
        return tLang({
            es: "En efectivo",
            ca: "En efectiu",
        });
    if (type === "card")
        return tLang({
            es: "Tarjeta de crédito",
            ca: "Targeta de crèdit",
        });
    return type || "NA";
}

function getPaymentTypeDisplay(r) {
    const gateway = r.payment_method || r.cart?.confirmed_payment?.gateway;

    if (gateway === "TicketOffice") {
        const type = r.ticket_payment_type;
        if (type === "cash")
            return tLang({
                es: "Efectivo",
                ca: "Efectiu",
            });
        if (type === "card")
            return tLang({
                es: "Tarjeta",
                ca: "Targeta",
            });
        return "TicketOffice";
    }

    // Cualquier otro gateway = tarjeta
    return tLang({
        es: "Tarjeta",
        ca: "Targeta",
    });
}

/* ─────────────────────────────── FILTROS ──────────────────────────────── */
function mountFilters() {
    const el = document.getElementById("sales-filters");
    if (!el) return;
    const { t } = JSON.parse(el.dataset.props || "{}");
    const today = new Date().toISOString().slice(0, 10);

    const App = {
        template: /*html*/ `
    <nav class="navbar navbar-expand-lg bp-filters-navbar p-0 rounded border-xs shadow-xs">
    <div class="container-fluid flex-wrap gap-3">

        <!-- ▼ Sesiones -->
        <div class="nav-item dropdown me-3">
            <a href="#" class="nav-link dropdown-toggle px-2" data-bs-toggle="dropdown">
                Filtrar {{ t.sessions }}
                <span v-if="filters.session_ids.length">
                ({{ filters.session_ids.length }})
                </span>
            </a>

            <div class="dropdown-menu p-3 shadow" style="min-width:320px;max-width:380px">

                <!-- rango sesiones -->
                <div class="row g-2 mb-2">
                    <div class="col">
                        <label class="form-label small mb-0">{{ t.sessions_from }}</label>
                        <input type="date" class="form-control form-control-sm"
                            v-model="filters.sessions_from">
                    </div>
                    <div class="col">
                        <label class="form-label small mb-0">{{ t.sessions_to }}</label>
                        <input type="date" class="form-control form-control-sm"
                            v-model="filters.sessions_to">
                    </div>
                </div>

                <button class="btn btn-link btn-sm px-0 mb-2" @click="selectAll">
                {{ t.select_all }}
                </button>

                <div class="border rounded p-2" style="max-height:240px;overflow:auto">
                    <div v-for="s in filteredSessions" :key="s.id" class="form-check mb-1">
                        <input class="form-check-input" type="checkbox"
                            :id="'sess-'+s.id" :value="s.id" v-model="filters.session_ids">
                        <label class="form-check-label small" :for="'sess-'+s.id">
                        {{ tLang(s.event.name) }} – {{ tLang(s.name) }}
                        ({{ fmtDateTime(s.starts_on) }})
                        </label>
                    </div>
                    <p v-if="!filteredSessions.length" class="text-muted small mb-0">
                        {{ t.no_sessions }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Desglosar por ------------------------------------------------------->
        <div class="d-flex align-items-center flex-wrap gap-1">
        <label class="text-muted small mb-0">{{ t.breakdown }}:</label>
        <select class="form-select form-select-sm w-auto" v-model="filters.breakdown">
            <option value="R">{{ t.by_rate }}</option>
            <option value="P">{{ t.by_payment }}</option>
            <option value="U">{{ t.by_user }}</option>
            <option value="T">{{ t.by_ticket }}</option>
        </select>
        </div>

    <!-- Ventas desde / hasta ------------------------------------------------>
    <div class="d-flex align-items-center flex-wrap gap-1">
      <label class="text-muted small mb-0">{{ t.sales_from }}:</label>
      <input type="date" class="form-control form-control-sm w-auto"
             v-model="filters.sales_from">
    </div>

    <div class="d-flex align-items-center flex-wrap gap-1">
      <label class="text-muted small mb-0">{{ t.sales_to }}:</label>
      <input type="date" class="form-control form-control-sm w-auto"
             v-model="filters.sales_to">
    </div>

    <!-- Sólo resumen ------------------------------------------------------->
    <div class="d-flex align-items-center flex-wrap gap-1">
      <label class="text-muted small mb-0" for="onlySummary">{{ t.only_summary }}:</label>
      <input class="form-check-input" type="checkbox" id="onlySummary"
             v-model="filters.only_summary">
    </div>

    <!-- Botón generar ------------------------------------------------------->
    <div class="ms-auto">
      <button class="btn btn-sm btn-primary" @click="generate">{{ t.generate }}</button>
    </div>
  </div>
</nav>

  <div v-if="selectedSessionsDisplay.length" class="container-fluid pt-2 pb-2 border-top">
    <small class="text-muted me-2">{{ t.selected_sessions || 'Sesiones seleccionadas' }}:</small>
    <div class="d-flex flex-wrap gap-2 mt-1">
        <span v-for="s in selectedSessionsDisplay" :key="s.id" 
              class="badge bg-primary d-inline-flex align-items-center gap-1">
            {{ tLang(s.event.name) }} – {{ tLang(s.name) }} ({{ fmtDateTime(s.starts_on) }})
            <button type="button" 
                    class="btn-close btn-close-white" 
                    style="font-size: 0.6rem; padding: 0.25rem;"
                    @click.stop="removeSession(s.id)"
                    :aria-label="'Eliminar ' + tLang(s.name)"></button>
        </span>
    </div>
</div>`,

        setup() {
            const filters = Vue.reactive({
                session_ids: [],
                sessions_from: null,
                sessions_to: null,
                sales_from: today,
                sales_to: today,
                breakdown: "R",
                only_summary: false,
            });
            const sessions = Vue.ref([]);

            Vue.onMounted(async () => {
                const { data } = await axios.get(
                    "/api/session?with_sales=1&show_expired=1"
                );
                sessions.value = data.data ?? data.sessions ?? data;
            });

            const filteredSessions = Vue.computed(() =>
                sessions.value.filter((s) => {
                    const d = s.starts_on.slice(0, 10);
                    if (filters.sessions_from && d < filters.sessions_from)
                        return false;
                    if (filters.sessions_to && d > filters.sessions_to)
                        return false;
                    return true;
                })
            );

            const selectedSessionsDisplay = Vue.computed(() =>
                sessions.value.filter((s) => filters.session_ids.includes(s.id))
            );

            const removeSession = (sessionId) => {
                filters.session_ids = filters.session_ids.filter(
                    (id) => id !== sessionId
                );
            };
            const selectAll = () => {
                filters.session_ids = filteredSessions.value.map((s) => s.id);
            };

            function buildUrl() {
                const ids = filters.session_ids.join(",");
                const range = `{"from":${new Date(
                    filters.sales_from
                ).getTime()},"to":${new Date(filters.sales_to).getTime()}}`;
                return (
                    `/api/statistics/sales?session_id=${ids}` +
                    `&breakdown=${filters.breakdown}` +
                    `&sales_range=${range}`
                );
                // ☝️ Quitamos &summary=${...} para siempre traer rows
            }
            const generate = () => {
                const selectedSessions = sessions.value.filter((s) =>
                    filters.session_ids.includes(s.id)
                );
                window.dispatchEvent(
                    new CustomEvent("sales:generate", {
                        detail: {
                            url: buildUrl(),
                            summaryOnly: filters.only_summary,
                            bk: filters.breakdown,
                            t,
                            sessions: selectedSessions,
                            filters: {
                                sales_from: filters.sales_from,
                                sales_to: filters.sales_to,
                            },
                        },
                    })
                );
            };

            Vue.watch(
                [() => filters.sessions_from, () => filters.sessions_to],
                () => {
                    filters.session_ids = filters.session_ids.filter((id) =>
                        filteredSessions.value.some((s) => s.id === id)
                    );
                }
            );
            return {
                filters,
                filteredSessions,
                selectedSessionsDisplay,
                removeSession,
                selectAll,
                generate,
                t,
                tLang,
                fmtDateTime,
            };
        },
    };
    Vue.createApp(App).mount(el);
}

/* ───────────────────────────── RESULTADOS ─────────────────────────────── */
function mountResults() {
    const el = document.getElementById("sales-results");
    if (!el) return;
    const { t } = JSON.parse(el.dataset.props || "{}");

    const rows = Vue.ref([]);
    const summary = Vue.ref([]);
    const loading = Vue.ref(false);
    const summaryOnly = Vue.ref(false);
    const bkNow = Vue.ref("R");
    const currentSessions = Vue.ref([]);
    const currentFilters = Vue.ref({});

    /* columnas detalle (fijas) ----------------------------------------------*/
    const colsDetail = [
        {
            label: t.event,
            key: "ev",
            val: (r) => tLang(r.event_name ?? r.session?.event?.name),
        },
        {
            label: t.session,
            key: "se",
            val: (r) => {
                const n = tLang(r.session?.name ?? r.session_name);
                const d = fmtDateTime(
                    r.session?.starts_on ?? r.session_starts_on
                );

                // Si no hay nombre o es 'null', solo mostrar fecha
                if (!n || n === "null" || n.trim() === "") {
                    return d || "";
                }

                // Si hay nombre, mostrar nombre + fecha entre paréntesis
                return n + (d ? " (" + d + ")" : "");
            },
        },
        {
            label: t.sold_at,
            key: "sa",
            val: (r) => fmtDateTime(r.paid_at),
        },
        {
            label: t.rate_pack,
            key: "rp",
            val: (r) => {
                // PRIMERO: comprobar si es un pack
                if (r.group_pack?.pack?.name)
                    return tLang(r.group_pack.pack.name);
                if (r.pack_name) return tLang(r.pack_name);

                // SEGUNDO: si NO es pack, mostrar la tarifa
                if (r.rate?.name) return tLang(r.rate.name);
                if (r.rate_name) return tLang(r.rate_name);

                return "";
            },
        },
        {
            label: t.price_sold,
            key: "ps",
            val: (r) => money(r.price_sold),
        },
        {
            label: t.cart,
            key: "ca",
            val: (r) => r.confirmation_code ?? "",
        },
        {
            label: t.client,
            key: "cl",
            val: (r) => r.cart?.client?.email ?? r.client_email ?? "",
        },
        {
            label: t.sold_by,
            key: "sb",
            val: (r) =>
                r.cart?.seller?.name ??
                r.cart?.seller?.code_name ??
                r.seller_name ??
                "",
        },
        {
            label: t.payment_method || "Método de pago",
            key: "pm",
            val: (r) => getPaymentTypeDisplay(r),
        },
    ];

    function getMetadataKeys(data) {
        const keys = new Set();
        data.forEach((r) => {
            if (r.inscription_metadata) {
                try {
                    // El metadata viene como string JSON, hay que parsearlo
                    const metadata =
                        typeof r.inscription_metadata === "string"
                            ? JSON.parse(r.inscription_metadata)
                            : r.inscription_metadata;

                    Object.keys(metadata).forEach((k) => keys.add(k));
                } catch (e) {
                    console.error("❌ Error parseando metadata:", e);
                }
            }
        });
        const result = Array.from(keys).sort();
        return result;
    }
    function getExportColumns(data) {
        const metaKeys = getMetadataKeys(data);
        const metaCols = metaKeys.map((key) => ({
            label: key,
            key: `meta_${key}`,
            val: (r) => {
                if (!r.inscription_metadata) return ""; // ⭐ CAMBIO AQUÍ

                try {
                    const metadata =
                        typeof r.inscription_metadata === "string"
                            ? JSON.parse(r.inscription_metadata)
                            : r.inscription_metadata;
                    return metadata[key] ?? "";
                } catch (e) {
                    return "";
                }
            },
        }));

        return [...colsDetail, ...metaCols];
    }

    /* construir resumen ------------------------------------------------------*/
    function buildSummary(bk, arr) {
        /* --- Pago taquilla (T) -------------------------------------------------*/
        if (bk === "T") {
            const map = new Map();
            arr.filter(
                (r) =>
                    r.payment_method === "TicketOffice" ||
                    r.cart?.confirmed_payment?.gateway === "TicketOffice"
            ).forEach((r) => {
                const k = ticketPaymentType(r); // ya viene traducido con el helper
                if (!map.has(k))
                    map.set(k, {
                        name: k,
                        count: 0,
                        amount: 0,
                    });
                const g = map.get(k);
                g.count++;
                g.amount += Number(r.price_sold) || 0;
            });
            return [...map.values()];
        }

        /* --- Usuario --- */
        if (bk === "U") {
            const map = new Map();
            arr.forEach((r) => {
                const n =
                    r.cart?.seller?.name ??
                    r.cart?.seller?.code_name ??
                    r.seller_name ??
                    "";
                if (!map.has(n))
                    map.set(n, {
                        name: n,
                        seller_type: r.seller_type,
                        count: 0,
                        amount: 0,
                        cash: 0,
                        card: 0,
                    });
                const g = map.get(n);
                g.count++;
                g.amount += Number(r.price_sold) || 0;

                const gateway =
                    r.payment_method || r.cart?.confirmed_payment?.gateway;
                if (
                    gateway === "TicketOffice" &&
                    r.ticket_payment_type === "cash"
                ) {
                    g.cash++;
                } else {
                    g.card++;
                }
            });

            const result = [...map.values()];
            return result;
        }

        /* --- Tarifa (R)   o   Método pago (P) --- con hijos -------------------*/
        if (bk === "P") {
            // Top: método de pago; Child: tarifa/pack
            const map = new Map();
            const keyTop = (r) =>
                r.payment_method ?? r.cart?.confirmed_payment?.gateway ?? "—";
            const isPack = (r) => r.group_pack_id != null || !!r.pack_name;
            const keyChild = (r) => {
                // Si es pack, mostrar SOLO el nombre del pack
                if (isPack(r)) {
                    const packName = tLang(
                        r.group_pack?.pack?.name ?? r.pack_name ?? "—"
                    );
                    return packName + " (Pack)";
                }
                // Si NO es pack, mostrar el nombre de la tarifa
                const rateName = tLang(r.rate?.name ?? r.rate_name ?? "—");
                return rateName;
            };

            arr.forEach((r) => {
                const a = keyTop(r),
                    b = keyChild(r);
                if (!map.has(a))
                    map.set(a, {
                        name: a,
                        count: 0,
                        amount: 0,
                        children: new Map(),
                    });
                const g = map.get(a);
                g.count++;
                g.amount += Number(r.price_sold) || 0;
                if (!g.children.has(b))
                    g.children.set(b, {
                        name: b,
                        count: 0,
                        amount: 0,
                    });
                const c = g.children.get(b);
                c.count++;
                c.amount += Number(r.price_sold) || 0;
            });

            return [...map.values()].map((x) => ({
                ...x,
                children: [...x.children.values()],
            }));
        }

        // bk === 'R' → tarifas + fila especial "Packs"
        {
            const isPack = (r) => r.group_pack_id != null || !!r.pack_name;
            const rateKey = (r) => tLang(r.rate?.name ?? r.rate_name ?? "—");
            const packName = (r) =>
                tLang(r.group_pack?.pack?.name ?? r.pack_name ?? "—");
            const method = (r) =>
                r.payment_method ?? r.cart?.confirmed_payment?.gateway ?? "NA";

            // 1) TARIFAS (NO packs) → hijos por método de pago (Sermepa, TicketOffice, ...)
            const mapRates = new Map();
            arr.filter((r) => !isPack(r)).forEach((r) => {
                const k = rateKey(r);
                if (!mapRates.has(k))
                    mapRates.set(k, {
                        name: k,
                        count: 0,
                        amount: 0,
                        children: new Map(),
                    });
                const g = mapRates.get(k);
                g.count++;
                g.amount += Number(r.price_sold) || 0;

                const m = method(r);
                if (!g.children.has(m))
                    g.children.set(m, {
                        name: m,
                        count: 0,
                        amount: 0,
                    });
                const c = g.children.get(m);
                c.count++;
                c.amount += Number(r.price_sold) || 0;
            });

            const rateRows = [...mapRates.values()].map((x) => ({
                ...x,
                children: [...x.children.values()],
            }));

            // 2) PACKS → hijos por nombre de pack Y gateway
            const packsDetailMap = new Map();
            let packsCount = 0,
                packsAmount = 0;
            arr.filter(isPack).forEach((r) => {
                const name = packName(r);
                const gateway = method(r); // ✅ Obtener gateway
                const key = name + "|" + gateway; // ✅ Clave única: pack + gateway

                if (!packsDetailMap.has(key))
                    packsDetailMap.set(key, {
                        name: name + " (" + gateway + ")", // ✅ Mostrar gateway en el nombre
                        count: 0,
                        amount: 0,
                        _set: new Set(),
                    });
                const d = packsDetailMap.get(key);
                d.count++;
                d.amount += Number(r.price_sold) || 0;
                packsCount++;
                packsAmount += Number(r.price_sold) || 0;
                if (r.group_pack_id != null) d._set.add(r.group_pack_id);
            });

            const packChildren = [...packsDetailMap.values()].map((d) => {
                const n = d._set.size;
                delete d._set;
                return {
                    ...d,
                    nPacks: n,
                };
            });
            const nPacksTotal = packChildren.reduce(
                (a, b) => a + (b.nPacks || 0),
                0
            );
            const packsRow = {
                name: "Packs",
                count: packsCount,
                amount: packsAmount,
                nPacks: nPacksTotal,
                children: packChildren,
            };

            return [...rateRows, packsRow];
        }
    }

    /* columnas resumen según breakdown --------------------------------------*/
    function colsSummary(bk) {
        const labelName =
            bk === "U"
                ? t.user
                : bk === "P"
                ? t.method
                : bk === "T"
                ? t.ticket_payment
                : t.rate_pack;
        return [
            {
                label: labelName,
                key: "n",
                val: (g) => g.name,
            },
            {
                label: t.quantity,
                key: "q",
                val: (g) => g.count,
            },
            {
                label: t.price_sold,
                key: "p",
                val: (g) => money(g.amount),
            },
        ];
    }

    /* fetch ------------------------------------------------------------------*/
    async function fetchData({ url, summaryOnly: so, bk, sessions, filters }) {
        summaryOnly.value = so;
        bkNow.value = bk;
        currentSessions.value = sessions || [];
        currentFilters.value = filters || {};
        loading.value = true;
        rows.value = [];
        summary.value = [];
        try {
            const { data } = await axios.get(url);
            rows.value = data.results ?? [];
            if (Array.isArray(data.summary) && data.summary.length) {
                summary.value =
                    bk === "T"
                        ? data.summary.map((x) => ({
                              ...x,
                              name:
                                  x.name === "cash"
                                      ? tLang({
                                            es: "En efectivo",
                                            ca: "En efectiu",
                                        })
                                      : x.name === "card"
                                      ? tLang({
                                            es: "Tarjeta de crédito",
                                            ca: "Targeta de crèdit",
                                        })
                                      : x.name || "NA",
                          }))
                        : data.summary; // ✅ Usar summary del backend tal cual
            } else {
                summary.value = buildSummary(bk, rows.value);
            }
        } catch (e) {
            console.error(e);
        } finally {
            loading.value = false;
        }
    }

    /* export helpers ---------------------------------------------------------*/
    function dl(blob, name) {
        const a = document.createElement("a");
        a.href = URL.createObjectURL(blob);
        a.download = name;
        a.click();
        URL.revokeObjectURL(a.href);
    }

    function exportCsv(cols, data, includeMetadata = false) {
        // Si queremos metadata, construir columnas dinámicas
        const finalCols = includeMetadata ? getExportColumns(data) : cols;

        const csv = [
            finalCols.map((c) => c.label),
            ...data.map((r) =>
                finalCols.map((c) => {
                    let val = c.val(r);

                    if (c.key === 'sa' && r.paid_at) {
                        // Formato DD/MM/YYYY HH:mm que Excel entiende bien
                        const d = new Date(r.paid_at);
                        if (!isNaN(d)) {
                            val = d.toLocaleString('es-ES', {
                                day: '2-digit',
                                month: '2-digit', 
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                        }
                    }

                    // Limpiar símbolo € si existe
                    if (typeof val === "string" && val.includes("€")) {
                        val = val.replace(/[^\d,.-]/g, "").replace(",", ".");
                    }

                    return val;
                })
            ),
        ]
            .map((r) =>
                r
                    .map((v) => {
                        // Si es número (o string numérico), no usar comillas
                        const num = Number(v);
                        if (!isNaN(num) && v !== "" && v !== null) {
                            return num; // Sin comillas
                        }
                        // Si es texto, escapar comillas y envolver
                        return `"${String(v).replace(/"/g, '""')}"`;
                    })
                    .join(";")
            )
            .join("\n");

        const BOM = "\uFEFF";
        dl(
            new Blob([BOM + csv], {
                type: "text/csv;charset=utf-8;",
            }),
            `sales_${Date.now()}.csv`
        );
    }

    /* componente -------------------------------------------------------------*/
    const App = {
        template: /*html*/ `
<div class="card rounded border-xs shadow-xs">
 <div class="card-header py-3 d-flex justify-content-between align-items-center">
   <h3 class="card-title mb-0">{{ t.results }}</h3>
   <div class="btn-group">
  <!-- PDF Resumen -->
  <button v-if="summary.length"
          class="btn btn-outline-secondary btn-sm"
          @click="exportPdfSummary"
          :disabled="!summary.length">
          <i class="la la-file-pdf me-1"></i> {{ t.pdf || 'PDF' }} {{ t.summary || 'Resumen' }}
  </button>
  
  <!-- PDF Detalle -->
  <button v-if="rows.length && !summaryOnly"
          class="btn btn-outline-secondary btn-sm"
          @click="exportPdfDetail"
          :disabled="!rows.length">
          <i class="la la-file-pdf me-1"></i> {{ t.pdf || 'PDF' }} {{ t.detail || 'Detalle' }}
  </button>
    
    <!-- CSV Resumen -->
    <button v-if="summary.length" 
            class="btn btn-outline-secondary btn-sm"
            @click="exportCsv(colsSum, summaryFlat)"
            :disabled="!summary.length">
            <i class="la la-download me-1"></i> {{ t.csv }} {{ t.summary || 'Resumen' }}
    </button>
    
    <!-- CSV Detalle -->
    <button v-if="rows.length && !summaryOnly" 
            class="btn btn-outline-secondary btn-sm"
            @click="exportCsv(colsDetail, rows, true)"
            :disabled="!rows.length">
            <i class="la la-download me-1"></i> {{ t.csv }} {{ t.detail || 'Detalle' }}
    </button>
    </div>
 </div>

 <div v-if="loading" class="py-5 text-center">
   <span class="spinner-border spinner-border-sm me-2"></span> {{ t.loading }}
 </div>

 <div v-else class="p-3">

   <!-- Resumen --------------------------------------------------------->
   <table v-if="summary.length" class="table table-striped mb-4">
     <thead><tr><th v-for="c in colsSum" :key="c.key">{{ c.label }}</th></tr></thead>
     <tbody>
       <template v-for="(g,i) in summary" :key="i">
         <tr class="fw-bold">
            <td>
                <i class="las la-user-astronaut" v-if="g.seller_type === 'App\\\\Models\\\\User'" title="User"></i>
                <i class="las la-globe-europe" v-else-if="g.seller_type === 'App\\\\Models\\\\Application'" title="Web"></i>
                {{ g.name === 'Free' ? 'Free (web)' : g.name }}
            </td>
            <td>{{ g.count }} <span v-if="bkNow === 'U' && (g.cash || g.card)" class="text-muted small">({{ g.cash }} {{ t.cash || 'efectivo' }} / {{ g.card }} {{ t.card || 'tarjeta' }})</span></td>
            <td>{{ money(g.amount) }}</td>
        </tr>
        <tr v-for="(c,j) in g.children||[]" :key="j">
            <td class="ps-4">{{ c.name === 'Free' ? 'Free (web)' : c.name }}</td>
            <td>{{ c.count }}</td>
            <td>{{ money(c.amount) }}</td>
        </tr>
    </template>
       <tr class="table-active fw-bold">
  <td>All</td>
  <td>
    {{ allTotals.count }}
    <span v-if="bkNow !== 'T' && (allTotals.webCount > 0 || allTotals.officeCount > 0)" class="text-muted small">
      (web: {{ allTotals.webCount }}, taquilla: {{ allTotals.officeCount }})
    </span>
  </td>
  <td>
    {{ money(allTotals.amount) }}
    <span v-if="bkNow !== 'T' && (allTotals.webAmount > 0 || allTotals.officeAmount > 0)" class="text-muted small">
      (web: {{ money(allTotals.webAmount) }}, taquilla: {{ money(allTotals.officeAmount) }})
    </span>
  </td>
</tr>
     </tbody>
   </table>

   <!-- Detalle --------------------------------------------------------->
   <div v-if="!summaryOnly" class="table-responsive">
     <table v-if="rows.length" class="table table-striped mb-0">
       <thead><tr><th v-for="c in colsDetail" :key="c.key">{{ c.label }}</th></tr></thead>
       <tbody>
        <tr v-for="(r,i) in rows" :key="i">
          <td v-for="c in colsDetail" :key="c.key">
            <template v-if="c.key==='ca'">
              <a :href="'/cart/' + r.cart_id + '/show'" target="_blank">{{ r.confirmation_code }}</a>
            </template>
            <template v-else-if="c.key==='sb'">
                <i class="las la-user-astronaut" v-if="r.seller_type === 'App\\\\Models\\\\User'" :title="c.val(r)"></i>
                <i class="las la-globe-europe" v-else-if="r.seller_type === 'App\\\\Models\\\\Application'" :title="c.val(r)"></i>
            </template>
            <template v-else>
              {{ c.val(r) }}
            </template>
          </td>
        </tr>
      </tbody>
     </table>
     <p v-else class="text-muted text-center small mb-0 ">{{ t.no_data }}</p>
   </div>
 </div>
</div>`,

        setup() {
            const summaryFlat = Vue.computed(() =>
                bkNow.value === "U"
                    ? summary.value
                    : summary.value.flatMap((g) => [g, ...(g.children || [])])
            );
            const colsSum = Vue.computed(() => colsSummary(bkNow.value));
            const colsExp = Vue.computed(() =>
                summaryOnly.value ? colsSum.value : colsDetail
            );

            Vue.watch(
                summary,
                (newVal) => {
                    console.log("📊 Summary actualizado:", newVal);
                },
                {
                    deep: true,
                }
            );

            const allTotals = Vue.computed(() => {
                let count = 0;
                let amount = 0;
                let webCount = 0,
                    webAmount = 0;
                let officeCount = 0,
                    officeAmount = 0;

                // Calcular desde el summary (que ya está filtrado por breakdown)
                if (summary.value && summary.value.length > 0) {
                    summary.value.forEach((g) => {
                        const groupCount = Number(g.count) || 0;
                        const groupAmount = Number(g.amount) || 0;

                        count += groupCount;
                        amount += groupAmount;

                        // Para breakdown T, todo es TicketOffice
                        if (bkNow.value === "T") {
                            officeCount += groupCount;
                            officeAmount += groupAmount;
                        } else {
                            // Determinar si es TicketOffice o Web
                            const isOffice =
                                g.name && g.name.includes("TicketOffice");
                            if (isOffice) {
                                officeCount += groupCount;
                                officeAmount += groupAmount;
                            } else {
                                webCount += groupCount;
                                webAmount += groupAmount;
                            }
                        }

                        // ❌ NO SUMAR CHILDREN - ya están incluidos en el count del padre
                    });
                }

                return {
                    count,
                    amount,
                    webCount,
                    webAmount,
                    officeCount,
                    officeAmount,
                };
            });

            function exportPdfSummary() {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();

                // Título
                doc.setFontSize(18);
                doc.text(t.title || "Estadísticas de Ventas", 14, 22);
                doc.setFontSize(11);
                doc.setTextColor(100);

                // Lista de eventos/sesiones seleccionadas
                const pageSize = doc.internal.pageSize;
                const pageWidth = pageSize.width
                    ? pageSize.width
                    : pageSize.getWidth();
                let events = "";
                currentSessions.value.forEach((session, index) => {
                    const eventName = tLang(session.event.name);
                    const sessionName = tLang(session.name);
                    events += `${eventName} - ${sessionName} (${fmtDateTime(
                        session.starts_on
                    )})`;
                    if (index < currentSessions.value.length - 1)
                        events += ", ";
                });

                if (events) {
                    const text = doc.splitTextToSize(
                        events,
                        pageWidth - 35,
                        {}
                    );
                    doc.text(text, 14, 30);
                    var nextY = 30 + text.length * 5;
                } else {
                    var nextY = 30;
                }

                // Tabla resumen
                const tableData = [];
                summary.value.forEach((g) => {
                    tableData.push([g.name, g.count, money(g.amount)]);
                    if (g.children && g.children.length) {
                        g.children.forEach((c) => {
                            tableData.push([
                                "  " + c.name,
                                c.count,
                                money(c.amount),
                            ]);
                        });
                    }
                });

                const allRow = [
                    "All",
                    allTotals.value.count,
                    money(allTotals.value.amount),
                ];

                if (
                    bkNow.value !== "T" &&
                    (allTotals.value.webCount > 0 ||
                        allTotals.value.officeCount > 0)
                ) {
                    allRow[1] = `${allTotals.value.count} (web: ${allTotals.value.webCount}, taquilla: ${allTotals.value.officeCount})`;
                    allRow[2] = `${money(allTotals.value.amount)} (web: ${money(
                        allTotals.value.webAmount
                    )}, taquilla: ${money(allTotals.value.officeAmount)})`;
                }

                tableData.push(allRow);

                const colLabel =
                    bkNow.value === "U"
                        ? t.user
                        : bkNow.value === "P"
                        ? t.method
                        : bkNow.value === "T"
                        ? t.ticket_payment
                        : t.rate_pack;

                doc.autoTable({
                    head: [[colLabel, t.quantity, t.price_sold]],
                    body: tableData,
                    startY: nextY,
                    styles: {
                        fontSize: 10,
                    },
                    headStyles: {
                        fillColor: [66, 139, 202],
                    },
                    didParseCell: function (data) {
                        if (data.row.index === tableData.length - 1) {
                            data.cell.styles.fillColor = [240, 240, 240];
                            data.cell.styles.fontStyle = "bold";
                        }
                    },
                    didDrawPage: function (data) {
                        const now = new Date();
                        const dateStr = now.toLocaleString("es-ES");
                        let str = `Generado el ${dateStr}`;

                        if (
                            currentFilters.value.sales_from &&
                            currentFilters.value.sales_to
                        ) {
                            const fromDate = new Date(
                                currentFilters.value.sales_from
                            ).toLocaleDateString("es-ES");
                            const toDate = new Date(
                                currentFilters.value.sales_to
                            ).toLocaleDateString("es-ES");
                            str += ` | Ventas del ${fromDate} al ${toDate}`;
                        }

                        doc.setFontSize(10);
                        const pageHeight = pageSize.height
                            ? pageSize.height
                            : pageSize.getHeight();
                        doc.text(
                            str,
                            data.settings.margin.left,
                            pageHeight - 10
                        );
                    },
                });

                doc.save(`ventas_resumen_${Date.now()}.pdf`);
            }

            function exportPdfDetail() {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF({
                    orientation: "landscape",
                }); // ✅ Horizontal para más columnas

                // Título
                doc.setFontSize(18);
                doc.text(t.title || "Estadísticas de Ventas - Detalle", 14, 22);
                doc.setFontSize(11);
                doc.setTextColor(100);

                // Lista de eventos/sesiones
                const pageSize = doc.internal.pageSize;
                const pageWidth = pageSize.width
                    ? pageSize.width
                    : pageSize.getWidth();
                let events = "";
                currentSessions.value.forEach((session, index) => {
                    const eventName = tLang(session.event.name);
                    const sessionName = tLang(session.name);
                    events += `${eventName} - ${sessionName} (${fmtDateTime(
                        session.starts_on
                    )})`;
                    if (index < currentSessions.value.length - 1)
                        events += ", ";
                });

                if (events) {
                    const text = doc.splitTextToSize(
                        events,
                        pageWidth - 35,
                        {}
                    );
                    doc.text(text, 14, 30);
                    var nextY = 30 + text.length * 5;
                } else {
                    var nextY = 30;
                }

                // Obtener columnas con metadata
                const exportCols = getExportColumns(rows.value);

                // Tabla detalle con metadata
                const tableData = rows.value.map((r) => {
                    return exportCols.map((col) => col.val(r));
                });

                // Headers dinámicos
                const headers = exportCols.map((col) => col.label);

                doc.autoTable({
                    head: [headers],
                    body: tableData,
                    startY: nextY,
                    styles: {
                        fontSize: 8,
                    },
                    headStyles: {
                        fillColor: [66, 139, 202],
                    },
                    columnStyles: {
                        4: { cellWidth: 'auto', minCellWidth: 12 }, // Preu venda - no wrap
                    },
                    didDrawPage: function (data) {
                        const now = new Date();
                        const dateStr = now.toLocaleString("es-ES");
                        let str = `Generado el ${dateStr}`;

                        if (
                            currentFilters.value.sales_from &&
                            currentFilters.value.sales_to
                        ) {
                            const fromDate = new Date(
                                currentFilters.value.sales_from
                            ).toLocaleDateString("es-ES");
                            const toDate = new Date(
                                currentFilters.value.sales_to
                            ).toLocaleDateString("es-ES");
                            str += ` | Ventas del ${fromDate} al ${toDate}`;
                        }

                        doc.setFontSize(10);
                        const pageHeight = pageSize.height
                            ? pageSize.height
                            : pageSize.getHeight();
                        doc.text(
                            str,
                            data.settings.margin.left,
                            pageHeight - 10
                        );
                    },
                });

                doc.save(`ventas_detalle_${Date.now()}.pdf`);
            }

            return {
                t,
                rows,
                summary,
                loading,
                summaryOnly,
                bkNow,
                money,
                colsDetail,
                colsSum,
                colsExp,
                summaryFlat,
                exportCsv,
                exportPdfSummary,
                exportPdfDetail,
                allTotals,
            };
        },
    };

    Vue.createApp(App).mount(el);
    window.addEventListener("sales:generate", (e) => fetchData(e.detail));
}
