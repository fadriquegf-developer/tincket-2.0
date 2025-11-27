<template>
    <div id="packsModal" class="modal fade" tabindex="-1" role="dialog">
        <!-- 🎯 CAMBIO: Usar modal-xxl en lugar de modal-xl -->
        <div class="modal-dialog modal-xxl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" v-if="step === 0">{{ $t('select_the_pack') }}</h4>
                    <h4 class="modal-title" v-if="step === 1">{{ $t('select_the_sessions') }}</h4>
                    <h4 class="modal-title" v-if="step === 2">
                        {{ $t('select_slots_for') }}
                        <strong>{{ getTranslationWithFallback(currentSession?.event?.name) }}</strong>
                    </h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Step 0: Selección de pack -->
                <div class="modal-body" v-if="step === 0">
                    <div class="row mb-4">
                        <div class="col-auto">
                            <a class="btn btn-outline-secondary" :href="showAllPacksUrl">
                                {{ $t('show_all_packs') }}
                            </a>
                        </div>
                    </div>
                    <div class="row">
                        <div v-for="pack in availablePacks" :key="pack.id" class="col-md-4 mb-3"
                            @click="selectPack(pack)">
                            <div class="card h-100" style="cursor: pointer;">
                                <div class="card-body">
                                    <h5 class="card-title">{{ getTranslationWithFallback(pack.name) }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 1: Selección de sesiones -->
                <div class="modal-body" v-if="step === 1">
                    <div v-if="minSessionSelection > 0 && !allSessions" class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        {{ $t('select_at_least') }}
                        <strong>{{ minSessionSelection }}</strong>
                        {{ $t('sessions_to_sell_this_pack') }}.
                    </div>
                    <div class="alert alert-warning" v-if="events.length === 0">
                        No se encontraron eventos con sesiones disponibles
                    </div>
                    <div class="row">
                        <div v-for="event in events" :key="event.id" class="col-12">
                            <h5>{{ getTranslationWithFallback(event.name) }}</h5>
                            <div class="row">
                                <div v-for="session in event.sessions" :key="session.id" class="col-md-4 mb-3">
                                    <div class="card"
                                        :class="{ 'border-success bg-success bg-opacity-10': session.is_selected }"
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
                    </div>
                </div>

                <!-- Step 2: Selección de butacas - 🎯 LAYOUT MEJORADO -->
                <div class="modal-body" v-if="step === 2">
                    <div class="row">
                        <!-- 🎯 CAMBIO: Mapa ocupa 9 columnas (antes 8) -->
                        <div class="col-xl-9">
                            <!-- Sesiones numeradas - mapa -->
                            <div v-if="currentSession?.is_numbered" class="zoomist-container-pack">
                                <div class="zoomist-wrapper">
                                    <div class="zoomist-image">
                                        <SpaceLayout :layout-url="layout" :layout-session="currentSession"
                                            :type-model="'pack'" @add-inscription="addInscription"
                                            @remove-inscription="removeInscription" />
                                    </div>
                                </div>
                            </div>

                            <!-- Sesiones no numeradas -->
                            <div v-if="currentSession && !currentSession.is_numbered" class="row">
                                <div class="col-md-8 offset-md-2">
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

                        <!-- 🎯 CAMBIO: Sidebar derecho más estrecho (3 columnas en lugar de 4) -->
                        <div class="col-xl-3">
                            <div class="sticky-sessions-list">
                                <div v-for="(session, index) in getSelectedSessions()" :key="session.id" class="mb-2">
                                    <div class="card session-card"
                                        :class="{ 'border-primary active': index === currentSessionIndex }"
                                        @click="goToSession(index)" style="cursor: pointer;">
                                        <div class="card-body p-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-truncate" style="max-width: 150px;">
                                                    {{ getTranslationWithFallback(session.event.name) }}
                                                </small>
                                                <span class="badge bg-primary">
                                                    {{ session.is_numbered ? session.selection?.length || 0 :
                                                        packMultiplier
                                                    }}/{{ packMultiplier }}
                                                </span>
                                            </div>
                                            <div v-if="session.is_numbered && !session.selection?.length" class="mt-1">
                                                <span class="badge bg-danger small">{{ $t('pendent') }}</span>
                                            </div>
                                            <div v-if="!session.is_numbered" class="mt-1">
                                                <span class="badge bg-success small">{{ $t('sessio_no_numerada')
                                                    }}</span>
                                            </div>
                                            <div v-for="inscription in session.selection" :key="inscription.slot.id"
                                                class="mt-1">
                                                <small class="text-muted d-block text-truncate">
                                                    {{ inscription.slot.name }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-secondary" @click="reset">
                        {{ $t('reset') }}
                    </button>
                    <div class="d-flex align-items-center gap-3">
                        <div v-if="step === 1" class="d-flex align-items-center gap-2">
                            <label class="form-label mb-0">{{ $t('how_many_packs') }}?</label>
                            <select class="form-select form-select-sm" v-model="packMultiplier" style="width: auto;">
                                <option v-for="number in options" :key="number" :value="number">{{ number }}</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-primary" :disabled="!isNextStepReady()" @click="nextStep">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, inject, onMounted, watch, nextTick } from 'vue'
import SpaceLayout from '../SpaceLayout.vue'
import { roundToDecimals } from '../../utils/helpers.js'
import { useTranslations } from '../../composables/useTranslations'

const { $t } = useTranslations()

// Inject stores
const packStore = inject('packStore')

// Estado local
const step = ref(0)
const packMultiplier = ref(1)
const currentSessionIndex = ref(0)
const availablePacks = ref(window.packs_list || [])
const hasInitialized = ref(false)

// Computed
const currentSession = computed(() => getSelectedSessions()[currentSessionIndex.value])
const layout = computed(() => currentSession.value?.space?.svg_host_path || '')
const events = computed(() => packStore.events)
const minSessionSelection = computed(() => packStore.minNumberOfSessions)
const maxSessionSelection = computed(() => packStore.maxNumberOfSessions)
const allSessions = computed(() => packStore.allSessions)
const options = computed(() => {
    const min = packStore.minPerCart
    const max = packStore.maxPerCart
    const result = []
    for (let i = min; i <= max; i++) {
        result.push(i)
    }
    return result
})

const showAllPacksUrl = computed(() => {
    const url = new URL(window.location.href)
    url.searchParams.set('show_expired', 'true')
    return url.toString()
})

const selectPack = (pack) => {
    packStore.selectPack(pack)
    nextStep()
}

const toggleSession = (session) => {
    if (allSessions.value) return
    packStore.toggleSession(session)
}

const nextStep = () => {
    if (step.value === 1) {
        currentSessionIndex.value = 0
    } else if (step.value === 2) {
        finishPack()
        const modal = bootstrap.Modal.getInstance(document.getElementById('packsModal'))
        modal.hide()
        reset()
    }
    step.value++
}

const addInscription = (session, slot) => {
    console.log('[PacksModal] Adding inscription:', { sessionId: session.id, slotId: slot?.id })

    const rateToUse = slot?.rates?.[0] || session.rates?.[0]

    if (!rateToUse) {
        console.warn('[PacksModal] No rate available')
        return false
    }

    const result = packStore.addInscription(session, slot, rateToUse)

    if (!result) {
        console.warn('[PacksModal] Slot not added (already used in another pack)')
        return false
    }

    const slotElement = document.querySelector(`[data-slot-id="${slot.id}"]`)
    if (slotElement) {
        slotElement.classList.add('selected')
    }

    return true
}

const removeInscription = (session, slot) => {
    console.log('[PacksModal] Removing inscription:', { sessionId: session.id, slotId: slot?.id })

    packStore.removeInscription(session, slot)

    const slotElement = document.querySelector(`[data-slot-id="${slot.id}"]`)
    if (slotElement) {
        slotElement.classList.remove('selected')
    }

    console.log('[PacksModal] After removing:')
    console.log('  - Selection length:', session.selection?.length || 0)

    return true
}

const getSelectedSessions = () => {
    return packStore.getSelectedSessions()
}

const isNextStepReady = () => {
    console.log('[Debug] Checking isNextStepReady...')
    console.log('[Debug] Current step:', step.value)

    if (step.value === 1) {
        const selectedCount = packStore.countSelectedSessions()
        console.log('[Debug] Step 1 - Selected sessions count:', selectedCount)
        const result = (selectedCount >= minSessionSelection.value &&
            selectedCount <= maxSessionSelection.value) || allSessions.value
        console.log('[Debug] Step 1 result:', result)
        return result
    }

    if (step.value === 2) {
        const selectedSessions = getSelectedSessions()
        console.log('[Debug] Step 2 - Selected sessions:', selectedSessions)
        console.log('[Debug] Pack multiplier:', packMultiplier.value)

        const numberedSessions = selectedSessions.filter(session => session.is_numbered)
        console.log('[Debug] Numbered sessions only:', numberedSessions)

        if (numberedSessions.length === 0) {
            console.log('[Debug] ✅ No numbered sessions - ready!')
            return true
        }

        for (let session of numberedSessions) {
            console.log(`[Debug] Checking numbered session ${session.id}:`)
            console.log(`  - Selection length: ${session.selection?.length || 0}`)

            const hasCorrectSlots = session.selection && session.selection.length === packMultiplier.value
            console.log(`  - Has correct slots (${packMultiplier.value}): ${hasCorrectSlots}`)

            if (!hasCorrectSlots) {
                console.log(`  - ❌ NOT READY: Need ${packMultiplier.value}, has ${session.selection?.length || 0}`)
                return false
            }

            console.log(`  - ✅ Session ${session.id} is ready`)
        }

        console.log('[Debug] ✅ All numbered sessions ready!')
        return true
    }

    return false
}

const finishPack = () => {
    for (let i = 0; i < packMultiplier.value; i++) {
        const selectedPack = packStore.getSelectedPack()
        const selectedPackRule = packStore.getRule(selectedPack.sessions.length)
        let total = 0

        const pack = {
            id: selectedPack.id,
            name: selectedPack.name,
            inscriptions: []
        }

        selectedPack.sessions.forEach(session => {
            let price = parseFloat(session.price || 0)

            if (selectedPackRule.price_pack) {
                total += price
            } else {
                const ratio = (100 - parseFloat(selectedPackRule.percent_pack)) / 100
                price = roundToDecimals(price * ratio, 4)

                if (selectedPack.round_to_nearest && selectedPackRule.percent_pack) {
                    const factor = 0.5
                    const cost = price / factor
                    price = roundToDecimals(cost) * factor
                }

                total += price
            }

            pack.inscriptions.push({
                session: session,
                slot: session.is_numbered ? session.selection[i]?.slot : null
            })
        })

        if (selectedPackRule.price_pack) {
            total = selectedPackRule.price_pack
        } else {
            total = roundToDecimals(total, 2)
        }

        pack.price = total
        packStore.packs.push(pack)
    }
}

const reset = () => {
    step.value = 0
    currentSessionIndex.value = 0
    packMultiplier.value = 1
    packStore.resetSelection()
}

const goToSession = (index) => {
    currentSessionIndex.value = index
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

watch(() => {
    const sessions = getSelectedSessions()
    return sessions.map(session => ({
        id: session.id,
        selectionLength: session.selection?.length || 0,
        isNumbered: session.is_numbered
    }))
}, (newSelections) => {
    console.log('[Debug] Selection watcher triggered:', newSelections)

    nextTick(() => {
        const ready = isNextStepReady()
        console.log('[Debug] Is ready for next?', ready)

        const nextButton = document.querySelector('.modal-footer .btn-primary')
        if (nextButton) {
            nextButton.disabled = !ready
        }
    })
}, {
    deep: true,
    immediate: true
})

onMounted(() => {
    const modal = document.getElementById('packsModal')

    modal?.addEventListener('show.bs.modal', async () => {
        if (!hasInitialized.value) {
            hasInitialized.value = true
            if (availablePacks.value.length === 0) {
                availablePacks.value = window.packs_list || []
            }
        }
        reset()
    })
})
</script>

<style scoped>
/* 🎯 CAMBIO: Usar modal-xxl más ancho */
.modal-xxl {
    max-width: 85vw !important;
    width: 85vw;
    margin: 1.5rem auto;
}

/* 🎯 Contenedor del mapa de packs más grande */
.zoomist-container-pack {
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

/* 🎯 Sidebar de sesiones con scroll */
.sticky-sessions-list {
    position: sticky;
    top: 1rem;
    max-height: calc(90vh - 200px);
    overflow-y: auto;
}

.session-card {
    transition: all 0.2s ease;
}

.session-card:hover {
    box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.15);
    transform: translateY(-1px);
}

.session-card.active {
    background-color: #e7f3ff;
    border-width: 2px !important;
}

/* Scrollbar personalizado */
.sticky-sessions-list::-webkit-scrollbar {
    width: 6px;
}

.sticky-sessions-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.sticky-sessions-list::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.sticky-sessions-list::-webkit-scrollbar-thumb:hover {
    background: #555;
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
</style>