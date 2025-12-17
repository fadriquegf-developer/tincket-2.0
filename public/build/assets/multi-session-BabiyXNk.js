(function(){const o=document.getElementById("multiSessionApp");if(!o)return;const{createApp:x,reactive:S,ref:i,computed:p,watch:w,onMounted:E,nextTick:u}=Vue;x({setup(){const j=JSON.parse(o.dataset.events),b=JSON.parse(o.dataset.spaces),z=JSON.parse(o.dataset.tpvs),C=JSON.parse(o.dataset.rates),T=o.dataset.storeUrl,D=o.dataset.indexUrl,O=JSON.parse(o.dataset.trans),f=i(!1),v=i("season"),c=i([{date:"",title:"",start:"",end:""}]),s=S({event_id:"",space_id:"",tpv_id:"",max_places:"",is_numbered:0,season_start:"",season_end:"",weekdays:[],inscription_start:""}),r=i([]),n=i([]);let d=0;const k=30;function q(){return new Promise((e,t)=>{if(typeof jQuery>"u"&&typeof $>"u"){const l=document.createElement("script");l.src="https://code.jquery.com/jquery-3.6.0.min.js",l.onload=()=>{window.jQuery=window.$,a()},l.onerror=()=>t("Error cargando jQuery"),document.head.appendChild(l)}else typeof jQuery>"u"&&(window.jQuery=window.$),a();function a(){if(typeof jQuery.fn.select2<"u"){e();return}const l=document.createElement("link");l.rel="stylesheet",l.href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css",document.head.appendChild(l);const y=document.createElement("link");y.rel="stylesheet",y.href="https://cdn.jsdelivr.net/npm/select2-bootstrap-theme@0.1.0-beta.10/dist/select2-bootstrap.min.css",document.head.appendChild(y);const g=document.createElement("style");g.innerHTML=`
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
                        `,document.head.appendChild(g);const m=document.createElement("script");m.src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.full.min.js",m.onload=()=>e(),m.onerror=()=>t("Error cargando Select2"),document.head.appendChild(m)}})}function h(){return new Promise((e,t)=>{const a=window.jQuery||window.$;if(!a){d<k?(d++,setTimeout(()=>{h().then(e).catch(t)},100)):t("jQuery no disponible después de múltiples intentos");return}if(typeof a.fn.select2<"u"){e(a);return}d<k?(d++,setTimeout(()=>{h().then(e).catch(t)},100)):t("Select2 no disponible después de múltiples intentos")})}async function A(){try{await q();const e=await h();u(()=>{e("#event-select, #space-select, #tpv-select").select2({theme:"bootstrap",width:"100%"}),e("#event-select").on("change",function(){s.event_id=e(this).val()}),e("#space-select").on("change",function(){s.space_id=e(this).val()}),e("#tpv-select").on("change",function(){s.tpv_id=e(this).val()})})}catch(e){console.warn("Select2 no se pudo cargar correctamente:",e)}}const _=p(()=>b.find(e=>e.id==s.space_id)||null),M=p(()=>_.value?_.value.zones:[]),R=p(()=>s.is_numbered==1);w(()=>s.space_id,e=>{if(!e){s.max_places="";return}const t=b.find(a=>a.id==e);t&&t.capacity&&(s.max_places=t.capacity)}),w(()=>s.is_numbered,e=>{u(()=>{const t=window.jQuery||window.$;t&&typeof t.fn.select2<"u"&&(e==1?(t(".zone-select").select2({theme:"bootstrap",width:"100%"}),t(".zone-select").on("change",function(){const a=t(this).data("index");n.value[a].zone_id=t(this).val()})):t(".zone-select").select2("destroy"))})},{immediate:!1});function Q(){r.value.push({title:"",start:"",end:""})}function F(e){r.value.splice(e,1)}function N(){const e={zone_id:"",rate_id:"",price:"",max_on_sale:"",max_per_order:"",is_public:0};n.value.push(e),u(()=>{const t=window.jQuery||window.$;if(t&&typeof t.fn.select2<"u"){const a=n.value.length-1;s.is_numbered==1&&(t(`.zone-select[data-index="${a}"]`).select2({theme:"bootstrap",width:"100%"}),t(`.zone-select[data-index="${a}"]`).on("change",function(){n.value[a].zone_id=t(this).val()})),t(`.rate-select[data-index="${a}"]`).select2({theme:"bootstrap",width:"100%"}),t(`.rate-select[data-index="${a}"]`).on("change",function(){n.value[a].rate_id=t(this).val()})}})}function L(e){n.value.splice(e,1)}function P(){c.value.push({date:"",title:"",start:"",end:""})}function Z(e){c.value.length>1&&c.value.splice(e,1)}async function J(){f.value=!0;try{const e={creation_mode:v.value,event_id:s.event_id,space_id:s.space_id,tpv_id:s.tpv_id,max_places:s.max_places,is_numbered:s.is_numbered,inscription_start:s.inscription_start,rates:n.value};v.value==="season"?(e.season_start=s.season_start,e.season_end=s.season_end,e.weekdays=s.weekdays,e.templates=r.value):e.specific_dates=c.value;const t=await axios.post(T,e);t.data&&t.data.success!==!1?window.location.href=D:alert(t.data.message||"Error al guardar")}catch(e){if(console.error(e),e.response&&e.response.data){const t=e.response.data.errors||{};let a=`Errores de validación:
`;for(const l in t)a+=`- ${t[l].join(", ")}
`;alert(a)}else alert("Error al crear las sesiones")}finally{f.value=!1}}return E(()=>{A()}),{events:j,spaces:b,tpvs:z,ratesCatalog:C,basic:s,templates:r,rates:n,creationMode:v,specificDates:c,selectedSpace:_,availableZones:M,showZoneColumn:R,isSaving:f,addTemplate:Q,removeTemplate:F,addRate:N,removeRate:L,addSpecificDate:P,removeSpecificDate:Z,save:J,trans:O}},template:`
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
</div>`}).mount("#multiSessionApp")})();
