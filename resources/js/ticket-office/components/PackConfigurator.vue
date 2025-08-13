<template>
    <div class="modal d-block" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@{{ pack.name[defaultLocale] }}</h5>
                    <button type="button" class="btn-close" @click="$emit('cancel')" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Paso 1: selección de sesiones -->
                    <div v-if="step === 0">
                        <h6>{{ $t ? $t('ticket-office.select_sessions') : 'Selecciona sesiones' }}</h6>
                        <ul class="list-group">
                            <li v-for="session in sessions" :key="session.id" class="list-group-item">
                                <label>
                                    <input type="checkbox" v-model="selectedSessions" :value="session" />
                                    @{{ session.name }}
                                </label>
                            </li>
                        </ul>
                    </div>

                    <!-- Paso 2: asignación de plazas -->
                    <div v-if="step === 1">
                        <h6>{{ $t ? $t('ticket-office.select_slots') : 'Selecciona plazas' }}</h6>
                        <div v-for="session in selectedSessions" :key="session.id">
                            <h6>@{{ session.name }}</h6>
                            <!-- Aquí podrías incorporar un componente que represente el plano de asientos -->
                            <button class="btn btn-secondary btn-sm" @click="toggleSelection(session, null)">
                                {{ $t ? $t('ticket-office.add_free_seat') : 'Añadir plaza' }}
                            </button>
                            <ul class="ms-3">
                                <li v-for="(ins, index) in inscriptionsBySession[session.id]" :key="index">
                                    {{ $t ? $t('ticket-office.inscription') : 'Inscripción' }}
                                    <button class="btn btn-link btn-sm"
                                        @click="removeInscription(session.id, index)">×</button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button v-if="step === 1" type="button" class="btn btn-secondary" @click="step = 0">
                        {{ $t ? $t('ticket-office.previous') : 'Anterior' }}
                    </button>
                    <button v-if="step === 0" type="button" class="btn btn-primary" @click="step = 1">
                        {{ $t ? $t('ticket-office.next') : 'Siguiente' }}
                    </button>
                    <button v-if="step === 1" type="button" class="btn btn-success" @click="finish">
                        {{ $t ? $t('ticket-office.finish_pack') : 'Finalizar pack' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
    pack: { type: Object, required: true },
    sessions: { type: Array, default: () => [] },
})

const state = reactive({
    step: 0,
    selectedSessions: [],
    inscriptionsBySession: {},
})

const defaultLocale = window.appLocale || 'es'
const { t } = useI18n ? useI18n() : { t: (s) => s }

/**
 * Inicializa el objeto inscriptionsBySession cuando cambian las sesiones seleccionadas.
 */
watch(
    () => state.selectedSessions,
    (newSessions) => {
        state.inscriptionsBySession = {}
        newSessions.forEach((s) => {
            state.inscriptionsBySession[s.id] = []
        })
    },
    { deep: true },
)

function toggleSelection(session, slot) {
    state.inscriptionsBySession[session.id].push({
        session,
        slot,
        price: 0, // ajustar según tarifa
    })
}

function removeInscription(sessionId, index) {
    state.inscriptionsBySession[sessionId].splice(index, 1)
}

function finish() {
    // Combina inscriptionsBySession en una lista de packs con sus inscripciones
    const newPacks = [
        {
            id: props.pack.id,
            name: props.pack.name,
            inscriptions: Object.values(state.inscriptionsBySession).flat(),
            price: 0, // calcula el precio según reglas
        },
    ]
    // Emite el resultado al componente padre
    emit('finish', newPacks)
}
</script>
