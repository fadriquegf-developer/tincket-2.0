<template>
    <div class="gift-card card">
        <div class="card-header">
            <h5 class="mb-0">{{ $t ? $t('ticket-office.gift_cards') : 'Tarjetas regalo' }}</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label" for="gift-code">{{ $t ? $t('ticket-office.code') : 'Código' }}</label>
                <input id="gift-code" type="text" v-model="code" class="form-control form-control-sm" />
            </div>
            <button class="btn btn-primary btn-sm" @click="validate">
                {{ $t ? $t('ticket-office.validate') : 'Validar' }}
            </button>
            <hr>
            <ul class="list-group">
                <li v-for="(gift, index) in gifts" :key="index" class="list-group-item">
                    @{{ gift.event.name }} – @{{ gift.session.name }}
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
    gifts: { type: Array, default: () => [] },
})

const emit = defineEmits(['validate-code'])

const code = ref('')

function validate() {
    if (code.value.trim() !== '') {
        emit('validate-code', code.value.trim())
        code.value = ''
    }
}
</script>
