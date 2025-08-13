<template>
    <div class="pack-selector card">
        <div class="card-header">
            <h5 class="mb-0">{{ $t ? $t('ticket-office.packs') : 'Packs' }}</h5>
        </div>
        <div class="card-body">
            <div v-if="packs.length === 0" class="alert alert-info">
                {{ $t ? $t('ticket-office.no_packs_available') : 'No hay packs disponibles' }}
            </div>
            <ul class="list-group" v-else>
                <li v-for="pack in packs" :key="pack.id"
                    class="list-group-item d-flex justify-content-between align-items-center">
                    <span>@{{ pack.name[defaultLocale] }}</span>
                    <button class="btn btn-sm btn-primary" @click="$emit('select-pack', pack)">
                        {{ $t ? $t('ticket-office.select') : 'Seleccionar' }}
                    </button>
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
    packs: { type: Array, default: () => [] },
})

/**
 * Si utilizas laravel/locales, puedes pasar el locale desde Blade.
 * En este ejemplo lo obtenemos de una variable global.
 */
const defaultLocale = window.appLocale || 'es'
const { t } = useI18n ? useI18n() : { t: (s) => s }
</script>

<style scoped>
.pack-selector ul {
    margin-bottom: 0;
}
</style>
