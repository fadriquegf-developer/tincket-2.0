<template>
    <div id="ticket-office-app">
        <form @submit="handleSubmit" method="POST" :action="storeRoute">
            <input type="hidden" name="_token" :value="csrfToken">

            <div class="row">
                <div class="col-12">
                    <div class="mb-3">
                        <InscriptionsList />
                    </div>
                    <div class="mb-3">
                        <PacksList />
                    </div>
                    <div class="mb-3">
                        <GiftCardsList />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <ClientForm :initial-data="oldData" />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <PaymentForm />
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <button type="button" class="btn btn-lg btn-success btn-confirm" :disabled="isSubmitting"
                        @click="handleSubmit">
                        <span v-if="isSubmitting">
                            <i class="fas fa-spinner fa-spin me-2"></i>
                            Procesando...
                        </span>
                        <span v-else>
                            {{ $t('confirm_cart') }}
                        </span>
                    </button>
                </div>
            </div>
        </form>

        <!-- Modales -->
        <LayoutModal />
        <PacksModal />
        <GiftModal />

        <!-- Loading overlay -->
        <div v-if="isLoading" id="loading">
            <div class="spinner-border text-light" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, provide } from 'vue'
import { useInscriptionStore } from '../stores/inscriptionStore'
import { usePackStore } from '../stores/packStore'
import { useGiftStore } from '../stores/giftStore'
import { useSessionStore } from '../stores/sessionStore'
import { useSlotMapStore } from '../stores/slotMapStore'
import { useTranslations } from '../composables/useTranslations'

import InscriptionsList from './InscriptionsList.vue'
import PacksList from './PacksList.vue'
import GiftCardsList from './GiftCardsList.vue'
import ClientForm from './ClientForm.vue'
import PaymentForm from './PaymentForm.vue'
import LayoutModal from './modals/LayoutModal.vue'
import PacksModal from './modals/PacksModal.vue'
import GiftModal from './modals/GiftModal.vue'

// Props desde Laravel
const props = defineProps({
    storeRoute: {
        type: String,
        default: '/ticket-office/store'
    },
    canManageGiftCards: {
        type: Boolean,
        default: false
    },
    oldData: {
        type: Object,
        default: () => ({})
    }
})

// Estado local
const isSubmitting = ref(false)
const isLoading = ref(false)
const csrfToken = ref(document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '')
const { $t } = useTranslations()

// Stores
const inscriptionStore = useInscriptionStore()
const packStore = usePackStore()
const giftStore = useGiftStore()
const sessionStore = useSessionStore()
const slotMapStore = useSlotMapStore()

// Provide stores para componentes hijos
provide('inscriptionStore', inscriptionStore)
provide('packStore', packStore)
provide('giftStore', giftStore)
provide('sessionStore', sessionStore)
provide('slotMapStore', slotMapStore)

// Métodos
const handleSubmit = (event) => {
    console.log('[TicketOffice] Form submission initiated')

    // Si es un evento de botón, no hay event.target.submit, así que obtenemos el form
    const form = event.target.closest('form') || document.querySelector('form[method="POST"]')

    if (!form) {
        console.error('[TicketOffice] Form not found')
        return false
    }

    // Realizar validaciones
    const validation = validateForm()

    if (!validation.isValid) {
        console.log('[TicketOffice] Validation failed:', validation.message)
        showValidationError(validation.message)
        return false
    }

    console.log('[TicketOffice] Validation passed, submitting form')

    // Si todo es válido, proceder con el envío
    isSubmitting.value = true

    // Enviar el formulario manualmente
    form.submit()
}

const validateForm = () => {
    const errors = []

    // 1. Validar datos del cliente
    const clientValidation = validateClient()
    if (!clientValidation.isValid) {
        errors.push(clientValidation.message)
    }

    // 2. Validar que tenga productos en el carrito
    const cartValidation = validateCart()
    if (!cartValidation.isValid) {
        errors.push(cartValidation.message)
    }

    return {
        isValid: errors.length === 0,
        message: errors.join('\n')
    }
}

const validateClient = () => {
    // Obtener valores del formulario
    const email = document.querySelector('input[name="client[email]"]')?.value?.trim()
    const firstname = document.querySelector('input[name="client[firstname]"]')?.value?.trim()
    const lastname = document.querySelector('input[name="client[lastname]"]')?.value?.trim()

    console.log('[Validation] Client data:', { email, firstname, lastname })

    // if (!email) {
    //     return {
    //         isValid: false,
    //         message: 'El email del cliente es obligatorio'
    //     }
    // }

    // Validar formato de email básico
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (email && !emailRegex.test(email)) {
        return {
            isValid: false,
            message: 'El email del cliente debe tener un formato válido'
        }
    }

    // if (!firstname) {
    //     return {
    //         isValid: false,
    //         message: 'El nombre del cliente es obligatorio'
    //     }
    // }

    // if (!lastname) {
    //     return {
    //         isValid: false,
    //         message: 'Los apellidos del cliente son obligatorios'
    //     }
    // }

    return { isValid: true }
}

const validateCart = () => {
    const hasInscriptions = inscriptionStore.inscriptions.length > 0
    const hasPacks = packStore.packs.length > 0
    const hasGiftCards = giftStore.inscriptions.length > 0

    console.log('[Validation] Cart status:', {
        hasInscriptions,
        hasPacks,
        hasGiftCards,
        inscriptionsCount: inscriptionStore.inscriptions.length,
        packsCount: packStore.packs.length,
        giftCardsCount: giftStore.inscriptions.length
    })

    if (!hasInscriptions && !hasPacks && !hasGiftCards) {
        return {
            isValid: false,
            message: 'Debe añadir al menos una inscripción, un pack o una tarjeta regalo al carrito'
        }
    }

    return { isValid: true }
}

const showValidationError = (message) => {
    // Opción 1: Usar PNotify si está disponible
    if (window.PNotify) {
        new window.PNotify({
            title: 'Error de validación',
            text: message,
            type: 'error',
            styling: 'bootstrap4',
            delay: 5000
        })
    }
    // Opción 2: Usar SweetAlert si está disponible  
    else if (window.Swal) {
        window.Swal.fire({
            icon: 'error',
            title: 'Error de validación',
            text: message,
            confirmButtonText: 'Entendido'
        })
    }
    // Opción 3: Fallback con alert nativo
    else {
        alert(`Error de validación:\n\n${message}`)
    }
}

const showLoading = () => {
    isLoading.value = true
}

const hideLoading = () => {
    isLoading.value = false
}

// Exponer funciones globalmente para compatibilidad
window.showLoading = showLoading
window.hideLoading = hideLoading

onMounted(() => {
    // Cargar datos iniciales si existen
    if (props.oldData && Object.keys(props.oldData).length > 0) {
        console.log('Loading old data:', props.oldData)
    }

    // Prevenir envío de formulario con Enter
    document.addEventListener('keypress', (e) => {
        if (e.target.closest('form') && e.which === 13) {
            e.preventDefault()
            return false
        }
    })
})
</script>

<style scoped>
#loading {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.spinner-border {
    width: 3rem;
    height: 3rem;
}
</style>