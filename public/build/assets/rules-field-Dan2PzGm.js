(function(){const{createApp:r,reactive:c,watch:p,computed:u}=Vue;document.querySelectorAll('[id^="rules-field-"]').forEach(l=>{const s=JSON.parse(l.dataset.props),d=s.t||{},m=Array.isArray(s.initial)?s.initial.map(t=>({number_sessions:t.number_sessions??null,percent_pack:t.percent_pack??null,price_pack:t.price_pack??null,all_sessions:t.all_sessions??!1})):[{number_sessions:null,percent_pack:null,price_pack:null,all_sessions:!1}],e=c({rules:m});function n(){const t=l.querySelector('input[type="hidden"]');t&&(t.value=JSON.stringify(e.rules))}r({template:`
      <div>
        <div class="table-responsive">
          <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
              <tr>
                <th scope="col" class="text-nowrap">{{ t.sessions }}</th>
                <th scope="col" class="text-nowrap">{{ t.discount_percent }}</th>
                <th scope="col" class="text-nowrap">{{ t.price }}</th>
                <th scope="col" class="text-center" style="width: 120px;">{{ t.actions }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(rule, index) in rules" :key="index">
                <td>
                  <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text">
                      <i class="la la-calendar-check-o"></i>
                    </span>
                    <input 
                      type="number" 
                      v-model.number="rule.number_sessions" 
                      class="form-control form-control-sm"
                      min="1"
                    />
                  </div>
                  <div class="form-check form-switch">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      role="switch"
                      v-model="rule.all_sessions"
                      :id="'all-remaining-' + index"
                    />
                    <label class="form-check-label small" :for="'all-remaining-' + index">
                      {{ t.all_remaining }}
                    </label>
                  </div>
                </td>
                <td class="align-top">
                  <div class="input-group input-group-sm">
                    <input 
                      type="number" 
                      v-model.number="rule.percent_pack" 
                      class="form-control form-control-sm" 
                      step="0.5"
                      min="0"
                      max="100"
                      placeholder="0.00"
                    />
                    <span class="input-group-text">%</span>
                  </div>
                </td>
                <td class="align-top">
                  <div class="input-group input-group-sm">
                    <input 
                      type="number" 
                      v-model.number="rule.price_pack" 
                      class="form-control form-control-sm" 
                      step="0.10"
                      min="0"
                      placeholder="0.00"
                    />
                    <span class="input-group-text">€</span>
                  </div>
                </td>
                <td class="text-center align-top">
                  <button
                    type="button"
                    class="btn btn-danger btn-sm"
                    @click="removeRule(index)"
                    :disabled="rules.length === 1"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    :title="rules.length === 1 ? '' : 'Eliminar regla'"
                  >
                    <i class="la la-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mt-3">
          <button
            type="button"
            class="btn btn-outline-primary btn-sm"
            @click="addRule"
          >
            <i class="la la-plus me-1"></i>
            {{ t.add_rule || 'Añadir regla' }}
          </button>
          
          <small class="text-muted">
            <i class="la la-info-circle"></i>
            Total de reglas: {{ rules.length }}
          </small>
        </div>

        <input type="hidden" :name="name" :value="jsonRules" />
      </div>
      `,setup(){function t(){e.rules.push({number_sessions:null,percent_pack:null,price_pack:null,all_sessions:!1}),n(),i(()=>{a()})}function b(o){e.rules.length>1&&(e.rules.splice(o,1),n())}function a(){typeof bootstrap<"u"&&bootstrap.Tooltip&&[...document.querySelectorAll('[data-bs-toggle="tooltip"]')].map(f=>new bootstrap.Tooltip(f))}const g=u(()=>JSON.stringify(e.rules)),{nextTick:i}=Vue;return i(()=>{a()}),{...e,addRule:t,removeRule:b,jsonRules:g,name:s.name,t:d}}}).mount(l),p(()=>e.rules,()=>{n()},{deep:!0})})})();
