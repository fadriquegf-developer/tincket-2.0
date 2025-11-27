import {
    defineStore
} from 'pinia'
import {
    ref,
    computed
} from 'vue'

export const useInscriptionStore = defineStore('inscription', () => {
    const inscriptions = ref([])
    const observerCallbacks = ref([])

    // 🔧 FIX: Método addInscription mejorado con return value
    const addInscription = (session, slot, rate = null) => {
        try {
            console.log('[InscriptionStore] addInscription called with:', {
                sessionId: session?.id,
                sessionName: session?.name,
                slotId: slot?.id,
                rateId: rate?.id
            })

            if (!session) {
                console.error('[InscriptionStore] Session is required')
                return false
            }

            // Verificar duplicados para sesiones numeradas
            if (slot !== null && hasInscription(session, slot)) {
                console.warn('[InscriptionStore] Inscription already exists for this slot')
                return false
            }

            // Crear la inscripción con la estructura correcta
            const inscription = {
                session: session, // Pasar el objeto completo
                slot: slot, // Pasar el objeto completo (o null)
                selected_rate: null,
                price: 0
            }

            if (slot === null) {
                // Sesión no numerada - necesita rate
                if (!rate) {
                    console.error('[InscriptionStore] Rate is required for non-numbered sessions')
                    return false
                }

                // Crear un slot "virtual" para sesiones no numeradas
                inscription.slot = {
                    id: null,
                    name: null,
                    rates: session.rates || []
                }
                inscription.selected_rate = rate
                inscription.price = rate.price || 0

            } else {
                // Sesión numerada - el slot existe
                // Si el slot tiene tarifas, usar la primera
                if (slot.rates && slot.rates.length > 0) {
                    const firstRate = slot.rates[0]
                    inscription.selected_rate = firstRate
                    inscription.price = firstRate.pivot ? firstRate.pivot.price : firstRate.price
                } else if (session.rates && session.rates.length > 0) {
                    // Si el slot no tiene tarifas, usar las de la sesión
                    const firstRate = session.rates[0]
                    inscription.selected_rate = firstRate
                    inscription.price = firstRate.price || 0
                }
            }

            // Agregar la inscripción
            inscriptions.value.push(inscription)

            console.log('[InscriptionStore] Inscription added:', inscription)
            console.log('[InscriptionStore] Total inscriptions:', inscriptions.value.length)

            notifyObservers()
            return true

        } catch (error) {
            console.error('[InscriptionStore] Error adding inscription:', error)
            return false
        }
    }

    const removeInscription = (index) => {
        try {
            if (index >= 0 && index < inscriptions.value.length) {
                const removed = inscriptions.value.splice(index, 1)

                notifyObservers()
                return true
            } else {
                console.warn('[InscriptionStore] Invalid index for removal:', index)
                return false
            }
        } catch (error) {
            console.error('[InscriptionStore] Error removing inscription:', error)
            return false
        }
    }

    const updateRate = (item) => {
        try {
            console.log('[InscriptionStore] updateRate called with item:', item)

            if (!item || !item.selected_rate) {
                console.warn('[InscriptionStore] updateRate: invalid item or rate')
                return
            }

            if (item.selected_rate.pivot) {
                item.price = item.selected_rate.pivot.price
            } else {
                item.price = item.selected_rate.price
            }

            console.log('[InscriptionStore] Price updated to:', item.price)
        } catch (error) {
            console.error('[InscriptionStore] Error updating rate:', error)
        }
    }

    const getTotal = computed(() => {
        return inscriptions.value.reduce((total, inscription) => {
            return total + parseFloat(inscription.price || 0)
        }, 0)
    })

    // 🔧 FIX: Mejorar la búsqueda de inscripciones
    const findInscriptionIndex = (session, slot, rate) => {
        return inscriptions.value.findIndex(inscription => {
            const sessionMatch = inscription.session.id === session.id

            // Para sesiones numeradas, comparar slot ID
            if (slot !== null && inscription.slot && inscription.slot.id) {
                const slotMatch = inscription.slot.id == slot.id
                if (rate && inscription.selected_rate) {
                    const rateMatch = inscription.selected_rate.id === rate.id
                    return sessionMatch && slotMatch && rateMatch
                }
                return sessionMatch && slotMatch
            }

            // Para sesiones no numeradas, comparar rate si se proporciona
            if (slot === null && inscription.slot && !inscription.slot.id) {
                if (rate && inscription.selected_rate) {
                    const rateMatch = inscription.selected_rate.id === rate.id
                    return sessionMatch && rateMatch
                }
                return sessionMatch
            }

            return false
        })
    }

    const findInscription = (session, slot) => {
        const index = findInscriptionIndex(session, slot)
        if (index > -1) {
            return inscriptions.value[index]
        }
        return null
    }

    // 🔧 FIX: Mejorar hasInscription para mayor precisión
    const hasInscription = (session, slot) => {
        if (!session) return false

        const index = findInscriptionIndex(session, slot)
        const result = index > -1

        return result
    }

    const countInscriptions = (session, rate) => {
        if (!session || !rate) return 0

        return inscriptions.value.filter(inscription => {
            return inscription.session.id == session.id &&
                inscription.selected_rate &&
                inscription.selected_rate.id == rate.id
        }).length
    }

    // 🔧 FIX: Método para limpiar todas las inscripciones
    const clearAllInscriptions = () => {
        inscriptions.value.splice(0, inscriptions.value.length)
        notifyObservers()
    }

    const registerObserverCallback = (callback) => {
        if (typeof callback === 'function') {
            observerCallbacks.value.push(callback)
        }
    }

    const notifyObservers = () => {
        observerCallbacks.value.forEach(callback => {
            try {
                callback()
            } catch (error) {
                console.error('[InscriptionStore] Error in observer callback:', error)
            }
        })
    }

    // 🔧 FIX: Método para debugging
    const getDebugInfo = () => {
        return {
            totalInscriptions: inscriptions.value.length,
            inscriptions: inscriptions.value.map(i => ({
                sessionId: i.session.id,
                slotId: i.slot?.id || 'no-slot',
                rateId: i.selected_rate?.id || 'no-rate',
                price: i.price
            })),
            total: getTotal.value
        }
    }

    return {
        inscriptions,
        addInscription,
        removeInscription,
        updateRate,
        getTotal,
        findInscriptionIndex,
        findInscription,
        hasInscription,
        countInscriptions,
        clearAllInscriptions,
        registerObserverCallback,
        getDebugInfo
    }
})
