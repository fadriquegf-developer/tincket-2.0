<template>
    <div class="layout-map card">
        <div class="card-header">
            <h5 class="mb-0">{{ $t ? $t('ticket-office.sessions') : 'Sesiones' }}</h5>
        </div>
        <div class="card-body">
            <div v-if="sessions.length === 0" class="alert alert-warning">
                {{ $t ? $t('ticket-office.no_sessions') : 'No hay sesiones disponibles' }}
            </div>
            <div v-else>
                <div v-for="session in sessions" :key="session.id" class="mb-3 border p-2">
                    <h6>@{{ session.name }}</h6>
                    <div v-if="session.is_numbered">
                        <!-- Aquí podrías cargar un plano SVG -->
                        <button class="btn btn-sm btn-outline-primary" @click="addSeat(session)">
                            {{ $t ? $t('ticket-office.add_seat') : 'Añadir asiento' }}
                        </button>
                    </div>
                    <div v-else>
                        <!-- Para sesiones no numeradas -->
                        <button class="btn btn-sm btn-outline-primary" @click="addSeat(session)">
                            {{ $t ? $t('ticket-office.add_ticket') : 'Añadir entrada' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    sessions: { type: Array, default: () => [] },
})

const emits = defineEmits(['add-inscription'])

function addSeat(session) {
    // Aquí se podría abrir un modal con el plano de asientos, o añadir directamente
    emits('add-inscription', session, null, null)
}
</script>
