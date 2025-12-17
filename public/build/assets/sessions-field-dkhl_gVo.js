(function(){const{createApp:p,reactive:m,computed:v}=Vue;document.querySelectorAll('[id^="sessions-field-"]').forEach(c=>{const n=JSON.parse(c.dataset.props||"{}"),t=m({available:[...n.sessions||[]],selected:[...n.initial||[]]});t.available=t.available.filter(e=>!t.selected.some(s=>String(s.id)===String(e.id)));const r=v(()=>JSON.stringify(t.selected)),a=()=>{const e=c.querySelector('input[type="hidden"]');e&&(e.value=r.value)};a();const u=e=>{b(),t.selected.some(l=>String(l.id)===String(e.id))||t.selected.push(e);const s=t.available.findIndex(l=>String(l.id)===String(e.id));s>-1&&t.available.splice(s,1),a()},g=e=>{b(),t.available.some(l=>String(l.id)===String(e.id))||t.available.push(e);const s=t.selected.findIndex(l=>String(l.id)===String(e.id));s>-1&&t.selected.splice(s,1),a()},b=()=>{typeof bootstrap<"u"&&bootstrap.Tooltip&&document.querySelectorAll(".tooltip.show").forEach(s=>s.remove())},h=()=>{[...t.available].forEach(s=>{t.selected.some(l=>String(l.id)===String(s.id))||t.selected.push(s)}),t.available=[],a()},f=()=>{[...t.selected].forEach(s=>{t.available.some(l=>String(l.id)===String(s.id))||t.available.push(s)}),t.selected=[],a()};p({template:`
      <div class="row g-3">
        <!-- Columna Disponibles -->
        <div class="col-md-5">
          <div class="card h-100">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center">
                <i class="la la-list-ul me-2"></i>
                <strong>{{ translations.available_sessions }}</strong>
                <span class="badge bg-secondary ms-2">{{ available.length }}</span>
              </div>
            </div>
            
            <div class="card-body">
              <!-- Lista de sesiones -->
              <div class="sessions-list" style="max-height: 400px; overflow-y: auto;">
                <div v-if="!available.length" class="text-center text-muted py-3">
                  <i class="la la-inbox la-2x d-block mb-2"></i>
                  {{ translations.no_available }}
                </div>

                <div v-for="s in available" 
                    :key="'av-' + s.id" 
                    class="session-item d-flex align-items-center p-2 mb-1 border rounded hover-bg">
                  <div class="flex-grow-1 text-truncate" :title="s.name">
                    <i class="la la-calendar-check-o text-muted me-1"></i>
                    {{ s.name }}
                  </div>
                  <button 
                    class="btn btn-sm btn-outline-primary ms-2"
                    type="button" 
                    @click="addSession(s)"
                    @mouseenter="showTooltip"
                    @mouseleave="hideTooltip"
                    data-bs-toggle="tooltip"
                    data-bs-placement="left"
                    :title="translations.add_to_pack"
                  >
                    <i class="la la-plus"></i>
                  </button>
                </div>
              </div>

              <!-- Botón añadir todos -->
              <div v-if="available.length > 1" class="mt-3 d-grid">
                <button 
                  class="btn btn-success btn-sm"
                  type="button" 
                  @click="addAll"
                >
                  <i class="la la-angle-double-right me-1"></i>
                  {{ translations.add_all }} ({{ available.length }})
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Columna Central con flechas -->
        <div class="col-md-2 d-flex align-items-center justify-content-center">
          <div class="text-center">
            <div class="mb-3">
              <i class="la la-exchange la-2x text-muted"></i>
            </div>
            <small class="text-muted" v-html="translations.drag_or_buttons"></small>
          </div>
        </div>

        <!-- Columna Seleccionadas -->
        <div class="col-md-5">
          <div class="card h-100">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center">
                <i class="la la-check-square me-2"></i>
                <strong>{{ translations.selected_sessions }}</strong>
                <span class="badge bg-white text-primary ms-2">{{ selected.length }}</span>
              </div>
            </div>
            
            <div class="card-body">
              <!-- Lista de sesiones -->
              <div class="sessions-list" style="max-height: 400px; overflow-y: auto;">
                <div v-if="!selected.length" class="text-center text-muted py-3">
                  <i class="la la-info-circle la-2x d-block mb-2"></i>
                  {{ translations.no_selected }}
                </div>

                <div v-for="s in selected" 
                    :key="'sel-' + s.id" 
                    class="session-item d-flex align-items-center p-2 mb-1 border rounded hover-bg bg-light">
                  <button 
                    class="btn btn-sm btn-outline-danger me-2"
                    type="button" 
                    @click="removeSession(s)"
                    @mouseenter="showTooltip"
                    @mouseleave="hideTooltip"
                    data-bs-toggle="tooltip"
                    data-bs-placement="right"
                    :title="translations.remove_from_pack"
                  >
                    <i class="la la-minus"></i>
                  </button>
                  <div class="flex-grow-1 text-truncate" :title="s.name">
                    <i class="la la-calendar-check-o text-primary me-1"></i>
                    {{ s.name }}
                  </div>
                </div>
              </div>

              <!-- Botón quitar todos -->
              <div v-if="selected.length > 1" class="mt-3 d-grid">
                <button 
                  class="btn btn-danger btn-sm"
                  type="button" 
                  @click="removeAll"
                >
                  <i class="la la-angle-double-left me-1"></i>
                  {{ translations.remove_all }} ({{ selected.length }})
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Hidden input -->
        <input type="hidden" :name="name" :value="jsonSelected" />
      </div>

      <style>
        .hover-bg:hover {
          background-color: rgba(0, 123, 255, 0.05) !important;
          cursor: pointer;
        }
        .session-item {
          transition: all 0.2s ease;
        }
        .session-item:hover {
          transform: translateX(2px);
        }
        .sessions-list::-webkit-scrollbar {
          width: 6px;
        }
        .sessions-list::-webkit-scrollbar-track {
          background: #f1f1f1;
          border-radius: 3px;
        }
        .sessions-list::-webkit-scrollbar-thumb {
          background: #888;
          border-radius: 3px;
        }
        .sessions-list::-webkit-scrollbar-thumb:hover {
          background: #555;
        }
      </style>
      `,setup(){const{onMounted:e,onUnmounted:s,nextTick:l}=Vue,x=n.translations||{};let d=[];const y=()=>{typeof bootstrap<"u"&&bootstrap.Tooltip&&(d.forEach(i=>i.dispose()),d=[],d=[...document.querySelectorAll('[data-bs-toggle="tooltip"]')].map(i=>new bootstrap.Tooltip(i,{trigger:"hover"})))},S=o=>{const i=bootstrap.Tooltip.getInstance(o.target);i&&i.show()},k=o=>{if(!o)typeof bootstrap<"u"&&document.querySelectorAll(".tooltip.show").forEach(w=>w.remove());else{const i=bootstrap.Tooltip.getInstance(o.target);i&&i.hide()}};return e(()=>{l(()=>{y()})}),s(()=>{d.forEach(i=>i.dispose()),document.querySelectorAll(".tooltip.show").forEach(i=>i.remove())}),{...t,addSession:u,removeSession:g,addAll:h,removeAll:f,jsonSelected:r,showTooltip:S,hideTooltip:k,name:n.name||"sessions",translations:x}}}).mount(c)})})();
