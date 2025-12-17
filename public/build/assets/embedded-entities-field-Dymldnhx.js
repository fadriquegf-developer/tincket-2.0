(function(){const{createApp:r,reactive:p,computed:u,onMounted:b,nextTick:m,ref:y}=Vue;document.querySelectorAll('[id^="embedded-entities-field-"]').forEach(d=>{const s=JSON.parse(d.dataset.props),f=s.locale||"es";let h=0;const a=()=>`e_${Date.now()}_${h++}`,g=t=>{const o=n=>typeof n=="object"&&n?n[f]??Object.values(n)[0]:n;return o(t.name)||o(t.title)||t.label||t.slug||t.id},v=async t=>{try{const o="/api/entity?type="+encodeURIComponent(t),n=await fetch(o);return n.ok?await n.json():(console.error("[EmbeddedEntities] "+n.status+" "+o),[])}catch(o){return console.error("[EmbeddedEntities] error",o),[]}},i=p({entities:(s.initial||[]).map(t=>({uid:a(),embeded_type:t.embeded_type,embeded_id:t.embeded_id!==null?String(t.embeded_id):null,options:[],loading:!0}))});r({template:`
        <div class="array-container">
          <table class="table table-bordered table-striped w-100" style="table-layout:fixed;">
            <colgroup>
              <col style="width:30%">
              <col style="width:60%">
              <col style="width:10%">
            </colgroup>
            <thead>
              <tr><th>{{ translations.type }}</th><th>{{ translations.entity }}</th><th class="text-center"></th></tr>
            </thead>

            <tbody ref="tbody">
              <tr v-for="(item,i) in entities" :key="item.uid">
                <!-- Tipo -->
                <td>
                  <select v-model="item.embeded_type"
                          class="form-control"
                          style="width:100%"
                          @change.stop="changeType(item)">
                    <option disabled value="">Selecciona</option>
                    <option value="App\\Models\\Event">{{ translations.event }}</option>
                    <option value="App\\Models\\Page">{{ translations.page }}</option>
                    <option value="App\\Models\\Post">{{ translations.post }}</option>
                  </select>
                </td>

                <!-- Entidad -->
                <td>
                  <select v-if="!item.loading"
                          v-model="item.embeded_id"
                          class="form-control"
                          style="width:100%"
                          @change.stop
                          @input.stop>
                    <option disabled :value="null">Selecciona</option>
                    <option v-for="o in item.options" :key="o.id" :value="String(o.id)">
                      {{ o.label }}
                    </option>
                  </select>
                  <span v-else class="fa fa-spinner fa-spin"></span>
                </td>

                <!-- Handle + Delete -->
                <td class="text-center align-middle">
                   <div class="d-flex flex-row h-100 w-100">
                      <button type="button"
                            class="drag-handle btn btn-light flex-fill d-flex align-items-center justify-content-center rounded-0 me-2"
                            style="cursor:move">
                            <i class="la la-sort"></i>
                      </button>
                      <button type="button"
                            class="btn btn-danger flex-fill d-flex align-items-center justify-content-center rounded-0"
                            @click.stop.prevent="remove(i)">
                            <i class="la la-trash"></i>
                      </button>
                    </div>
                </td>
              </tr>
            </tbody>
          </table>

          <button type="button"
                  class="btn btn-sm btn-primary mt-2"
                  @click.stop.prevent="add">
            <i class="la la-plus"></i> {{ translations.add }}
          </button>

          <input type="hidden"
                 name="embedded_entities"
                 :value="jsonValue">
        </div>
      `,setup(){const t=y(null),o=s.translations||{},n=u(()=>JSON.stringify(i.entities.filter(e=>e.embeded_type&&e.embeded_id).map(e=>({embeded_type:e.embeded_type,embeded_id:e.embeded_id})))),c=async e=>{e.loading=!0,e.options=(await v(e.embeded_type)).map(l=>({...l,label:g(l)})),e.loading=!1};b(()=>{i.entities.forEach(c),m(()=>{Sortable.create(t.value,{handle:".drag-handle",animation:150,onEnd(e){if(e.oldIndex===e.newIndex)return;const l=i.entities.splice(e.oldIndex,1)[0];i.entities.splice(e.newIndex,0,l)}})})});const _=()=>i.entities.push({uid:a(),embeded_type:"",embeded_id:null,options:[],loading:!1}),w=e=>i.entities.splice(e,1),x=e=>{e.embeded_id=null,c(e)};return{tbody:t,entities:i.entities,jsonValue:n,add:_,remove:w,changeType:x,translations:o}}}).mount(d)})})();
