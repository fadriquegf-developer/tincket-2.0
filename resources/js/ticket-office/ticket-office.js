import { createApp } from 'vue'
import TicketOfficeApp from './components/TicketOfficeApp.vue'

const app = createApp({})
app.component('ticket-office-app', TicketOfficeApp)
app.mount('#ticketOfficeApp')
