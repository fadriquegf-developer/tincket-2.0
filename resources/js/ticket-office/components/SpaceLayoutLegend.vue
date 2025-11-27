<template>
    <div class='space-layout-legend'>
        <ul class='legend-labels list-unstyled d-flex flex-wrap gap-3 mb-3'>
            <li class="d-flex align-items-center gap-2">
                <span class="slot-available"></span>
                <small>{{ $t('svg_layout.legend.available') }}</small>
            </li>
            <li class="d-flex align-items-center gap-2">
                <span class="slot-selected"></span>
                <small>{{ $t('svg_layout.legend.selected') }}</small>
            </li>
            <li class="d-flex align-items-center gap-2">
                <span class="slot-sold"></span>
                <small>{{ $t('svg_layout.legend.sold') }}</small>
            </li>
            <li class="d-flex align-items-center gap-2">
                <span class="slot-booked"></span>
                <small>{{ $t('svg_layout.legend.booked') }}</small>
            </li>
            <li class="d-flex align-items-center gap-2">
                <span class="slot-booked-packs"></span>
                <small>{{ $t('svg_layout.legend.booked_packs') }}</small>
            </li>
            <li class="d-flex align-items-center gap-2">
                <span class="slot-hidden"></span>
                <small>{{ $t('svg_layout.legend.hidden') }}</small>
            </li>
            <li class="d-flex align-items-center gap-2">
                <span class="slot-locked"></span>
                <small>{{ $t('svg_layout.legend.locked') }}</small>
            </li>
            <li class="d-flex align-items-center gap-2">
                <span class="slot-covid19"></span>
                <small>{{ $t('svg_layout.legend.covid19') }}</small>
            </li>
            <li class="d-flex align-items-center gap-2">
                <span class="slot-disability"></span>
                <small>{{ $t('svg_layout.legend.disability') }}</small>
            </li>

            <template v-if="showZones && zones && zones.length > 1">
                <li v-for="zone in zones" :key="zone.id" class="d-flex align-items-center gap-2">
                    <span 
                        class="slot-zone-indicator" 
                        :style="{ borderColor: zone.color }"
                    ></span>
                    <small>{{ zone.name }}</small>
                </li>
            </template>
        </ul>
    </div>
</template>

<script setup>
    import { useTranslations } from '../composables/useTranslations'
    const { $t } = useTranslations()

    const props = defineProps({
        zones: {
            type: Array,
            default: () => []
        },
        showZones: {
            type: Boolean,
            default: true
        }
    })
</script>

<style scoped>
    .slot-zone-indicator {
        display: inline-block;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: white;
        border: 2px solid;
        flex-shrink: 0;
    }
</style>