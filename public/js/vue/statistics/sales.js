/* ========================================================================
 *  Estadísticas de VENTAS  —  Backpack 6 · Vue 3 · Axios
 * ====================================================================== */
document.addEventListener('DOMContentLoaded', () => {
  mountResults();   // primero el listener
  mountFilters();   // luego los filtros
});

/* ─ utilidades ─────────────────────────────────────────────────────────── */
const money = v =>
  new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' })
      .format(Number(v) || 0);

function tLang(v){                         /* string | {es:'', ca:''} */
  if(v==null) return '';
  if(typeof v==='string') return v;
  const lang = window.appLocale || 'es';
  return v[lang] ?? Object.values(v)[0] ?? '';
}

function fmtDateTime(iso){                 /* «30/05/2025 20:00» */
  if(!iso) return '';
  return new Date(iso).toLocaleString('es-ES',{
    day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'
  }).replace(',','');
}

/* Quick helper para TicketOffice ---------------------------------------- */
function ticketPaymentType(r){
  if(r.cart?.confirmed_payment?.gateway !== 'TicketOffice') return '';
  try{
    const obj = JSON.parse(r.cart.confirmed_payment.gateway_response||'{}');
    switch(obj.payment_type){
      case 'card' : return tLang({es:'Tarjeta de crédito', ca:'Targeta de crèdit'});
      case 'cash' : return tLang({es:'En efectivo',        ca:'En efectiu'});
      default     : return obj.payment_type||'';
    }
  }catch{ return ''; }
}

/* ─────────────────────────────── FILTROS ──────────────────────────────── */
function mountFilters(){
  const el=document.getElementById('sales-filters'); if(!el) return;
  const { t }=JSON.parse(el.dataset.props||'{}');
  const today=new Date().toISOString().slice(0,10);

  const App={
    template: /*html*/`
    <nav class="navbar navbar-expand-lg bp-filters-navbar p-0 rounded border-xs shadow-xs">
    <div class="container-fluid flex-wrap gap-3">

        <!-- ▼ Sesiones -->
        <div class="nav-item dropdown me-3">
            <a href="#" class="nav-link dropdown-toggle px-2" data-bs-toggle="dropdown">
                {{ t.sessions }}
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
</nav>`,

    setup(){
      const filters=Vue.reactive({
        session_ids:[],sessions_from:null,sessions_to:null,
        sales_from:today,sales_to:today,breakdown:'R',only_summary:false
      });
      const sessions=Vue.ref([]);

      Vue.onMounted(async()=>{
        const {data}=await axios.get('/api/session?with_sales=1&show_expired=1');
        sessions.value=data.data??data.sessions??data;
      });

      const filteredSessions=Vue.computed(()=>sessions.value.filter(s=>{
        const d=s.starts_on.slice(0,10);
        if(filters.sessions_from&&d<filters.sessions_from) return false;
        if(filters.sessions_to  &&d>filters.sessions_to)   return false;
        return true;}));
      const selectAll=()=>{filters.session_ids=filteredSessions.value.map(s=>s.id);};

      function buildUrl(){
        const ids=filters.session_ids.join(',');
        const range=`{"from":${new Date(filters.sales_from).getTime()},"to":${new Date(filters.sales_to).getTime()}}`;
        return `/api/statistics/sales?session_id=${ids}`
             + `&breakdown=${filters.breakdown}&summary=${filters.only_summary?1:0}`
             + `&sales_range=${range}`;
      }
      const generate=()=>window.dispatchEvent(new CustomEvent('sales:generate',{
        detail:{url:buildUrl(),summaryOnly:filters.only_summary,bk:filters.breakdown,t}
      }));

      Vue.watch([()=>filters.sessions_from,()=>filters.sessions_to],
        ()=>{filters.session_ids=filters.session_ids.filter(id=>filteredSessions.value.some(s=>s.id===id));});
      return{filters,filteredSessions,selectAll,generate,t,tLang,fmtDateTime};
    }
  };
  Vue.createApp(App).mount(el);
}

