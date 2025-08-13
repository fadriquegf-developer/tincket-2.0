<template>
    <div class="modal d-block" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@{{ event.name }}</h5>
                    <button type="button" class="btn-close" @click="$emit('cancel-gift')" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>
                        {{ $t ? $t('ticket-office.select_session_for_gift') : 'Selecciona una sesión para la tarjeta regalo' }}
                    </p>
                    <ul class="list-group">
                        <li v-for="session in event.sessions" :key="session.id"
                            class="list-group-item d-flex justify-content-between">
                            <span>@{{ session.name }}</span>
                            <button class="btn btn-sm btn-success" @click="selectSession(session)">
                                {{ $t ? $t('ticket-office.select') : 'Seleccionar' }}
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    event: { type: Object, required: true },
})

const emit = defineEmits(['finish-gift', 'cancel-gift'])

function selectSession(session) {
    // Crea una inscripción asociada al evento regalado
    const inscription = {
        event: props.event,
        session,
        slot: null,
        price: 0, // define el precio o úsalo como gratis
    }
    emit('finish-gift', inscription)
}
</script>
