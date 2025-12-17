document.addEventListener("DOMContentLoaded",()=>{H(),J()});const A=u=>new Intl.NumberFormat("es-ES",{style:"currency",currency:"EUR"}).format(Number(u)||0);function b(u){if(u==null)return"";if(typeof u=="string")return u;const m=window.appLocale||"es";return u[m]??Object.values(u)[0]??""}function P(u){if(!u)return"";const m=typeof u=="string"&&u.includes(" ")&&!u.includes("T")?u.replace(" ","T"):u,h=new Date(m);return Number.isNaN(h.getTime())?u:h.toLocaleString("es-ES",{day:"2-digit",month:"2-digit",year:"numeric",hour:"2-digit",minute:"2-digit"}).replace(",","")}function W(u){var h,g;if(u.payment_method!=="TicketOffice"&&((g=(h=u.cart)==null?void 0:h.confirmed_payment)==null?void 0:g.gateway)!=="TicketOffice")return"";const m=u.ticket_payment_type||(()=>{var d,E;try{return JSON.parse(((E=(d=u.cart)==null?void 0:d.confirmed_payment)==null?void 0:E.gateway_response)||"{}").payment_type||""}catch{return""}})();return m==="cash"?b({es:"En efectivo",ca:"En efectiu"}):m==="card"?b({es:"Tarjeta de crédito",ca:"Targeta de crèdit"}):m||"NA"}function B(u){var h,g;if((u.payment_method||((g=(h=u.cart)==null?void 0:h.confirmed_payment)==null?void 0:g.gateway))==="TicketOffice"){const d=u.ticket_payment_type;return d==="cash"?b({es:"Efectivo",ca:"Efectiu"}):d==="card"?b({es:"Tarjeta",ca:"Targeta"}):"TicketOffice"}return b({es:"Tarjeta",ca:"Targeta"})}function J(){const u=document.getElementById("sales-filters");if(!u)return;const{t:m}=JSON.parse(u.dataset.props||"{}"),h=new Date().toISOString().slice(0,10),g={template:`
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
</div>`,setup(){const d=Vue.reactive({session_ids:[],sessions_from:null,sessions_to:null,sales_from:h,sales_to:h,breakdown:"R",only_summary:!1}),E=Vue.ref([]);Vue.onMounted(async()=>{const{data:_}=await axios.get("/api/session?with_sales=1&show_expired=1");E.value=_.data??_.sessions??_});const T=Vue.computed(()=>E.value.filter(_=>{const N=_.starts_on.slice(0,10);return!(d.sessions_from&&N<d.sessions_from||d.sessions_to&&N>d.sessions_to)})),O=Vue.computed(()=>E.value.filter(_=>d.session_ids.includes(_.id))),C=_=>{d.session_ids=d.session_ids.filter(N=>N!==_)},$=()=>{d.session_ids=T.value.map(_=>_.id)};function F(){const _=d.session_ids.join(","),N=`{"from":${new Date(d.sales_from).getTime()},"to":${new Date(d.sales_to).getTime()}}`;return`/api/statistics/sales?session_id=${_}&breakdown=${d.breakdown}&sales_range=${N}`}const V=()=>{const _=E.value.filter(N=>d.session_ids.includes(N.id));window.dispatchEvent(new CustomEvent("sales:generate",{detail:{url:F(),summaryOnly:d.only_summary,bk:d.breakdown,t:m,sessions:_,filters:{sales_from:d.sales_from,sales_to:d.sales_to}}}))};return Vue.watch([()=>d.sessions_from,()=>d.sessions_to],()=>{d.session_ids=d.session_ids.filter(_=>T.value.some(N=>N.id===_))}),{filters:d,filteredSessions:T,selectedSessionsDisplay:O,removeSession:C,selectAll:$,generate:V,t:m,tLang:b,fmtDateTime:P}}};Vue.createApp(g).mount(u)}function H(){const u=document.getElementById("sales-results");if(!u)return;const{t:m}=JSON.parse(u.dataset.props||"{}"),h=Vue.ref([]),g=Vue.ref([]),d=Vue.ref(!1),E=Vue.ref(!1),T=Vue.ref("R"),O=Vue.ref([]),C=Vue.ref({}),$=[{label:m.event,key:"ev",val:s=>{var r,e;return b(s.event_name??((e=(r=s.session)==null?void 0:r.event)==null?void 0:e.name))}},{label:m.session,key:"se",val:s=>{var l,c;const r=b(((l=s.session)==null?void 0:l.name)??s.session_name),e=P(((c=s.session)==null?void 0:c.starts_on)??s.session_starts_on);return!r||r==="null"||r.trim()===""?e||"":r+(e?" ("+e+")":"")}},{label:m.sold_at,key:"sa",val:s=>P(s.paid_at)},{label:m.rate_pack,key:"rp",val:s=>{var r,e,l;return(e=(r=s.group_pack)==null?void 0:r.pack)!=null&&e.name?b(s.group_pack.pack.name):s.pack_name?b(s.pack_name):(l=s.rate)!=null&&l.name?b(s.rate.name):s.rate_name?b(s.rate_name):""}},{label:m.price_sold,key:"ps",val:s=>A(s.price_sold)},{label:m.cart,key:"ca",val:s=>s.confirmation_code??""},{label:m.client,key:"cl",val:s=>{var r,e;return((e=(r=s.cart)==null?void 0:r.client)==null?void 0:e.email)??s.client_email??""}},{label:m.sold_by,key:"sb",val:s=>{var r,e,l,c;return((e=(r=s.cart)==null?void 0:r.seller)==null?void 0:e.name)??((c=(l=s.cart)==null?void 0:l.seller)==null?void 0:c.code_name)??s.seller_name??""}},{label:m.payment_method||"Método de pago",key:"pm",val:s=>B(s)}];function F(s){const r=new Set;return s.forEach(l=>{if(l.inscription_metadata)try{const c=typeof l.inscription_metadata=="string"?JSON.parse(l.inscription_metadata):l.inscription_metadata;Object.keys(c).forEach(p=>r.add(p))}catch(c){console.error("❌ Error parseando metadata:",c)}}),Array.from(r).sort()}function V(s){const e=F(s).map(l=>({label:l,key:`meta_${l}`,val:c=>{if(!c.inscription_metadata)return"";try{return(typeof c.inscription_metadata=="string"?JSON.parse(c.inscription_metadata):c.inscription_metadata)[l]??""}catch{return""}}}));return[...$,...e]}function _(s,r){if(s==="T"){const e=new Map;return r.filter(l=>{var c,p;return l.payment_method==="TicketOffice"||((p=(c=l.cart)==null?void 0:c.confirmed_payment)==null?void 0:p.gateway)==="TicketOffice"}).forEach(l=>{const c=W(l);e.has(c)||e.set(c,{name:c,count:0,amount:0});const p=e.get(c);p.count++,p.amount+=Number(l.price_sold)||0}),[...e.values()]}if(s==="U"){const e=new Map;return r.forEach(c=>{var o,y,v,k,w,x;const p=((y=(o=c.cart)==null?void 0:o.seller)==null?void 0:y.name)??((k=(v=c.cart)==null?void 0:v.seller)==null?void 0:k.code_name)??c.seller_name??"";e.has(p)||e.set(p,{name:p,seller_type:c.seller_type,count:0,amount:0,cash:0,card:0});const t=e.get(p);t.count++,t.amount+=Number(c.price_sold)||0,(c.payment_method||((x=(w=c.cart)==null?void 0:w.confirmed_payment)==null?void 0:x.gateway))==="TicketOffice"&&c.ticket_payment_type==="cash"?t.cash++:t.card++}),[...e.values()]}if(s==="P"){const e=new Map,l=t=>{var n,o;return t.payment_method??((o=(n=t.cart)==null?void 0:n.confirmed_payment)==null?void 0:o.gateway)??"—"},c=t=>t.group_pack_id!=null||!!t.pack_name,p=t=>{var o,y,v;return c(t)?b(((y=(o=t.group_pack)==null?void 0:o.pack)==null?void 0:y.name)??t.pack_name??"—")+" (Pack)":b(((v=t.rate)==null?void 0:v.name)??t.rate_name??"—")};return r.forEach(t=>{const n=l(t),o=p(t);e.has(n)||e.set(n,{name:n,count:0,amount:0,children:new Map});const y=e.get(n);y.count++,y.amount+=Number(t.price_sold)||0,y.children.has(o)||y.children.set(o,{name:o,count:0,amount:0});const v=y.children.get(o);v.count++,v.amount+=Number(t.price_sold)||0}),[...e.values()].map(t=>({...t,children:[...t.children.values()]}))}{const e=i=>i.group_pack_id!=null||!!i.pack_name,l=i=>{var a;return b(((a=i.rate)==null?void 0:a.name)??i.rate_name??"—")},c=i=>{var a,f;return b(((f=(a=i.group_pack)==null?void 0:a.pack)==null?void 0:f.name)??i.pack_name??"—")},p=i=>{var a,f;return i.payment_method??((f=(a=i.cart)==null?void 0:a.confirmed_payment)==null?void 0:f.gateway)??"NA"},t=new Map;r.filter(i=>!e(i)).forEach(i=>{const a=l(i);t.has(a)||t.set(a,{name:a,count:0,amount:0,children:new Map});const f=t.get(a);f.count++,f.amount+=Number(i.price_sold)||0;const D=p(i);f.children.has(D)||f.children.set(D,{name:D,count:0,amount:0});const S=f.children.get(D);S.count++,S.amount+=Number(i.price_sold)||0});const n=[...t.values()].map(i=>({...i,children:[...i.children.values()]})),o=new Map;let y=0,v=0;r.filter(e).forEach(i=>{const a=c(i),f=p(i),D=a+"|"+f;o.has(D)||o.set(D,{name:a+" ("+f+")",count:0,amount:0,_set:new Set});const S=o.get(D);S.count++,S.amount+=Number(i.price_sold)||0,y++,v+=Number(i.price_sold)||0,i.group_pack_id!=null&&S._set.add(i.group_pack_id)});const k=[...o.values()].map(i=>{const a=i._set.size;return delete i._set,{...i,nPacks:a}}),w=k.reduce((i,a)=>i+(a.nPacks||0),0),x={name:"Packs",count:y,amount:v,nPacks:w,children:k};return[...n,x]}}function N(s){return[{label:s==="U"?m.user:s==="P"?m.method:s==="T"?m.ticket_payment:m.rate_pack,key:"n",val:e=>e.name},{label:m.quantity,key:"q",val:e=>e.count},{label:m.price_sold,key:"p",val:e=>A(e.amount)}]}async function R({url:s,summaryOnly:r,bk:e,sessions:l,filters:c}){E.value=r,T.value=e,O.value=l||[],C.value=c||{},d.value=!0,h.value=[],g.value=[];try{const{data:p}=await axios.get(s);h.value=p.results??[],Array.isArray(p.summary)&&p.summary.length?g.value=e==="T"?p.summary.map(t=>({...t,name:t.name==="cash"?b({es:"En efectivo",ca:"En efectiu"}):t.name==="card"?b({es:"Tarjeta de crédito",ca:"Targeta de crèdit"}):t.name||"NA"})):p.summary:g.value=_(e,h.value)}catch(p){console.error(p)}finally{d.value=!1}}function z(s,r){const e=document.createElement("a");e.href=URL.createObjectURL(s),e.download=r,e.click(),URL.revokeObjectURL(e.href)}function U(s,r,e=!1){const l=e?V(r):s,c=[l.map(t=>t.label),...r.map(t=>l.map(n=>{let o=n.val(t);if(n.key==="sa"&&t.paid_at){const y=new Date(t.paid_at);isNaN(y)||(o=y.toLocaleString("es-ES",{day:"2-digit",month:"2-digit",year:"numeric",hour:"2-digit",minute:"2-digit"}))}return typeof o=="string"&&o.includes("€")&&(o=o.replace(/[^\d,.-]/g,"").replace(",",".")),o}))].map(t=>t.map(n=>{const o=Number(n);return!isNaN(o)&&n!==""&&n!==null?o:`"${String(n).replace(/"/g,'""')}"`}).join(";")).join(`
`),p="\uFEFF";z(new Blob([p+c],{type:"text/csv;charset=utf-8;"}),`sales_${Date.now()}.csv`)}const q={template:`
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
</div>`,setup(){const s=Vue.computed(()=>T.value==="U"?g.value:g.value.flatMap(t=>[t,...t.children||[]])),r=Vue.computed(()=>N(T.value)),e=Vue.computed(()=>E.value?r.value:$);Vue.watch(g,t=>{console.log("📊 Summary actualizado:",t)},{deep:!0});const l=Vue.computed(()=>{let t=0,n=0,o=0,y=0,v=0,k=0;return g.value&&g.value.length>0&&g.value.forEach(w=>{const x=Number(w.count)||0,i=Number(w.amount)||0;t+=x,n+=i,T.value==="T"||w.name&&w.name.includes("TicketOffice")?(v+=x,k+=i):(o+=x,y+=i)}),{count:t,amount:n,webCount:o,webAmount:y,officeCount:v,officeAmount:k}});function c(){const{jsPDF:t}=window.jspdf,n=new t;n.setFontSize(18),n.text(m.title||"Estadísticas de Ventas",14,22),n.setFontSize(11),n.setTextColor(100);const o=n.internal.pageSize,y=o.width?o.width:o.getWidth();let v="";if(O.value.forEach((a,f)=>{const D=b(a.event.name),S=b(a.name);v+=`${D} - ${S} (${P(a.starts_on)})`,f<O.value.length-1&&(v+=", ")}),v){const a=n.splitTextToSize(v,y-35,{});n.text(a,14,30);var k=30+a.length*5}else var k=30;const w=[];g.value.forEach(a=>{w.push([a.name,a.count,A(a.amount)]),a.children&&a.children.length&&a.children.forEach(f=>{w.push(["  "+f.name,f.count,A(f.amount)])})});const x=["All",l.value.count,A(l.value.amount)];T.value!=="T"&&(l.value.webCount>0||l.value.officeCount>0)&&(x[1]=`${l.value.count} (web: ${l.value.webCount}, taquilla: ${l.value.officeCount})`,x[2]=`${A(l.value.amount)} (web: ${A(l.value.webAmount)}, taquilla: ${A(l.value.officeAmount)})`),w.push(x);const i=T.value==="U"?m.user:T.value==="P"?m.method:T.value==="T"?m.ticket_payment:m.rate_pack;n.autoTable({head:[[i,m.quantity,m.price_sold]],body:w,startY:k,styles:{fontSize:10},headStyles:{fillColor:[66,139,202]},didParseCell:function(a){a.row.index===w.length-1&&(a.cell.styles.fillColor=[240,240,240],a.cell.styles.fontStyle="bold")},didDrawPage:function(a){let S=`Generado el ${new Date().toLocaleString("es-ES")}`;if(C.value.sales_from&&C.value.sales_to){const j=new Date(C.value.sales_from).toLocaleDateString("es-ES"),M=new Date(C.value.sales_to).toLocaleDateString("es-ES");S+=` | Ventas del ${j} al ${M}`}n.setFontSize(10);const L=o.height?o.height:o.getHeight();n.text(S,a.settings.margin.left,L-10)}}),n.save(`ventas_resumen_${Date.now()}.pdf`)}function p(){const{jsPDF:t}=window.jspdf,n=new t({orientation:"landscape"});n.setFontSize(18),n.text(m.title||"Estadísticas de Ventas - Detalle",14,22),n.setFontSize(11),n.setTextColor(100);const o=n.internal.pageSize,y=o.width?o.width:o.getWidth();let v="";if(O.value.forEach((a,f)=>{const D=b(a.event.name),S=b(a.name);v+=`${D} - ${S} (${P(a.starts_on)})`,f<O.value.length-1&&(v+=", ")}),v){const a=n.splitTextToSize(v,y-35,{});n.text(a,14,30);var k=30+a.length*5}else var k=30;const w=V(h.value),x=h.value.map(a=>w.map(f=>f.val(a))),i=w.map(a=>a.label);n.autoTable({head:[i],body:x,startY:k,styles:{fontSize:8},headStyles:{fillColor:[66,139,202]},columnStyles:{4:{cellWidth:"auto",minCellWidth:12}},didDrawPage:function(a){let S=`Generado el ${new Date().toLocaleString("es-ES")}`;if(C.value.sales_from&&C.value.sales_to){const j=new Date(C.value.sales_from).toLocaleDateString("es-ES"),M=new Date(C.value.sales_to).toLocaleDateString("es-ES");S+=` | Ventas del ${j} al ${M}`}n.setFontSize(10);const L=o.height?o.height:o.getHeight();n.text(S,a.settings.margin.left,L-10)}}),n.save(`ventas_detalle_${Date.now()}.pdf`)}return{t:m,rows:h,summary:g,loading:d,summaryOnly:E,bkNow:T,money:A,colsDetail:$,colsSum:r,colsExp:e,summaryFlat:s,exportCsv:U,exportPdfSummary:c,exportPdfDetail:p,allTotals:l}}};Vue.createApp(q).mount(u),window.addEventListener("sales:generate",s=>R(s.detail))}
