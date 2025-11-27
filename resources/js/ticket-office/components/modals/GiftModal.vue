<template>
  <div id="layoutGiftModal" class="modal fade" tabindex="-1" role="dialog">
    <!-- 🎯 CAMBIO: Usar modal-xxl en lugar de modal-xl -->
    <div class="modal-dialog modal-xxl" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title" v-if="step === 0">{{ $t('gift_card.select_the_sessions') }}</h4>
          <h4 class="modal-title" v-if="step === 1">
            {{ $t('select_slots_for') }}
            <strong>{{ getTranslationWithFallback(currentSession?.event?.name) }}</strong>
          </h4>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <!-- Step 0: Selección de sesión -->
        <div class="modal-body" v-if="step === 0">
          <div class="row">
            <div v-for="session in event?.next_sessions" :key="session.id" class="col-md-4 mb-3">
              <div class="card" :class="{ 'border-success bg-success bg-opacity-10': session.is_selected }"
                @click="toggleSession(session)" style="cursor: pointer;">
                <div class="card-body">
                  {{ getTranslationWithFallback(event.name) }}
                  <span v-if="getTranslationWithFallback(session.name)">
                    - {{ getTranslationWithFallback(session.name) }}
                  </span>
                  ({{ session.starts_on }})
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 1: Selección de butaca - 🎯 LAYOUT MEJORADO -->
        <div class="modal-body" v-if="step === 1">
          <div class="row">
            <!-- 🎯 CAMBIO: Navegación lateral reducida -->
            <div class="col-1 d-flex align-items-center">
              <button v-if="currentSessionIndex > 0" class="btn btn-link" @click="previousSession">
                <i class="fas fa-chevron-left text-muted fa-2x"></i>
              </button>
            </div>

            <!-- 🎯 CAMBIO: Mapa ocupa casi todo el ancho (10 columnas en lugar de 7) -->
            <div class="col-10">
              <!-- Sesiones numeradas - mapa -->
              <div v-if="currentSession?.is_numbered" class="zoomist-container-gift">
                <div class="zoomist-wrapper">
                  <div class="zoomist-image">
                    <SpaceLayout :layout-url="layout" :layout-session="currentSession" :type-model="'gift'"
                      @add-inscription="addInscription" @remove-inscription="removeInscription" />
                  </div>
                </div>
              </div>

              <!-- Sesiones no numeradas -->
              <div v-if="currentSession && !currentSession.is_numbered"
                class="d-flex align-items-center justify-content-center" style="min-height: 400px;">
                <div class="col-md-6">
                  <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    {{ $t('sessio_no_numerada') }}<br>
                    <div v-if="currentSession.free_positions < 30">
                      {{ $t('there_is_only') }}
                      <strong>{{ currentSession.free_positions }}</strong>
                      {{ $t('free_slots_in_session') }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 🎯 Columna derecha reducida a solo 1 columna para navegación -->
            <div class="col-1 d-flex align-items-center justify-content-end">
              <button v-if="event?.next_sessions && currentSessionIndex < event.next_sessions.length - 1"
                class="btn btn-link" @click="nextSession">
                <i class="fas fa-chevron-right text-muted fa-2x"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="reset">
            {{ $t('reset') }}
          </button>
          <button type="button" class="btn btn-primary" :disabled="!isNextStepReady()" @click="nextStep">
            Next
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, inject, onMounted } from 'vue'
import SpaceLayout from '../SpaceLayout.vue'
import { useTranslations } from '../../composables/useTranslations'

const { $t } = useTranslations()

// Inject stores
const giftStore = inject('giftStore')

// Estado local
const step = ref(0)
const currentSessionIndex = ref(0)
const inscription = ref(null)

// Computed
const event = computed(() => giftStore.event)
const currentSession = computed(() => giftStore.currentSession)
const layout = computed(() => currentSession.value?.space?.svg_host_path || '')

