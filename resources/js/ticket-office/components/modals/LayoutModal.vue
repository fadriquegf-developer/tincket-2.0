<template>
    <div id="layoutModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-xxl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $t('select_session') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- 🎯 CAMBIO: Selector y alertas arriba en una sola fila compacta -->
                    <div class="row mb-2">
                        <div class="col-xl-8">
                            <select class="form-select" v-model="currentSession" @change="updateLayout">
                                <option v-for="session in sessions" :key="session.id" :value="session">
                                    {{ session.name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-xl-4">
                            <a class="btn btn-outline-secondary w-100" :href="showAllSessionsUrl">
                                {{ $t('show_all_sessions') }}
                            </a>
                        </div>
                    </div>

                    <!-- 🎯 CAMBIO: Alertas más compactas en una sola fila -->
                    <div v-if="currentSession?.is_numbered" class="row mb-3">
                        <div class="col-lg-6">
                            <div class="alert alert-info mb-0 py-2">
                                <i class="la la-info-circle"></i>
                                {{ $t('tickets_sold') }}: <strong>{{ currentSession.sold }}</strong>
                            </div>
                        </div>
                        <div v-if="currentSession.free_positions < 30" class="col-lg-6">
                            <div class="alert alert-warning mb-0 py-2">
                                <i class="la la-exclamation-triangle"></i>
                                {{ $t('there_is_only') }}
                                <strong>{{ currentSession.free_positions }}</strong>
                                {{ $t('free_slots_in_session') }}
                            </div>
                        </div>
                    </div>

                    <!-- 🎯 CAMBIO PRINCIPAL: Mapa a la izquierda (9 columnas), Lista a la derecha (3 columnas) -->
                    <div class="row">
                        <div class="col-xl-9">
                            <!-- Contenedor Zoomist para sesiones numeradas -->
                            <div v-if="currentSession?.is_numbered" class="zoomist-container-inscription">
                                <div class="zoomist-wrapper">
                                    <div class="zoomist-image">
                                        <SpaceLayout :key="layoutKey" :layout-url="layout" :layout-session="currentSession"
                                            :type-model="'inscription'" @add-inscription="addInscription"
                                            @remove-inscription="removeInscription" />
                                    </div>
                                </div>
                            </div>

                            <!-- Sesiones no numeradas -->
                            <div v-if="currentSession && !currentSession.is_numbered">
                                <div v-if="currentSession.rates && currentSession.rates.length > 0">
                                    <div v-for="rate in currentSession.rates" :key="rate.id" class="card mb-2">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <label class="form-label mb-0">
                                                        {{ getTranslationWithFallback(rate.name) }}
                                                    </label>
                                                    <small class="text-muted d-block">
                                                        Precio: {{ rate.price }}€
                                                    </small>
                                                </div>
                                                <div class="wrapper-rate">
                                                    <button type="button" class="minus" @click="decreaseRate(rate)"
                                                        :disabled="!rate.quantity || rate.quantity === 0">
                                                        <i class="la la-minus"></i>
                                                    </button>
                                                    <span class="num">
                                                        {{ rate.quantity || 0 }} / {{ rate.available }}
                                                    </span>
                                                    <button type="button" class="plus" @click="increaseRate(rate)"
                                                        :disabled="(rate.quantity || 0) >= rate.available">
                                                        <i class="la la-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="alert alert-warning">
                                    <i class="la la-exclamation-triangle"></i>
                                    No hay tarifas disponibles para esta sesión
                                </div>

                                <div v-if="currentSession.free_positions < 30" class="alert alert-warning mt-3">
                                    <i class="la la-exclamation-triangle"></i>
                                    {{ $t('there_is_only') }}
                                    <strong>{{ currentSession.free_positions }}</strong>
                                    {{ $t('free_slots_in_session') }}
                                </div>
                            </div>
                        </div>

                        <!-- 🎯 CAMBIO: Sidebar derecho más estrecho (3 columnas) -->
                        <div class="col-xl-3">
                            <div class="sticky-sidebar">
                                <div class="alert alert-info mb-2 py-2">
                                    <small>
                                        <i class="la la-info-circle"></i>
                                        {{ $t('zoom_help') }}
                                    </small>
                                </div>
                                <div class="alert alert-info mb-2 py-2">
                                    <strong>Total: {{ inscriptions.length }}</strong>
                                </div>
                                <div class="inscriptions-list">
                                    <div v-for="inscription in inscriptions" :key="getInscriptionKey(inscription)"
                                        class="inscription-item mb-2">
                                        <div class="card">
                                            <div class="card-body p-2">
                                                <strong class="d-block small">{{ inscription.session.name }}</strong>
                                                <div v-if="inscription.slot && inscription.slot.name">
                                                    <small class="text-muted">{{ inscription.slot.name }}</small>
                                                </div>
                                                <div v-if="inscription.slot && inscription.slot.comment" class="mt-1">
                                                    <small class="text-info">{{ inscription.slot.comment }}</small>
                                                </div>
                                                <div v-if="inscription.selected_rate" class="mt-1">
                                                    <small class="text-success">
                                                        {{ getTranslationWithFallback(inscription.selected_rate.name) }}
                                                        -
                                                        {{ inscription.price }}€
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="d-flex justify-content-end align-items-center w-100">
                        <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                            {{ $t('end_selection') }}
                        </button>
                    </div>
                    <div v-if="currentSession?.is_numbered" class="w-100 mt-3">
                        <hr>
                        <SpaceLayoutLegend :zones="currentSession?.space?.zones || []" />
                        <p class="help-text text-start small text-muted">
                            {{ $t('svg_layout.help') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, inject, onMounted, nextTick, watch } from 'vue'
import SpaceLayout from '../SpaceLayout.vue'
import SpaceLayoutLegend from '../SpaceLayoutLegend.vue'
import { useTranslations } from '../../composables/useTranslations'

// Inject stores
const { $t } = useTranslations()
const inscriptionStore = inject('inscriptionStore')
const sessionStore = inject('sessionStore')
const slotMapStore = inject('slotMapStore')

// Estado local
const layoutKey = ref(0)
const currentSession = ref(null)
const layout = ref('')
const sessions = ref(window.sessions_list || [])
const showDebugInfo = ref(process.env.NODE_ENV === 'development')
const isModalOpen = ref(false)
const hasInitialized = ref(false)

// Computed
const inscriptions = computed(() => inscriptionStore.inscriptions)
const showAllSessionsUrl = computed(() => {
    const url = new URL(window.location.href)
    url.searchParams.set('show_expired', 'true')
    return url.toString()
})

const getInscriptionKey = (inscription) => {
    const sessionId = inscription.session.id
    const slotId = inscription.slot?.id || 'no-slot'
    const rateId = inscription.selected_rate?.id || 'no-rate'
    return `${sessionId}-${slotId}-${rateId}-${Math.random()}`
}

const loadSessionRates = async (sessionId) => {
    try {
        console.log('[LayoutModal] Loading rates for session:', sessionId)

        const response = await fetch(`/api/session/${sessionId}/rates`)
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }

        const rates = await response.json()
        console.log('[LayoutModal] Rates loaded:', rates)

        if (currentSession.value && currentSession.value.id === sessionId) {
            currentSession.value.rates = rates.map(rate => ({
                ...rate,
                quantity: 0
            }))

            console.log('[LayoutModal] Rates assigned to session:', currentSession.value.rates)
        }
    } catch (error) {
        console.error('[LayoutModal] Error loading rates:', error)
    }
}

const updateLayout = async () => {
    if (!currentSession.value) return

    try {
        const sessionId = currentSession.value.id
        console.log('[LayoutModal] Updating layout for session:', sessionId)

        if (currentSession.value.is_numbered) {
            if (window.showLoading) window.showLoading()

            const mapData = await slotMapStore.loadSessionMap(sessionId)

            if (mapData && mapData.space) {
                layout.value = mapData.space.svg_host_path
                currentSession.value.space = mapData.space
                sessionStore.setCurrentSession(currentSession.value)

                await nextTick()
                // await initializeZoomist()
            }
        } else {
            await loadSessionRates(sessionId)
            sessionStore.setCurrentSession(currentSession.value)
        }
    } catch (error) {
        console.error('[LayoutModal] Error updating layout:', error)
    } finally {
        if (window.hideLoading) window.hideLoading()
    }
}

const addInscription = (session, slot) => {
    console.log('[LayoutModal] Adding inscription:', { sessionId: session.id, slotId: slot?.id })

    const success = inscriptionStore.addInscription(session, slot)

    if (success) {
        if (session.is_numbered) {
            session.sold = (session.sold || 0) + 1
        }

        const slotElement = document.querySelector(`[data-slot-id="${slot.id}"]`)
        if (slotElement) {
            slotElement.classList.add('selected')
            slotElement.setAttribute('data-checked', 'true')
        }
    }

    return success
}

const removeInscription = (session, slot) => {
    console.log('[LayoutModal] Removing inscription:', { sessionId: session.id, slotId: slot?.id })

    const index = inscriptionStore.findInscriptionIndex(session, slot)

    if (index > -1) {
        inscriptionStore.removeInscription(index)

        if (session.is_numbered && session.sold > 0) {
            session.sold--
        }

        const slotElement = document.querySelector(`[data-slot-id="${slot.id}"]`)
        if (slotElement) {
            slotElement.classList.remove('selected')
            slotElement.setAttribute('data-checked', 'false')
        }

        return true
    }

    return false
}

const increaseRate = (rate) => {
    if (!rate || !currentSession.value) return

    if ((rate.quantity || 0) < rate.available) {
        rate.quantity = (rate.quantity || 0) + 1
        console.log('[LayoutModal] Increased rate:', rate.id, 'to:', rate.quantity)

        const success = inscriptionStore.addInscription(currentSession.value, null, rate)

        if (!success) {
            rate.quantity--
            console.warn('[LayoutModal] Failed to add inscription, reverting quantity')
        }
    }
}

const decreaseRate = (rate) => {
    if (!rate || !rate.quantity || rate.quantity === 0) return

    const index = inscriptionStore.findInscriptionIndex(currentSession.value, null, rate)
    console.log('[LayoutModal] Found inscription at index:', index)

    if (index > -1) {
        inscriptionStore.removeInscription(index)
        rate.quantity--
        console.log('[LayoutModal] Decreased rate:', rate.id, 'to:', rate.quantity)
    }
}

const clearSelectionMarks = () => {
    const slots = document.querySelectorAll('[data-slot-id]')
    if (slots) {
        slots.forEach(slot => {
            slot.classList.remove('selected')
            if (slot.dataset.checked === 'true') {
                slot.click()
            }
        })
    }
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

const loadSessionsList = async () => {
    if (!window.sessions_list) {
        const response = await fetch('/api/session?with_sales=false')
        window.sessions_list = await response.json()
    }
    return window.sessions_list
}

const initializeModal = async () => {
    if (!hasInitialized.value) {
        sessions.value = await loadSessionsList()
        hasInitialized.value = true
        currentSession.value = sessions.value.find(s => !s.is_past) || sessions.value[sessions.value.length - 1]
    }

    // Siempre actualizar el layout al abrir
    if (currentSession.value) {
        await updateLayout()
    }
}

// Inicialización
onMounted(() => {
    const modal = document.getElementById('layoutModal')

    modal?.addEventListener('show.bs.modal', async () => {
        isModalOpen.value = true
        layoutKey.value++
        await initializeModal()
    })

    modal?.addEventListener('hidden.bs.modal', () => {
        isModalOpen.value = false
    })
})

watch(currentSession, async (newSession) => {
    if (newSession && isModalOpen.value) {
        await updateLayout()
    }
})
</script>

<style scoped>
/* 🎯 CAMBIO: Modal más ancho - 85vw en lugar de 70vw */
.modal-xxl {
    max-width: 85vw !important;
    width: 85vw;
    margin: 1.5rem auto;
}

.modal-xxl .modal-content {
    min-height: 0vh;
}

/* 🎯 CAMBIO: Mapa más alto para aprovechar el espacio */
.zoomist-container-inscription {
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

/* SVG escalado mejorado */
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

/* 🎯 NUEVO: Sidebar con scroll independiente */
.sticky-sidebar {
    position: sticky;
    top: 1rem;
    max-height: calc(90vh - 200px);
    overflow-y: auto;
}

.inscriptions-list {
    max-height: calc(90vh - 270px);
    overflow-y: auto;
}

.inscription-item .card {
    transition: all 0.2s ease;
}

.inscription-item .card:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

/* Controles de cantidad */
.wrapper-rate {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.wrapper-rate .minus,
.wrapper-rate .plus {
    background: #6c757d;
    color: white;
    border: none;
    border-radius: 0.25rem;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 1.25rem;
    line-height: 1;
    transition: background-color 0.15s ease-in-out;
}

.wrapper-rate .minus:hover,
.wrapper-rate .plus:hover {
    background: #495057;
}

.wrapper-rate .minus:disabled,
.wrapper-rate .plus:disabled {
    background: #dee2e6;
    cursor: not-allowed;
    opacity: 0.6;
}

.wrapper-rate .num {
    font-weight: 600;
    min-width: 80px;
    text-align: center;
    font-size: 1.1rem;
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

/* Scrollbar personalizado para la lista */
.inscriptions-list::-webkit-scrollbar {
    width: 6px;
}

.inscriptions-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.inscriptions-list::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.inscriptions-list::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>