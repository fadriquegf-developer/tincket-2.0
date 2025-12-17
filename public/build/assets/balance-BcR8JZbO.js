document.addEventListener("DOMContentLoaded",()=>{D(),k()});function k(){const c=document.getElementById("balance-filters");if(!c)return;const{filters:t,t:r}=JSON.parse(c.dataset.props||"{}"),s=new Date().toISOString().slice(0,10),f={template:`
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
    `,setup(){const e=Vue.reactive({from:t.from||s,to:t.to||s,breakdown:t.breakdown||"U"}),v=Vue.computed(()=>e.from!==s||e.to!==s||e.breakdown!=="U"),i=a=>a?a.replace(/-/g,""):null;function u(){window.dispatchEvent(new CustomEvent("balance:generate",{detail:{from:i(e.from),to:i(e.to),breakdown:e.breakdown,filters:{from:e.from,to:e.to}}}))}function b(){e.from=s,e.to=s,e.breakdown="U",u()}return Vue.onMounted(u),{filters:e,hasFilters:v,generate:u,reset:b,t:r}}};Vue.createApp(f).mount(c)}function D(){const c=document.getElementById("balance-results");if(!c)return;const{t}=JSON.parse(c.dataset.props||"{}"),r=Vue.ref([]),s=Vue.ref("U"),f=Vue.ref(!1),e=Vue.ref({}),v={U:[{label:t.seller,field:"name"},{label:t.inscriptions??"Inscripciones",field:"count"},{label:t.cash??"Pago en efectiu",field:"totalCash",format:i},{label:t.card??"Pago con tarjeta",field:"totalCard",format:i},{label:t.total??"Total",field:"sum",format:i}],E:[{label:t.event,field:"name"},{label:t.inscriptions??"Inscripciones",field:"count"},{label:t.total??"Total",field:"sum",format:i}],P:[{label:t.promoter,field:"name"},{label:t.inscriptions??"Inscripciones",field:"count"},{label:t.total??"Total",field:"sum",format:i}]};function i(a){return new Intl.NumberFormat("es-ES",{style:"currency",currency:"EUR"}).format(a??0)}async function u(a){f.value=!0,s.value=a.breakdown,e.value=a.filters||{},r.value=[];try{const{data:m}=await axios.get("/api/statistics/balance",{params:a});r.value=m}catch(m){console.error(m),alert("Error al obtener datos")}finally{f.value=!1}}const b={template:`
      <div class="card rounded border-xs shadow-xs">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
           <h3 class="card-title mb-0">{{ t.results }}</h3>
           <div class="btn-group">
            <button class="btn btn-outline-secondary btn-sm"
                   @click="exportPdf"
                   :disabled="!results.length">
                <i class="la la-file-pdf me-1"></i> PDF
            </button>
            <button class="btn btn-outline-secondary btn-sm"
                   @click="exportCsv"
                   :disabled="!results.length">
                <i class="la la-download me-1"></i> CSV
            </button>
           </div>
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
    `,setup(){const a=Vue.computed(()=>v[s.value]??[]);function m(){if(!r.value.length)return;const w=a.value.map(l=>l.label),o=r.value.map(l=>a.value.map(n=>n.format?n.format(l[n.field]):l[n.field])),p=[w,...o].map(l=>l.map(n=>`"${String(n).replace(/"/g,'""')}"`).join(";")).join(`
`),g=new Blob([p],{type:"text/csv;charset=utf-8;"}),d=document.createElement("a");d.href=URL.createObjectURL(g),d.download=`balance_${s.value}_${Date.now()}.csv`,d.click(),URL.revokeObjectURL(d.href)}function x(){if(!r.value.length)return;const{jsPDF:w}=window.jspdf,o=new w;o.setFontSize(18),o.text(t.title||"Estadísticas de Balance",14,22),o.setFontSize(11),o.setTextColor(100);let p=30;if(e.value.from&&e.value.to){const l=new Date(e.value.from).toLocaleDateString("es-ES"),n=new Date(e.value.to).toLocaleDateString("es-ES");o.text(`Período: ${l} - ${n}`,14,p),p+=10}const g=r.value.map(l=>a.value.map(n=>n.format?n.format(l[n.field]):l[n.field])),d=a.value.map(l=>l.label);o.autoTable({head:[d],body:g,startY:p,styles:{fontSize:10},headStyles:{fillColor:[66,139,202]},didDrawPage:function(l){const y=`Generado el ${new Date().toLocaleString("es-ES")}`;o.setFontSize(10);const h=o.internal.pageSize,S=h.height?h.height:h.getHeight();o.text(y,l.settings.margin.left,S-10)}}),o.save(`balance_${s.value}_${Date.now()}.pdf`)}return{results:r,columns:a,loading:f,t,exportCsv:m,exportPdf:x}}};Vue.createApp(b).mount(c),window.addEventListener("balance:generate",a=>u(a.detail))}
