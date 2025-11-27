import {
    defineStore
} from 'pinia'
import {
    ref
} from 'vue'

export const useSlotMapStore = defineStore('slotMap', () => {
    const slotMap = ref(null)
    const zoomistPack = ref(null)
    const zoomistInscription = ref(null)
    const zoomistGift = ref(null)

    const loadSessionMap = async (sessionId) => {
        slotMap.value = null

        const url = `/api/session/${sessionId}/configuration?${Math.random()}`

        try {
            if (window.showLoading) window.showLoading()

            const response = await fetch(url)
            const data = await response.json()
            slotMap.value = data

            if (data.space && data.zones) {
                data.space.zones = data.zones.map(z => ({
                    id: z.id,
                    name: z.name,
                    color: z.color
                }))
            }
        
            return data
        } catch (error) {
            console.error('Error loading session map:', error)
            return null
        } finally {
            if (window.hideLoading) window.hideLoading()
        }
    }

    const getSlot = (id) => {
        if (!slotMap.value || !slotMap.value.zones) return false

        const allSlots = slotMap.value.zones
            .map(zone => zone.slots)
            .flat()

        return allSlots.find(slot => slot.id == id)
    }

    const isReady = () => {
        return slotMap.value !== null
    }

    const getZoomistInstance = (type) => {
        switch (type) {
            case 'pack':
                return zoomistPack.value
            case 'gift':
                return zoomistGift.value
            case 'inscription':
            default:
                return zoomistInscription.value
        }
    }

    const setZoomistInstance = (type, instance) => {
        switch (type) {
            case 'pack':
                zoomistPack.value = instance
                break
            case 'gift':
                zoomistGift.value = instance
                break
            case 'inscription':
            default:
                zoomistInscription.value = instance
                break
        }
    }

    return {
        slotMap,
        zoomistPack,
        zoomistInscription,
        zoomistGift,
        loadSessionMap,
        getSlot,
        isReady,
        getZoomistInstance,
        setZoomistInstance
    }
})