/* ───────────────────────────── RESULTADOS ─────────────────────────────── */
function mountResults(){
  const el=document.getElementById('sales-results'); if(!el) return;
  const {t}=JSON.parse(el.dataset.props||'{}');

  const rows=Vue.ref([]);
  const summary=Vue.ref([]);
  const loading=Vue.ref(false);
  const summaryOnly=Vue.ref(false);
  const bkNow=Vue.ref('R');

  /* columnas detalle (fijas) ----------------------------------------------*/
  const colsDetail=[
    {label:t.event  ,key:'ev',val:r=>tLang(r.session?.event?.name??r.event_name)},
    {label:t.session,key:'se',val:r=>{
      const n=tLang(r.session?.name??r.session_name);
      const d=fmtDateTime(r.session?.starts_on??r.session_starts_on);
      return n+(d?' ('+d+')':'');}},
    {label:t.sold_at,key:'sa',val:r=>r.cart?.confirmed_payment?.paid_at??''},
    {label:t.rate_pack,key:'rp',val:r=>{
      if(r.rate?.name)             return tLang(r.rate.name);
      if(r.rate_name)              return tLang(r.rate_name);
      if(r.group_pack?.pack?.name) return tLang(r.group_pack.pack.name);
      if(r.pack_name)              return tLang(r.pack_name);
      return '';}},
    {label:t.price_sold,key:'ps',val:r=>money(r.price_sold)},
    {label:t.cart   ,key:'ca',val:r=>r.cart?.confirmation_code??''},
    {label:t.client ,key:'cl',val:r=>r.cart?.client?.email??r.client_email??''},
    {label:t.sold_by,key:'sb',val:r=>r.cart?.seller?.name??r.cart?.seller?.code_name??r.seller_name??''}
  ];

  /* construir resumen ------------------------------------------------------*/
  function buildSummary(bk,arr){
    /* --- Pago taquilla --- (una fila por tipo de pago dentro de TicketOffice) */
    if(bk==='T'){
      const map=new Map();
      arr.filter(r=>r.cart?.confirmed_payment?.gateway==='TicketOffice')
         .forEach(r=>{
           const k=ticketPaymentType(r);
           if(!map.has(k)) map.set(k,{name:k,count:0,amount:0});
           const g=map.get(k); g.count++; g.amount+=Number(r.price_sold)||0;
         });
      return [...map.values()];
    }

    /* --- Usuario --- */
    if(bk==='U'){
      const map=new Map();
      arr.forEach(r=>{
        const n=r.cart?.seller?.name??r.cart?.seller?.code_name??r.seller_name??'';
        if(!map.has(n)) map.set(n,{name:n,count:0,amount:0});
        const g=map.get(n); g.count++; g.amount+=Number(r.price_sold)||0;
      });
      return [...map.values()];
    }

    /* --- Tarifa (R)   o   Método pago (P) ---  con hijos ------------------*/
    const map=new Map();
    const keyTop = bk==='P'
      ? r=>r.payment_method??r.cart?.confirmed_payment?.gateway??'—'
      : r=>tLang(r.rate?.name??r.rate_name??
                 r.group_pack?.pack?.name??r.pack_name??'—');
    const keyChild = bk==='P'
      ? r=>tLang(r.rate?.name??r.rate_name??
                 r.group_pack?.pack?.name??r.pack_name??'—')
      : r=>r.payment_method??r.cart?.confirmed_payment?.gateway??'—';

    arr.forEach(r=>{
      const a=keyTop(r), b=keyChild(r);
      if(!map.has(a)) map.set(a,{name:a,count:0,amount:0,children:new Map()});
      const g=map.get(a); g.count++; g.amount+=Number(r.price_sold)||0;
      if(!g.children.has(b)) g.children.set(b,{name:b,count:0,amount:0});
      const c=g.children.get(b); c.count++; c.amount+=Number(r.price_sold)||0;
    });

    return [...map.values()].map(g=>({...g,children:[...g.children.values()]}));
  }

  /* columnas resumen según breakdown --------------------------------------*/
  function colsSummary(bk){
    const labelName = bk==='U' ? t.user
                     : bk==='P' ? t.method
                     : bk==='T' ? t.ticket_payment
                     : t.rate_pack;
    return [
      {label:labelName        ,key:'n',val:g=>g.name},
      {label:t.quantity  ,key:'q',val:g=>g.count},
      {label:t.price_sold     ,key:'p',val:g=>money(g.amount)}
    ];
  }

  /* fetch ------------------------------------------------------------------*/
  async function fetchData({url,summaryOnly:so,bk}){
    summaryOnly.value=so;
    bkNow.value=bk;
    loading.value=true; rows.value=[]; summary.value=[];
    try{
      const {data}=await axios.get(url);
      rows.value=data.results??[];
      summary.value=buildSummary(bk,rows.value);
    }catch(e){console.error(e);}
    finally{loading.value=false;}
  }

  /* export helpers ---------------------------------------------------------*/
  function dl(blob,name){
    const a=document.createElement('a');
    a.href=URL.createObjectURL(blob);a.download=name;a.click();
    URL.revokeObjectURL(a.href);}

  function exportCsv(cols,data){
    const csv=[cols.map(c=>c.label),...data.map(r=>cols.map(c=>c.val(r)))]
      .map(r=>r.map(v=>`"${String(v).replace(/"/g,'""')}"`).join(';')).join('\n');
    dl(new Blob([csv],{type:'text/csv'}),`sales_${Date.now()}.csv`);}

  function exportPdf(cols,data){
    const { jsPDF } = window.jspdf || {};
    if(!jsPDF){ alert('jsPDF no cargado'); return; }

    const doc = new jsPDF('p','pt');
    doc.setFontSize(14);
    doc.text('Estadísticas de ventas', 40, 40);

    /* construimos un array de arrays: [ [col1,col2,…], … ] */
    const body = data.map(r => cols.map(c => String(c.val(r))));
    doc.autoTable({
      head:[cols.map(c=>c.label)],
      body,
      startY: 60,
      styles:{fontSize:8,cellPadding:3}
    });

    doc.save(`sales_${Date.now()}.pdf`);
  }

  /* componente -------------------------------------------------------------*/
  const App={
    template:/*html*/`
<div class="card rounded border-xs shadow-xs">
 <div class="card-header py-3 d-flex justify-content-between align-items-center">
   <h3 class="card-title mb-0">{{ t.results }}</h3>
   <div class="btn-group">
     <button class="btn btn-outline-secondary btn-sm me-2"
             @click="exportCsv(colsExp, summaryOnly?summaryFlat:rows)"
             :disabled="!summary.length && !rows.length">{{ t.csv }}</button>
     <button class="btn btn-outline-secondary btn-sm"
             @click="exportPdf(colsExp , summaryOnly?summaryFlat:rows)"
             :disabled="!summary.length && !rows.length">PDF</button>
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
           <td>{{ g.name }}</td><td>{{ g.count }}</td><td>{{ money(g.amount) }}</td>
         </tr>
         <tr v-for="(c,j) in g.children||[]" :key="j">
           <td class="ps-4">{{ c.name }}</td><td>{{ c.count }}</td><td>{{ money(c.amount) }}</td>
         </tr>
       </template>
       <tr class="table-active fw-bold">
         <td>All</td><td>{{ rows.length }}</td>
         <td>{{ money(rows.reduce((s,r)=>s+Number(r.price_sold||0),0)) }}</td>
       </tr>
     </tbody>
   </table>

   <!-- Detalle --------------------------------------------------------->
   <div v-if="!summaryOnly" class="table-responsive">
     <table v-if="rows.length" class="table table-striped mb-0">
       <thead><tr><th v-for="c in colsDetail" :key="c.key">{{ c.label }}</th></tr></thead>
       <tbody><tr v-for="(r,i) in rows" :key="i">
         <td v-for="c in colsDetail" :key="c.key">{{ c.val(r) }}</td>
       </tr></tbody>
     </table>
     <p v-else class="text-muted text-center small mb-0 ">{{ t.no_data }}</p>
   </div>

 </div>
</div>`,

    setup(){
      const summaryFlat=Vue.computed(()=> bkNow.value==='U'
           ? summary.value
           : summary.value.flatMap(g=>[g,...(g.children||[])]));
      const colsSum=Vue.computed(()=>colsSummary(bkNow.value));
      const colsExp=Vue.computed(()=> summaryOnly.value ? colsSum.value : colsDetail);
      return{
        t, rows, summary, loading, summaryOnly, money,
        colsDetail, colsSum, colsExp, summaryFlat,
        exportCsv, exportPdf
      };
    }
  };

  Vue.createApp(App).mount(el);
  window.addEventListener('sales:generate', e=>fetchData(e.detail));
}
