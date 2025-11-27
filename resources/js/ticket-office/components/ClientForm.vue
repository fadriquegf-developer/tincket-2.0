<template>
  <div class="box">
    <div class="box-header">
      <h3 class="box-title">{{ $t('client') }}</h3>
    </div>
    <div class="box-body">
      <div class="row mb-3">
        <label for="client_email" class="col-sm-3 col-form-label">
          {{ $t('email') }}
        </label>
        <div class="col-sm-9">
          <input 
            type="email" 
            class="form-control" 
            name="client[email]" 
            id="client_email" 
            v-model="client.email"
            autocomplete="email"
            ref="emailInput"
          />
        </div>
      </div>
      <div class="row mb-3">
        <label for="client_firstname" class="col-sm-3 col-form-label">
          {{ $t('firstname') }}
        </label>
        <div class="col-sm-9">
          <input 
            type="text" 
            class="form-control" 
            name="client[firstname]" 
            id="client_firstname"
            v-model="client.firstname"
            autocomplete="given-name"
          />
        </div>
      </div>
      <div class="row mb-3">
        <label for="client_lastname" class="col-sm-3 col-form-label">
          {{ $t('lastname') }}
        </label>
        <div class="col-sm-9">
          <input 
            type="text" 
            class="form-control" 
            name="client[lastname]" 
            id="client_lastname"
            v-model="client.lastname"
            autocomplete="family-name"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { useTranslations } from '../composables/useTranslations'

// Estado reactivo del cliente
const client = ref({
  email: '',
  firstname: '',
  lastname: ''
})

const emailInput = ref(null)
const { $t } = useTranslations()

// Props para datos iniciales
const props = defineProps({
  initialData: {
    type: Object,
    default: () => ({})
  }
})

// Métodos
const setupAutocomplete = () => {
  if (!window.$ || !emailInput.value) return

  window.$(emailInput.value).autocomplete({
    source: function (request, response) {
      // 🔧 FIX: Cambiar POST a GET y usar parámetro 'q' en lugar de 'email'
      window.$.get('/api/client/autocomplete', { 
        q: request.term  // 🔧 FIX: Cambiar 'email' por 'q' según el controlador
      })
      .done(function (data) {
        // 🔧 FIX: El controlador devuelve un array directo, no un objeto con 'data'
        response(data.map(item => ({
          label: `${item.email} - ${item.name} ${item.surname}`,
          value: item.email,
          name: item.name,
          surname: item.surname
        })))
      })
      .fail(function (xhr) {
        console.error("Error searching:", xhr.responseText)
        response([])
      })
    },
    minLength: 3,
    select: function (event, ui) {
      client.value.email = ui.item.value
      client.value.firstname = ui.item.name
      client.value.lastname = ui.item.surname
    }
  })
}

onMounted(() => {
  // Cargar datos iniciales si existen
  if (props.initialData.client) {
    Object.assign(client.value, props.initialData.client)
  }

  // Configurar autocompletado en el siguiente tick
  nextTick(() => {
    setupAutocomplete()
  })
})
</script>