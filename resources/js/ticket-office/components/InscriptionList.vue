<template>
    <div class="inscription-list card">
        <div class="card-header">
            <h5 class="mb-0">{{ $t ? $t('ticket-office.inscriptions') : 'Inscripciones' }}</h5>
        </div>
        <div class="card-body">
            <div v-if="inscriptions.length === 0" class="alert alert-info">
                {{ $t ? $t('ticket-office.no_inscriptions') : 'No hay inscripciones' }}
            </div>
            <table v-else class="table table-sm">
                <thead>
                    <tr>
                        <th>{{ $t ? $t('ticket-office.session') : 'Sesión' }}</th>
                        <th>{{ $t ? $t('ticket-office.price') : 'Precio' }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(inscription, index) in inscriptions" :key="index">
                        <td>@{{ inscription.session.name }}</td>
                        <td>@{{ inscription.price }}€</td>
                        <td>
                            <button class="btn btn-link btn-sm" @click="$emit('remove-inscription', index)">×</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    inscriptions: { type: Array, default: () => [] },
})
</script>