// Métodos
const toggleSession = (session) => {
  giftStore.setCurrentSession(session)
}

const nextStep = () => {
  if (step.value === 0) {
    onSessionChanged()
  } else if (step.value === 1) {
    finish()
  }
  step.value++
}

const previousSession = () => {
  currentSessionIndex.value--
}

const nextSession = () => {
  currentSessionIndex.value++
}

const addInscription = (session, slot) => {
  console.log('[GiftModal] Adding inscription for slot:', slot?.id)

  if (!inscription.value) {
    inscription.value = giftStore.prepareInscription(session, slot)
    return true
  }
  return false
}

const removeInscription = (session, slot) => {
  console.log('[GiftModal] Removing inscription for slot:', slot?.id)
  inscription.value = null
  return true
}

const onSessionChanged = () => {
  const session = giftStore.getCurrentSession()
  if (session) {
    session.zoom = session.space?.zoom

    if (!session.is_numbered) {
      inscription.value = giftStore.prepareInscription(session, null)
    }
  }
}

const finish = () => {
  if (inscription.value) {
    console.log('[GiftModal] Finishing with inscription:', inscription.value)

    giftStore.addInscription(inscription.value)

    giftStore.setCurrentCode(null)
    inscription.value = null

    const modal = bootstrap.Modal.getInstance(document.getElementById('layoutGiftModal'))
    modal.hide()
    reset()
  }
}

const isNextStepReady = () => {
  if (step.value === 0 && giftStore.getCurrentSession() !== null) {
    return true
  }
  if (step.value === 1 && inscription.value !== null) {
    return true
  }
  return false
}

const reset = () => {
  step.value = 0
  inscription.value = null
  giftStore.setCurrentSession(null)
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

// Inicialización
onMounted(() => {
  const modal = document.getElementById('layoutGiftModal')
  modal?.addEventListener('shown.bs.modal', () => {
    reset()
  })
})
</script>

<style scoped>
/* 🎯 CAMBIO: Modal más ancho - 85vw */
.modal-xxl {
  max-width: 85vw !important;
  width: 85vw;
  margin: 1.5rem auto;
}

/* 🎯 Contenedor del mapa de gift cards más grande */
.zoomist-container-gift {
  width: 100%;
  height: calc(90vh - 200px) !important;
  min-height: 800px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

.zoomist-wrapper {
  background: #f8f9fa;
  border-radius: 0.375rem;
  height: 100% !important;
  width: 100% !important;
  min-height: 800px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

.zoomist-image {
  height: 100% !important;
  width: 100% !important;
  min-height: 800px !important;
  pointer-events: auto;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

/* Escalado del SVG */
.zoomist-image svg,
.zoomist-image object,
.zoomist-image object svg,
.zoomist-image>div,
.zoomist-image>div>* {
  width: 100% !important;
  height: 100% !important;
  min-height: 800px !important;
  max-width: none !important;
  transform: scale(1) !important;
  transform-origin: center !important;
}

:deep(svg) {
  width: 100% !important;
  height: 100% !important;
  min-height: 800px !important;
  transform-origin: center !important;
}

:deep(object) {
  width: 100% !important;
  height: 100% !important;
}

:deep(.space-layout-container) {
  width: 100% !important;
  height: 100% !important;
  min-height: 800px !important;
}

/* 🎯 Botones de navegación mejorados */
.btn-link {
  padding: 0.5rem;
  text-decoration: none;
}

.btn-link:hover i {
  color: #0d6efd !important;
}

/* Responsivo para pantallas 2K+ */
@media (min-width: 1921px) and (min-height: 1081px) {

  .zoomist-image svg,
  .zoomist-image object,
  .zoomist-image object svg,
  .zoomist-image>div,
  .zoomist-image>div>* {
    transform: scale(1) !important;
  }

  :deep(svg) {
    transform: scale(1) !important;
  }
}
</style>