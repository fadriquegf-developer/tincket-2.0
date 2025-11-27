<template>
  <div class="box">
    <div class="box-header">
      <h3 class="box-title">{{ $t('gift_cards') }}</h3>
      <div class="box-tools">
        <div class="input-group input-group-sm" style="width: 250px;">
          <input v-model="code" name="table_search" class="form-control" placeholder="Code" type="text"
            @keyup.enter="validate">
          <button class="btn btn-success" type="button" @click="validate" :disabled="isValidating">
            {{ $t('gift_card.validate') }}
          </button>
        </div>
      </div>
    </div>
    <div class="box-body table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>{{ $t('session') }}</th>
            <th>{{ $t('slot') }}</th>
            <th>{{ $t('gift_card.code') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in inscriptions" :key="index">
            <td style="width: 50%">
              {{ getTranslationWithFallback(item.session.event.name) }}
              <span v-if="getTranslationWithFallback(item.session.name)">-
                {{ getTranslationWithFallback(item.session.name) }}
              </span> ({{ item.session.starts_on }})
              <input type="hidden" name="gift_cards[session_id][]" :value="item.session.id" />
            </td>
            <td style="width: 25%">
              <p class="mb-0">{{ item.slot.name }}</p>
              <input type="hidden" name="gift_cards[slot_id][]" :value="item.slot.id" />
            </td>
            <td style="width: 20%">
              <p class="mb-0">{{ item.code }}</p>
              <input type="hidden" name="gift_cards[code][]" :value="item.code" />
            </td>
            <td style="width: 5%">
              <button class="btn btn-sm btn-outline-danger" type="button" @click="removeItem(index)">
                <i class="la la-trash" aria-hidden="true"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, inject } from 'vue'
import { useTranslations } from '../composables/useTranslations'

const giftStore = inject('giftStore')
const { $t } = useTranslations()

const code = ref('')
const isValidating = ref(false)

const inscriptions = computed(() => giftStore.inscriptions)

const removeItem = (index) => {
  giftStore.removeInscription(index)
}

const validate = async () => {
  if (!code.value) return

  isValidating.value = true

  try {
    const data = await giftStore.validateCode(code.value)

    if (data.success) {
      giftStore.setEvent(data.event)
      giftStore.setCurrentCode(code.value)
      code.value = ''

      // Abrir modal
      const modal = new bootstrap.Modal(document.getElementById('layoutGiftModal'))
      modal.show()
    }
  } catch (error) {
    // Mostrar notificación de error
    if (window.PNotify) {
      new PNotify({
        title: "Alerta",
        text: error.message,
        type: "warning"
      })
    } else {
      alert(error.message)
    }
  } finally {
    isValidating.value = false
  }
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