<template>
    <div class="ticket-office">
        <!-- Selector de packs -->
        <pack-selector :packs="packs" @select-pack="selectPack" />

        <!-- Configurador de packs (sólo cuando haya pack seleccionado) -->
        <pack-configurator v-if="selectedPack" :pack="selectedPack" :sessions="sessions" @finish="onPackConfigured"
            @cancel="selectedPack = null" />

        <!-- Mapa de asientos e inscripciones sueltas -->
        <layout-map :sessions="sessions" @add-inscription="addInscription" />

        <!-- Lista de inscripciones -->
        <inscription-list :inscriptions="inscriptions" @remove-inscription="removeInscription" />

        <!-- Tarjetas regalo -->
        <gift-card :gifts="gifts" @validate-code="validateGiftCode" />

        <!-- Configurador de tarjeta regalo -->
        <gift-configurator v-if="giftEvent" :event="giftEvent" @finish-gift="onGiftFinished"
            @cancel-gift="giftEvent = null" />
    </div>
</template>

<script setup>
import { reactive } from 'vue'
import axios from 'axios'
import PackSelector from './PackSelector.vue'
import PackConfigurator from './PackConfigurator.vue'
import LayoutMap from './LayoutMap.vue'
import InscriptionList from './InscriptionList.vue'
import GiftCard from './GiftCard.vue'
import GiftConfigurator from './GiftConfigurator.vue'

/**
 * Estado principal de la taquilla. 
 * sessions: lista de sesiones (proviene de Blade a través de window.sessions_list)
 * packs: lista de packs ya añadidos al carrito
 * inscriptions: inscripciones sueltas
 * gifts: inscripciones procedentes de tarjetas regalo
 * selectedPack: pack que se está configurando en este momento
 * giftEvent: evento asociado a un código regalo válido
 */
const state = reactive({
    sessions: window.sessions_list || [],
    packs: [],
    inscriptions: [],
    gifts: [],
    selectedPack: null,
    giftEvent: null,
})

// Exponemos el estado a la plantilla
const { sessions, packs, inscriptions, gifts } = state

function selectPack(pack) {
    state.selectedPack = pack
}

function onPackConfigured(newPacks) {
    state.packs.push(...newPacks)
    state.selectedPack = null
}

function addInscription(session, slot, rate = null) {
    // Crea una inscripción (simplificada)
    state.inscriptions.push({
        session,
        slot,
        rate,
        price: rate?.price || 0,
    })
}

function removeInscription(index) {
    state.inscriptions.splice(index, 1)
}

function validateGiftCode(code) {
    // Llama a la API para validar el código
    axios
        .get('/api/gift-card/validate', { params: { code } })
        .then(({ data }) => {
            if (data.success) {
                state.giftEvent = data.event
            } else {
                alert('Código no válido o ya utilizado')
            }
        })
        .catch(() => {
            alert('Error al validar el código')
        })
}

function onGiftFinished(inscription) {
    state.gifts.push(inscription)
    state.giftEvent = null
}
</script>

<style scoped>
.ticket-office {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
</style>
