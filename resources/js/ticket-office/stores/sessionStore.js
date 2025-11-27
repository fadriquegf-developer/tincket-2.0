// resources/js/ticket-office/stores/sessionStore.js
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useSessionStore = defineStore('session', () => {
  const currentSession = ref(null)
  const observerCallbacks = ref([])

  const setCurrentSession = (session) => {
    currentSession.value = session
    if (session) {
      if (typeof session.sold === 'undefined' || session.sold === null) {
        session.sold = 0;
      }
    }
    notifyObservers()
  }

  const getCurrentSession = () => {
    return currentSession.value
  }

  const registerObserverCallback = (callback) => {
    observerCallbacks.value.push(callback)
  }

  const notifyObservers = () => {
    observerCallbacks.value.forEach(callback => callback())
  }

  return {
    currentSession,
    setCurrentSession,
    getCurrentSession,
    registerObserverCallback
  }
})