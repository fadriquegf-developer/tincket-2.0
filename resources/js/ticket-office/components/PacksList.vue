<template>
  <div class="box">
    <div class="box-header">
      <h3 class="box-title">{{ $t('packs') }}</h3>
    </div>
    <div class="box-body table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>{{ $t('pack') }}</th>
            <th>{{ $t('inscriptions') }}</th>
            <th>{{ $t('price') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(pack, index) in packs" :key="index">
            <td>
              {{ getTranslationWithFallback(pack.name) }}
            </td>
            <td>
              <input type="hidden" name="packs[]" :value="prepareJson(pack)" />
              <div v-for="inscription in pack.inscriptions" :key="inscription.session.id" class="small">
                {{ getTranslationWithFallback(inscription.session.event.name) }}
                <span v-if="inscription.session.is_numbered"> -
                  {{ inscription.slot.name }}
                </span>
              </div>
            </td>
            <td>
              {{ pack.price }}€
            </td>
            <td>
              <button class="btn btn-sm btn-outline-danger" type="button" @click="removeItem(index)">
                <i class="la la-trash me-1" aria-hidden="true"></i>
                {{ $t('delete_item') }}
              </button>
            </td>
          </tr>
          <tr>
            <td colspan="4" class="text-end">
              <button class="btn btn-sm btn-success" type="button" data-bs-toggle="modal" data-bs-target="#packsModal">
                <i class="la la-plus me-1" aria-hidden="true"></i>
                {{ $t('new_pack') }}
              </button>
            </td>
          </tr>
          <tr class="table-secondary">
            <td>
              <strong>{{ $t('total') }}</strong>
            </td>
            <td></td>
            <td>
              <strong>{{ getTotal }}€</strong>
            </td>
            <td></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { inject, computed } from 'vue'
import { useTranslations } from '../composables/useTranslations'

const packStore = inject('packStore')
const { $t } = useTranslations()

const packs = computed(() => packStore.packs)
const getTotal = computed(() => packStore.getTotal)

const removeItem = (index) => {
  if (packs.value.length <= 1) { // pack_multiplier equivalent
    packStore.packs.splice(0)
  } else {
    packStore.packs.splice(index, 1)
  }
}

const prepareJson = (pack) => {
  const json = {
    pack_id: pack.id,
    selection: pack.inscriptions.map(inscription => ({
      session_id: inscription.session.id,
      is_numbered: inscription.session.is_numbered,
      slot_id: inscription.slot ? inscription.slot.id : null
    }))
  }
  return JSON.stringify(json)
}

const getTranslationWithFallback = (translations) => {
  if (typeof translations === 'string') return translations
  if (!translations || typeof translations !== 'object') return 'Sin nombre'
  
  const currentLocale = document.documentElement.lang || 'es'
  const fallbackOrder = ['ca', 'es', 'en']
  
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