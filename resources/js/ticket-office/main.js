import { createApp } from 'vue'
import { createPinia } from 'pinia'
import TicketOffice from './components/TicketOffice.vue'

// Crear la app Vue
const app = createApp(TicketOffice, {
  // Pasar props desde window.vueAppProps
  ...window.vueAppProps
})

// Usar Pinia para gestión de estado
app.use(createPinia())

// Configurar datos globales que vienen de Laravel
app.config.globalProperties.$sessionsData = window.sessions_list || []
app.config.globalProperties.$slotStatus = window.slotStatus || {}

// Montar la aplicación cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('ticket-office-app')
  if (container) {
    app.mount('#ticket-office-app')
  } else {
    console.error('Container #ticket-office-app not found')
  }
})