<template>
  <div class="box">
    <div class="box-header">
      <h3 class="box-title">{{ $t('inscriptions_set') }}</h3>
    </div>
    <div class="box-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th style="width: 35%">{{ $t('session') }}</th>
              <th style="width: 25%">{{ $t('rate') }}</th>
              <th style="width: 20%">{{ $t('slot') }}</th>
              <th style="width: 15%">{{ $t('price') }}</th>
              <th style="width: 5%"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in inscriptions" :key="`inscription-${index}`">
              <td>
                {{ item.session.name }}
                <input type="hidden" name="inscriptions[session_id][]" :value="item.session.id" />
              </td>
              <td>
                <input v-if="item.selected_rate" type="hidden" name="inscriptions[rate_id][]"
                  :value="item.selected_rate.id" />
                <select class="form-select form-select-sm" v-model="item.selected_rate" @change="updateRate(item)">
                  <option v-for="rate in item.slot.rates" :key="rate.id" :value="rate">
                    {{ getTranslationWithFallback(rate.name) }}
                  </option>
                </select>
              </td>
              <td>
                <span class="text-muted">{{ item.slot.name }}</span>
                <input type="hidden" name="inscriptions[slot_id][]" :value="item.slot.id" />
              </td>
              <td>
                <strong>{{ item.price }}€</strong>
              </td>
              <td>
                <button class="btn btn-sm btn-outline-danger" type="button" @click="removeItem(index)">
                  <i class="la la-trash" aria-hidden="true"></i>
                </button>
              </td>
            </tr>
            <tr>
              <td colspan="5" class="text-end">
                <button class="btn btn-sm btn-success" type="button" data-bs-toggle="modal"
                  data-bs-target="#layoutModal">
                  <i class="la la-plus" aria-hidden="true"></i>
                  {{ $t('add_inscription') }}
                </button>
              </td>
            </tr>
            <tr class="table-secondary">
              <td colspan="3">
                <strong>{{ $t('total') }}</strong>
              </td>
              <td>
                <strong class="text-success">{{ getTotal }}€</strong>
              </td>
              <td></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { inject, computed } from 'vue'
import { useTranslations } from '../composables/useTranslations'

// Inject stores
const inscriptionStore = inject('inscriptionStore')
const { $t } = useTranslations()

// Computed
const inscriptions = computed(() => {
  console.log('[InscriptionsList] Current inscriptions:', inscriptionStore.inscriptions)
  inscriptionStore.inscriptions.forEach((insc, idx) => {
    console.log(`[InscriptionsList] Inscription ${idx}:`, {
      hasSession: !!insc.session,
      sessionType: typeof insc.session,
      session: insc.session,
      hasSlot: !!insc.slot,
      slot: insc.slot
    })
  })
  return inscriptionStore.inscriptions
})

const getTotal = computed(() => inscriptionStore.getTotal)

// Methods
const updateRate = (item) => {
  inscriptionStore.updateRate(item)
}

const removeItem = (index) => {
  inscriptionStore.removeInscription(index)
}

const getTranslationWithFallback = (translations) => {
  if (typeof translations === 'string') return translations
  if (!translations || typeof translations !== 'object') return 'Sin nombre'

  const currentLocale = document.documentElement.lang || 'es'
  const fallbackOrder = ['ca', 'es', 'gl']

  if (translations[currentLocale]?.trim()) return translations[currentLocale]

  for (let locale of fallbackOrder) {
    if (translations[locale]?.trim()) return translations[locale]
  }

  for (let key in translations) {
    if (translations[key]?.trim()) return translations[key]
  }

  return 'Sin nombre'
}

</script>