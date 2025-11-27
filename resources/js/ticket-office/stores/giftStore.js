import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useGiftStore = defineStore('gift', () => {
  const inscriptions = ref([])
  const event = ref(null)
  const currentSession = ref(null)
  const currentCode = ref(null)
  const observerCallbacks = ref([])

  const setEvent = (ev) => {
    event.value = ev
  }

  const getEvent = () => {
    return event.value
  }

  const setCurrentSession = (session) => {
    currentSession.value = session
    toggleSession(session)
  }

  const setCurrentCode = (code) => {
    currentCode.value = code
  }

  const getCurrentSession = () => {
    return currentSession.value
  }

  const toggleSession = (session) => {
    currentSession.value = session
    if (event.value && event.value.next_sessions) {
      event.value.next_sessions.forEach(s => {
        s.is_selected = session && s.id === session.id
      })
    }
  }

  const prepareInscription = (session, slot) => {
    const inscription = {
      session: session,
      slot: slot,
      code: currentCode.value
    }
    
    if (slot === null) {
      inscription.slot = { rates: session.rates }
    } else {
      if (slot.rates && slot.rates.length > 0) {
        inscription.selected_rate = slot.rates[0]
      }
    }
    
    return inscription
  }

  const addInscription = (inscription) => {
    inscriptions.value.push(inscription)
    notifyObservers()
  }

  const removeInscription = (index) => {
    inscriptions.value.splice(index, 1)
    notifyObservers()
  }

  const resetInscriptions = () => {
    inscriptions.value.splice(0, inscriptions.value.length)
  }

  const findInscriptionIndex = (session, slot, rate) => {
    return inscriptions.value.findIndex(i => {
      return i.session.id === session.id && 
             (slot === null || i.slot.id == slot.id) && 
             (typeof rate === 'undefined' || i.selected_rate.id === rate.id)
    })
  }

  const findInscription = (session, slot) => {
    const index = findInscriptionIndex(session, slot)
    if (index > -1) {
      return inscriptions.value[index]
    }
    return null
  }

  const hasInscription = (session, slot) => {
    return findInscriptionIndex(session, slot) > -1
  }

  const hasCode = (code) => {
    return inscriptions.value.findIndex(i => i.code === code) > -1
  }

  const validateCode = async (code) => {
    if (hasCode(code)) {
      throw new Error('Ja tens aquest codi a la cistella')
    }

    if (window.showLoading) window.showLoading()

    try {
      const response = await fetch(`/api/gift-card/validate?code=${code}`)
      const data = await response.json()
      
      if (!data.success) {
        throw new Error("No s'ha trobat el codi o ja s'ha reclamat")
      }
      
      return data
    } catch (error) {
      throw error
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
    inscriptions,
    event,
    currentSession,
    currentCode,
    setEvent,
    getEvent,
    setCurrentSession,
    setCurrentCode,
    getCurrentSession,
    toggleSession,
    prepareInscription,
    addInscription,
    removeInscription,
    resetInscriptions,
    findInscriptionIndex,
    findInscription,
    hasInscription,
    hasCode,
    validateCode,
    registerObserverCallback
  }
})