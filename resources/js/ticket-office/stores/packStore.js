// resources/js/ticket-office/stores/packStore.js
import {
    defineStore
} from 'pinia'
import {
    ref,
    computed
} from 'vue'

export const usePackStore = defineStore('pack', () => {
    const packs = ref([])
    const selectedPack = ref(null)
    const selectedSessions = ref([])
    const selectedPackRules = ref([])
    const events = ref([])
    const inscriptions = ref([])
    const observerCallbacks = ref([])

    const minNumberOfSessions = ref(1)
    const maxNumberOfSessions = ref(0)
    const allSessions = ref(false)
    const minPerCart = ref(1)
    const maxPerCart = ref(10)

    const selectPack = (pack) => {
        // Limpiar sessions anteriores del pack para evitar acumulación
        pack.sessions = []
        selectedPack.value = pack
        loadEvents()
    }

    const resetSelection = () => {
        if (selectedPack.value) {
            selectedPack.value.sessions = []
        }
        selectedPack.value = null
        selectedSessions.value.splice(0, selectedSessions.value.length)
        events.value.splice(0, events.value.length)
        selectedPackRules.value.splice(0, selectedPackRules.value.length)
        inscriptions.value.splice(0, inscriptions.value.length)
        allSessions.value = false
    }

    const toggleSession = (session) => {
        if (allSessions.value) return false

        const index = selectedSessions.value.findIndex(s => s.id === session.id)

        if (index === -1) {
            selectedPack.value.sessions = selectedPack.value.sessions || []
            selectedPack.value.sessions.push(session)
            selectedSessions.value.push(session)
            session.is_selected = true
        } else {
            selectedPack.value.sessions.splice(index, 1)
            selectedSessions.value.splice(index, 1)
            session.is_selected = false
        }
    }

    const addInscription = (session, slot, rate) => {
        console.log('[PackStore] Adding inscription:', {
            sessionId: session.id,
            slotId: slot?.id,
            rateId: rate?.id
        })

        if (slot && hasInscriptionAllPacks(session, slot)) {
            console.warn('[PackStore] Slot already used in another pack, cannot add')
            return null
        }

        const inscription = {
            slot: slot
        }

        if (slot === null) {
            inscription.slot = {
                rates: session.rates
            }
        }

        inscription.selected_rate = rate

        // 🔧 FIX: Asegurar que session.selection existe
        if (!session.selection) {
            session.selection = []
        }

        session.selection.push(inscription)

        console.log('[PackStore] Session selection after add:', session.selection.length)

        // Añadir a inscriptions internas para tracking
        inscriptions.value.push({
            session: session,
            slot: slot
        })

        console.log('[PackStore] Total inscriptions:', inscriptions.value.length)

        notifyObservers()
        return inscription
    }

    const removeInscription = (session, slot) => {
        console.log('[PackStore] Removing inscription:', {
            sessionId: session.id,
            slotId: slot?.id
        })

        if (hasInscription(session, slot)) {
            const index = findInscriptionIndex(session, slot)
            inscriptions.value.splice(index, 1)

            // 🔧 FIX: Remover de session.selection correctamente
            if (session.selection) {
                const selectionIndex = session.selection.findIndex(inscription => {
                    if (slot === null) {
                        return inscription.slot === null
                    }
                    return inscription.slot && inscription.slot.id === slot.id
                })

                if (selectionIndex > -1) {
                    session.selection.splice(selectionIndex, 1)
                    console.log('[PackStore] Removed from session.selection at index:', selectionIndex)
                }
            }

            console.log('[PackStore] Session selection after remove:', session.selection?.length || 0)

            notifyObservers()
        }
    }

    const hasInscription = (session, slot) => {
        return findInscriptionIndex(session, slot) > -1
    }

    const findInscriptionIndex = (session, slot) => {
        return inscriptions.value.findIndex(i => {
            return i.session.id === session.id && (slot === null || i.slot.id == slot.id)
        })
    }

    const getTotal = computed(() => {
        return packs.value.reduce((total, pack) => {
            return total + parseFloat(pack.price || 0)
        }, 0)
    })

    const getSelectedSessions = () => {
        return selectedSessions.value
    }

    const countSelectedSessions = () => {
        return selectedSessions.value.length
    }

    const resetInscriptions = () => {
        inscriptions.value.splice(0, inscriptions.value.length)
    }

    const hasInscriptionAllPacks = (session, slot) => {
        return packs.value.findIndex(p => {
            return p.inscriptions.findIndex(i => {
                return i.session.id === session.id && (slot === null || i.slot.id == slot.id)
            }) > -1
        }) > -1
    }

    const getSelectedPack = () => {
        return selectedPack.value
    }

    const getRule = (n) => {
        return selectedPackRules.value.find(rule => rule.number_sessions === n || rule.all_sessions === 1)
    }

    const shouldShowExpired = () => {
        const urlParams = new URLSearchParams(window.location.search)
        return urlParams.get('show_expired') === 'true'
    }

    const loadEvents = async () => {
        if (!selectedPack.value) return

        let url = `/api/pack/${selectedPack.value.id}`

        // Si la URL actual tiene show_expired=true, añadirlo a la petición API
        if (shouldShowExpired()) {
            url += '?show_expired=true';
        }

        try {
            // Clear current data
            events.value.splice(0, events.value.length)
            selectedSessions.value.splice(0, selectedSessions.value.length)
            selectedPackRules.value.splice(0, selectedPackRules.value.length)

            // Show loader
            if (window.showLoading) window.showLoading()

            const response = await fetch(url)
            const data = await response.json()

            events.value = data.events.filter(event => event.sessions.length > 0)
            selectedPackRules.value = data.rules

            minNumberOfSessions.value = Math.min(...data.rules.map(rule => rule.number_sessions)) || 1
            maxNumberOfSessions.value = Math.max(...data.rules.map(rule => rule.number_sessions)) || 0

            if (data.rules.length === 1 && data.rules[0].all_sessions) {
                allSessions.value = true
            } else {
                allSessions.value = false
            }

            minPerCart.value = data.min_per_cart
            maxPerCart.value = data.max_per_cart

            events.value.forEach(event => {
                event.sessions.forEach(session => {
                    session.event = event
                    session.selection = []

                    if (allSessions.value) {
                        selectedPack.value.sessions = selectedPack.value.sessions || []
                        selectedPack.value.sessions.push(session)
                        selectedSessions.value.push(session)
                        session.is_selected = true
                    }
                })
            })

            notifyObservers()
        } catch (error) {
            console.error('Error loading pack events:', error)
        } finally {
            if (window.hideLoading) window.hideLoading()
        }
    }

    const registerObserverCallback = (callback) => {
        observerCallbacks.value.push(callback)
    }

    const notifyObservers = () => {
        observerCallbacks.value.forEach(callback => callback())
    }

    return {
        packs,
        selectedPack,
        selectedSessions,
        events,
        minNumberOfSessions,
        maxNumberOfSessions,
        allSessions,
        minPerCart,
        maxPerCart,
        selectPack,
        toggleSession,
        addInscription,
        removeInscription,
        hasInscription,
        getTotal,
        getSelectedSessions,
        countSelectedSessions,
        resetInscriptions,
        hasInscriptionAllPacks,
        getSelectedPack,
        getRule,
        loadEvents,
        registerObserverCallback,
        resetSelection
    }
})
