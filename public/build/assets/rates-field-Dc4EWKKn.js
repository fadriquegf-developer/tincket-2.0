(function(){const{createApp:b,reactive:v,watch:f,nextTick:h}=Vue;document.querySelectorAll('[id^="rates-field-"]').forEach(p=>{const l=JSON.parse(p.dataset.props);let x=0;function m(){return"r_"+Date.now()+"_"+x++}const t=v({zones:l.zones||[],defined:l.definedRates||[],numbered:!!l.isNumbered,dirty:!1,modalIndex:null,rates:(l.initial||[]).map(a=>{var d;const e=((d=a.rate)==null?void 0:d.validator_class_attr)||{},o=a.rate&&l.definedRates?l.definedRates.find(c=>c.id===a.rate.id):null;return{uid:m(),assignated_rate_id:a.assignated_rate_id??null,rate:o,max_on_sale:a.max_on_sale??0,max_per_order:a.max_per_order??0,price:a.price??0,is_public:a.is_public??!1,is_private:a.is_private??!1,max_per_code:a.max_per_code??0,available_since:a.available_since??"",available_until:a.available_until??"",code:e.code||"",max_per_user:e.max_per_user||""}})});b({template:`
        <div>
          <table ref="table" class="table table-bordered table-striped">
            <thead>
              <tr>
                <th v-if="zones.length && numbered">{{ translations.zone }}</th>
                <th>{{ translations.rate }}</th>
                <th>{{ translations.total }}</th>
                <th>{{ translations.per_insc }}</th>
                <th>{{ translations.price }}</th>
                <th>Web</th>
                <th>Limitada</th>
                <th>{{ translations.actions }}</th>
              </tr>
            </thead>
            <tbody ref="tbody">
              <tr v-for="(r,i) in rates" :key="r.uid" class="draggable">
                <td v-if="zones.length && numbered">
                  <select v-model="r.assignated_rate_id" @change="onZoneChange(i)" class="form-control">
                    <option :value="null" disabled>Selecciona zona</option>
                    <option v-for="z in zones" :key="z.id" :value="z.id">{{ z.name }}</option>
                  </select>
                </td>
                <td>
                  <select v-model="r.rate" @change="onRateChange(i)" class="form-control">
                    <option :value="null" disabled>Selecciona tarifa</option>
                    <option v-for="d in filteredRates(r)" :key="d.id" :value="d">{{ d.name }}</option>
                  </select>
                  <div v-if="r.rate && r.rate.needs_code===1" class="mt-2">
                    <input class="form-control mb-1"
                          :placeholder=" translations.discount_code"
                          v-model="r.code">
                    <input type="number" class="form-control"
                          :placeholder="translations.max_per_user"
                          v-model.number="r.max_per_user">
                  </div>
                </td>
                <td><input class="form-control" v-model.number="r.max_on_sale"></td>
                <td><input class="form-control" v-model.number="r.max_per_order"></td>
                <td><input class="form-control" v-model.number="r.price" step="0.1"></td>
                <td class="text-center"><input type="checkbox" v-model="r.is_public"></td>
                <td class="text-center"><input type="checkbox" v-model="r.is_private" @change="onPrivateChange(i)"></td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm">
                      <button
                        v-if="r.is_private"
                        type="button"
                        class="btn btn-secondary"
                        @click.prevent="openModal(i)"
                        title="Importar códigos"
                      ><i class="la la-cog"></i></button>
                      <button
                        type="button"
                        class="btn btn-danger"
                        @click.prevent="remove(i)"
                        title="Eliminar tarifa"
                      ><i class="la la-trash"></i></button>
                      <button
                        type="button"
                        class="btn btn-light sort-handle"
                        title="Reordenar filas"
                      ><i class="la la-sort"></i></button>
                    </div>
                  </td>
              </tr>
            </tbody>
          </table>
          <button type="button" class="btn btn-sm btn-primary" @click.prevent="add">
            <i class="la la-plus"></i> {{ translations.add_rate }}
          </button>       
      </div>
      `,setup(){const a=n=>t.defined.filter(i=>{var u;return((u=n.rate)==null?void 0:u.id)===i.id?!0:t.numbered&&t.zones.length>0?!t.rates.some(r=>{var s;return r!==n&&r.assignated_rate_id===n.assignated_rate_id&&((s=r.rate)==null?void 0:s.id)===i.id}):!t.rates.some(r=>{var s;return r!==n&&((s=r.rate)==null?void 0:s.id)===i.id})});function e(){t.rates.push({uid:m(),assignated_rate_id:null,rate:null,max_on_sale:0,max_per_order:0,price:0,is_public:!0,is_private:!1,max_per_code:0,available_since:"",available_until:"",code:"",max_per_user:""}),t.dirty=!0}function o(n){t.rates.splice(n,1),t.dirty=!0}function d(n){t.rates[n].is_private&&c(n),t.dirty=!0}function c(n){const i=t.rates[n];$("#modal_max_per_code").val(i.max_per_code),$("#modal_available_since").val(i.available_since),$("#modal_available_until").val(i.available_until),t.modalIndex=n,$("#importCodesModal").modal("show")}function g(n){t.dirty=!0}function y(n){const i=t.rates[n];i.rate&&i.assignated_rate_id&&t.rates.some((_,r)=>{var s;return r!==n&&_.assignated_rate_id===i.assignated_rate_id&&((s=_.rate)==null?void 0:s.id)===i.rate.id})&&(i.rate=null),t.dirty=!0}return{...t,translations:l.translations,filteredRates:a,add:e,remove:o,onPrivateChange:d,openModal:c,onRateChange:g,onZoneChange:y}},mounted(){h(()=>{Sortable.create(this.$refs.tbody,{handle:".sort-handle",animation:150,onEnd(a){const e=t.rates.splice(a.oldIndex,1);t.rates.splice(a.newIndex,0,e[0]),t.dirty=!0}})})}}).mount(p),$("#modal_save_codes").on("click",()=>{const a=t.modalIndex;if(a===null)return;const e=t.rates[a];e.max_per_code=Number($("#modal_max_per_code").val()),e.available_since=$("#modal_available_since").val(),e.available_until=$("#modal_available_until").val(),t.dirty=!0,$("#importCodesModal").modal("hide")}),f(()=>t.rates,()=>{const a=t.rates.map(e=>{const o={uid:e.uid,assignated_rate_id:e.assignated_rate_id,zone_id:e.assignated_rate_id,rate:e.rate,price:e.price,max_on_sale:e.max_on_sale,max_per_order:e.max_per_order,is_public:e.is_public,is_private:e.is_private,max_per_code:e.max_per_code,available_since:e.available_since||null,available_until:e.available_until||null};return e.rate&&e.rate.needs_code===1&&(o.validator_class={class:e.rate.validator_class,attr:{code:e.code,max_per_user:e.max_per_user}}),o});document.getElementById("rates-json-"+l.name).value=JSON.stringify(a),document.getElementById("rates-dirty-"+l.name).value=1},{deep:!0})})})();
