<template>
    <div ref="spaceLayoutContainer" class="space-layout-container" :class="{
        'pack': typeModel === 'pack' || currentSession?.zoom,
        'selection-wrap': true
    }">
        <!-- El SVG se cargará aquí dinámicamente -->
    </div>
</template>

<script setup>
import { ref, watch, onMounted, inject, nextTick, onUnmounted, computed } from 'vue'

const props = defineProps({
    layoutUrl: {
        type: String,
        required: true
    },
    layoutSession: {
        type: Object,
        required: true
    },
    typeModel: {
        type: String,
        default: 'inscription'
    }
})

const emit = defineEmits(['add-inscription', 'remove-inscription'])

// Refs
const spaceLayoutContainer = ref(null)
const zoomistInstance = ref(null)
const selectionInstance = ref(null)

// Inject stores
const inscriptionStore = inject('inscriptionStore')
const packStore = inject('packStore')
const giftStore = inject('giftStore')
const slotMapStore = inject('slotMapStore')

// Estado local
const currentSession = ref(props.layoutSession)
const slotStatus = window.slotStatus || {}
let hasMoved = 0
let touchStartX = 0
let touchStartY = 0
let updateTimeout = null
let slotEventHandlers = new Map()

// Popover personalizado
const popoverEl = ref(null)
const currentPopoverNode = ref(null)
const popoverTimeout = ref(null)

// Colores EXACTOS de sesiones
const STATUS_STROKE_COLORS = {
    null: "#008102",
    1: "#ffa500",
    2: "#e53935",
    3: "#800080",
    4: "#7b68ee",
    5: "#ffffff",
    6: "#696969",
    7: "#b9b9b9",
    8: "#0368ae",
}

const STROKE_WIDTH = 1.25

const hasSingleZone = computed(() => {
    const zones = currentSession.value?.space?.zones || []
    return zones.length <= 1
})

// Estado de carga para evitar llamadas duplicadas
const isLoadingLayout = ref(false)
const hasInitialized = ref(false)

const debugLog = (message, data = null) => {
    console.log(`[SpaceLayout] ${message}`, data || '')
}

// Métodos del popover (sin cambios)
const createPopover = () => {
    if (!popoverEl.value) {
        const div = document.createElement('div')
        div.className = 'slot-popover'
        div.style.cssText = `
            position: fixed;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 13px;
            line-height: 1.5;
            pointer-events: none;
            z-index: 99999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            max-width: 250px;
            word-wrap: break-word;
            display: none;
        `
        document.body.appendChild(div)
        popoverEl.value = div
    }
}

const showPopover = (node, e) => {
    const slotId = node.dataset.slotId || node.getAttribute('data-slot-id')
    if (!slotId) return

    const slot = slotMapStore.getSlot(parseInt(slotId))
    if (!slot) return

    createPopover()

    let content = ''
    const statusColor = slot.lock_reason
        ? (STATUS_STROKE_COLORS[slot.lock_reason] || "#666")
        : STATUS_STROKE_COLORS[null]

    if (slot.name) {
        content += `<div style="font-weight: 600; margin-bottom: 5px;">
            <span style="color: ${statusColor};">●</span> ${escapeHtml(slot.name)}
        </div>`
    }

    if (slot.lock_reason !== null && slotStatus[slot.lock_reason]) {
        content += `<div style="color: #ffffff; font-size: 12px; margin-bottom: 5px;">
            ${escapeHtml(slotStatus[slot.lock_reason])}
        </div>`
    }

    if (slot.comment) {
        content += `<div style="color: #e0e0e0; font-size: 12px;">
            ${escapeHtml(slot.comment)}
        </div>`
    }

    if (!content) return

    popoverEl.value.innerHTML = content
    currentPopoverNode.value = node

    // 🔥 Mostrar con visibility hidden para calcular posición
    popoverEl.value.style.display = 'block'
    popoverEl.value.style.visibility = 'hidden'
    positionPopover(e)

    // 🔥 Delay de 1 segundo antes de mostrar realmente
    if (popoverTimeout.value) clearTimeout(popoverTimeout.value)

    popoverTimeout.value = setTimeout(() => {
        if (currentPopoverNode.value === node && popoverEl.value) {
            popoverEl.value.style.visibility = 'visible'
        }
    }, 500)
}

const positionPopover = (e) => {
    if (!popoverEl.value) {
        return
    }

    const offset = 15
    let left = e.clientX + offset
    let top = e.clientY + offset

    const rect = popoverEl.value.getBoundingClientRect()

    if (left + rect.width > window.innerWidth) {
        left = e.clientX - rect.width - offset
    }

    if (top + rect.height > window.innerHeight) {
        top = e.clientY - rect.height - offset
    }

    popoverEl.value.style.left = left + 'px'
    popoverEl.value.style.top = top + 'px'

}

const hidePopover = () => {
    if (popoverTimeout.value) {
        clearTimeout(popoverTimeout.value)
        popoverTimeout.value = null
    }
    if (popoverEl.value) {
        popoverEl.value.style.visibility = 'hidden'
    }
    currentPopoverNode.value = null
}

const escapeHtml = (text) => {
    const div = document.createElement('div')
    div.textContent = text
    return div.innerHTML
}

const getZoneFillColor = (slot) => {
    return getStrokeColor(slot)
}

const getZoneStrokeColor = (slot) => {
    if (!slot.zone_id) return '#cccccc'

    const zones = currentSession.value?.space?.zones || []
    const zone = zones.find(z => z.id === slot.zone_id)

    return zone?.color || '#cccccc'
}

const getStrokeColor = (slot) => {
    const lockReason = slot.lock_reason ?? null
    return STATUS_STROKE_COLORS[lockReason] || STATUS_STROKE_COLORS[null]
}

const canSelectSlot = (slot) => {
    const hasInscription = getHasInscription(slot)
    if (hasInscription) return false

    const hasPackInscription = getHasPackInscription(slot)
    if (hasPackInscription && props.typeModel !== 'pack') return false

    // Verificar si el slot está ocupado por otro pack finalizado
    const hasAllPacksInscription = getHasAllPacksInscription(slot)
    if (hasAllPacksInscription) return false

    if (slot.lock_reason === 2) return false

    if (slot.is_locked && [1, 4, 5].includes(slot.lock_reason)) return false

    if (slot.is_locked && slot.lock_reason === 8 && props.typeModel === 'inscription') return false

    return true
}

const updateLayout = async () => {
    if (!props.layoutUrl || isLoadingLayout.value) return

    try {
        isLoadingLayout.value = true
        // 🔧 FIX: Si es sesión numerada, cargar slotMap primero
        if (currentSession.value?.id && currentSession.value.is_numbered) {
            await slotMapStore.loadSessionMap(currentSession.value.id)
        }
        debugLog('📥 Fetching SVG from:', props.layoutUrl)
        const response = await fetch(props.layoutUrl)

        if (!response.ok) {
            debugLog('❌ Fetch failed:', response.status)
            return
        }

        const svgContent = await response.text()
        debugLog('✅ SVG loaded, length:', svgContent.length)

        if (spaceLayoutContainer.value) {
            spaceLayoutContainer.value.innerHTML = `<object id='svg-object'>${svgContent}</object>`
            debugLog('✅ SVG injected into DOM')

            const svg = spaceLayoutContainer.value.querySelector('svg')
            if (svg) {
                svg.style.height = '90%'
                svg.style.width = '90%'
                debugLog('✅ SVG styles applied')
            }

            await nextTick()
            bindEventsToLayout()

            // 🔧 FIX: Actualizar mapa inmediatamente si está listo
            if (currentSession.value?.is_numbered && slotMapStore.isReady()) {
                updateSessionMap()
            }
        } else {
            debugLog('❌ spaceLayoutContainer is null')
        }
    } catch (error) {
        debugLog('❌ Error loading layout:', error)
        console.error('Error loading layout:', error)
    } finally {
        isLoadingLayout.value = false
    }
}

const bindEventsToLayout = () => {
    debugLog('bindEventsToLayout called')
    cleanupSlotEvents()

    const slots = spaceLayoutContainer.value?.querySelectorAll('.slot')

    if (!slots || slots.length === 0) {
        debugLog('⚠️ No slots found')
        return
    }

    debugLog(`✅ Found ${slots.length} slots`)

    slots.forEach((slot, index) => {
        const slotId = slot.dataset.slotId ||
            slot.getAttribute('data-slot-id') ||
            slot.getAttribute('slot-id') ||
            slot.id

        if (!slotId) {
            return
        }

        const handlers = createSlotHandlers(slot, slotId)
        slotEventHandlers.set(slot, handlers)

        slot.addEventListener('click', handlers.click)
        slot.addEventListener('pointerdown', handlers.pointerDown)
        slot.addEventListener('pointermove', handlers.pointerMove)
        slot.addEventListener('mouseenter', handlers.mouseEnter)
        slot.addEventListener('mousemove', handlers.mouseMove)
        slot.addEventListener('mouseleave', handlers.mouseLeave)

        slot.classList.add('clickable-slot')
        slot.style.cursor = 'pointer'
    })

    debugLog('✅ Events bound to all slots')

    setTimeout(function () { setupZoomist(); }, 100);

    if (props.typeModel !== 'pack' && !currentSession.value?.zoom) {
        setupMultipleSelection()
    }
}

const createSlotHandlers = (slotElement, slotId) => {
    let hideTimeout = null

    const handlers = {
        click: (e) => {
            e.preventDefault()
            e.stopPropagation()

            if (hasMoved === 0) {
                hidePopover()
                handleSlotClick(slotElement, slotId)
            }
        },
        pointerDown: (event) => {
            hasMoved = 0
            touchStartX = event.clientX
            touchStartY = event.clientY
            hidePopover()
        },
        pointerMove: (event) => {
            const touchX = event.clientX
            const touchY = event.clientY
            const deltaX = Math.abs(touchX - touchStartX)
            const deltaY = Math.abs(touchY - touchStartY)

            if (!hasMoved && (deltaX > 10 || deltaY > 10)) {
                hasMoved = 1
                if (popoverEl.value && popoverEl.value.style.display === 'block') {
                    hidePopover()
                }
            }
        },
        mouseEnter: (e) => {
            // Cancelar cualquier timeout de ocultar
            if (hideTimeout) {
                clearTimeout(hideTimeout)
                hideTimeout = null
            }
            showPopover(slotElement, e)
        },
        mouseLeave: () => {
            // 🔥 Cancelar el timeout de mostrar si sales antes de 1 segundo
            if (popoverTimeout.value) {
                clearTimeout(popoverTimeout.value)
                popoverTimeout.value = null
            }

            // Delay de 50ms antes de ocultar
            hideTimeout = setTimeout(() => {
                if (currentPopoverNode.value === slotElement) {
                    hidePopover()
                }
            }, 50)
        },
        mouseMove: (e) => {
            if (currentPopoverNode.value === slotElement && popoverEl.value) {
                positionPopover(e)
            }
        },
    }

    return handlers
}

const cleanupSlotEvents = () => {
    slotEventHandlers.forEach((handlers, slot) => {
        slot.removeEventListener('click', handlers.click)
        slot.removeEventListener('pointerdown', handlers.pointerDown)
        slot.removeEventListener('pointermove', handlers.pointerMove)
        slot.removeEventListener('mouseenter', handlers.mouseEnter)
        slot.removeEventListener('mousemove', handlers.mouseMove)
        slot.removeEventListener('mouseleave', handlers.mouseLeave)
    })
    slotEventHandlers.clear()
}

// 🎯 CLAVE: Solo añadir/quitar la clase "selected", NO cambiar stroke
const handleSlotClick = (slotElement, slotId) => {
    const slot = slotMapStore.getSlot(parseInt(slotId))
    if (!slot) {
        return
    }

    // 🎯 Verificar si ya tiene la clase "selected"
    const wasSelected = slotElement.classList.contains('selected')

    if (!wasSelected && !canSelectSlot(slot)) {
        return
    }

    if (slotElement._clicking) {
        return
    }

    slotElement._clicking = true
    setTimeout(() => {
        slotElement._clicking = false
    }, 300)

    if (!wasSelected) {
        // Añadir al carrito
        emit('add-inscription', currentSession.value, slot)

        if (props.typeModel === 'pack') {
            nextTick(() => {
                const wasAdded = getHasPackInscription(slot)
                if (wasAdded) {
                    // 🎯 Solo añadir clase, el CSS hará el resto
                    slotElement.classList.add('selected')
                }
            })
        } else if (props.typeModel === 'gift') {
            // Limpiar otras selecciones
            const allSlots = spaceLayoutContainer.value?.querySelectorAll('.slot.selected')
            allSlots?.forEach(s => {
                s.classList.remove('selected')
            })
            // 🎯 Solo añadir clase
            slotElement.classList.add('selected')
        } else {
            const wasAdded = getHasInscription(slot)
            if (wasAdded) {
                // 🎯 Solo añadir clase
                slotElement.classList.add('selected')
            }
        }
    } else {
        // Quitar del carrito
        emit('remove-inscription', currentSession.value, slot)
        // 🎯 Solo quitar clase
        slotElement.classList.remove('selected')
    }
}

const setupZoomist = () => {
    const container = spaceLayoutContainer.value?.querySelector('#svg-object')
    if (!container || !currentSession.value?.space?.zoom) return

    if (zoomistInstance.value) {
        try {
            zoomistInstance.value.destroy()
        } catch (error) {
            console.warn('Error destroying Zoomist:', error)
        }
        zoomistInstance.value = null
    }

    if (window.Zoomist && document.querySelector('.modal.show')) {
        const containerClass = `.zoomist-container-${props.typeModel}`
        const containerElement = document.querySelector(containerClass)

        if (containerElement) {
            try {
                zoomistInstance.value = new window.Zoomist(containerClass, {
                    maxScale: 7,
                    bounds: true,
                    slider: true,
                    zoomer: false,
                    draggable: false,
                    on: {
                        ready() {
                            setupMiddleMouseDrag(containerClass)
                        }
                    }
                })
            } catch (error) {
                console.warn('Error creating Zoomist:', error)
            }
        }
    }
}

const setupMiddleMouseDrag = (containerClass) => {
    const container = document.querySelector(containerClass)
    if (!container) return

    const zoomistWrapper = container.querySelector('.zoomist-wrapper')
    if (!zoomistWrapper) return

    let isDragging = false
    let startX = 0
    let startY = 0
    let scrollLeft = 0
    let scrollTop = 0
    let currentX = 0
    let currentY = 0
    let animationFrame = null

    const smoothMove = () => {
        if (!isDragging) return

        if (zoomistInstance.value && zoomistInstance.value.move) {
            zoomistInstance.value.move({
                x: currentX,
                y: currentY
            })
        }
    }

    zoomistWrapper.addEventListener('mousedown', (e) => {
        if (e.button === 1) {
            e.preventDefault()
            isDragging = true
            startX = e.pageX
            startY = e.pageY
            hidePopover()

            const zoomistImage = container.querySelector('.zoomist-image')
            if (zoomistImage) {
                const transform = window.getComputedStyle(zoomistImage).transform
                if (transform !== 'none') {
                    const matrix = new DOMMatrixReadOnly(transform)
                    scrollLeft = matrix.m41
                    scrollTop = matrix.m42
                }
            }

            zoomistWrapper.style.cursor = 'grabbing'
            zoomistWrapper.style.transition = 'none'
        }
    })

    document.addEventListener('mousemove', (e) => {
        if (!isDragging) return

        e.preventDefault()
        const x = e.pageX - startX
        const y = e.pageY - startY

        currentX = scrollLeft + x
        currentY = scrollTop + y

        if (animationFrame) {
            cancelAnimationFrame(animationFrame)
        }

        animationFrame = requestAnimationFrame(smoothMove)
    })

    document.addEventListener('mouseup', (e) => {
        if (e.button === 1 && isDragging) {
            isDragging = false
            zoomistWrapper.style.cursor = 'default'

            if (zoomistInstance.value) {
                const zoomistImage = container.querySelector('.zoomist-image')
                if (zoomistImage) {
                    zoomistImage.style.transition = 'transform 0.2s ease-out'
                    setTimeout(() => {
                        zoomistImage.style.transition = ''
                    }, 200)
                }
            }

            if (animationFrame) {
                cancelAnimationFrame(animationFrame)
                animationFrame = null
            }
        }
    })

    zoomistWrapper.addEventListener('auxclick', (e) => {
        if (e.button === 1) {
            e.preventDefault()
        }
    })
}

const setupMultipleSelection = () => {
    const container = spaceLayoutContainer.value?.querySelector('#svg-object')
    if (!container || !window.Selection) return

    if (selectionInstance.value) {
        try {
            selectionInstance.value.destroy()
        } catch (error) {
            console.warn('Error destroying Selection:', error)
        }
    }

    try {
        selectionInstance.value = window.Selection.create({
            class: 'selection-area',
            selectables: ['.slot.free'],
            boundaries: ['.selection-wrap:not(.pack)']
        })
            .on('start', ({ inst, selected, oe }) => {
                if (oe.button === 1) {
                    inst.cancel()
                    return false
                }

                hidePopover()
                inst.T.style.zIndex = 1150

                if (!oe.ctrlKey && !oe.metaKey) {
                    // 🔥 Al empezar sin Ctrl, quitar todas las selecciones previas
                    selected.forEach(el => {
                        const slotId = el.dataset.slotId || el.getAttribute('data-slot-id')
                        if (slotId) {
                            const slot = slotMapStore.getSlot(parseInt(slotId))
                            const hasInscription = getHasInscription(slot)

                            // Si está en el carrito, quitarla
                            if (hasInscription) {
                                emit('remove-inscription', currentSession.value, slot)
                            }
                        }
                        el.classList.remove('selected')
                        inst.removeFromSelection(el)
                    })
                    inst.clearSelection()
                }
            })
            .on('move', ({ changed: { removed, added } }) => {
                // Durante el arrastre, solo gestionar clases visuales
                added.forEach(el => {
                    const slotId = el.dataset.slotId || el.getAttribute('data-slot-id')
                    if (slotId) {
                        const slot = slotMapStore.getSlot(parseInt(slotId))
                        if (slot && canSelectSlot(slot)) {
                            el.classList.add('selected')
                        }
                    }
                })
                removed.forEach(el => el.classList.remove('selected'))
            })
            .on('stop', ({ inst }) => {
                // 🔥 Al soltar, añadir/quitar del carrito según corresponda
                debugLog('Selection finished, processing slots')

                // Obtener todas las butacas que ESTÁN seleccionadas ahora
                const currentlySelected = new Set()
                document.querySelectorAll('.slot.selected').forEach(el => {
                    const slotId = el.dataset.slotId || el.getAttribute('data-slot-id')
                    if (slotId) {
                        currentlySelected.add(slotId)
                    }
                })

                // Procesar cada butaca seleccionada
                currentlySelected.forEach(slotId => {
                    const slotElement = document.querySelector(`.slot[data-slot-id="${slotId}"]`)
                    if (!slotElement) return

                    const slot = slotMapStore.getSlot(parseInt(slotId))
                    if (!slot) return

                    const hasInscription = getHasInscription(slot)

                    if (!hasInscription) {
                        // No está en el carrito → añadirla
                        if (canSelectSlot(slot)) {
                            debugLog(`Adding slot ${slotId} to cart`)
                            emit('add-inscription', currentSession.value, slot)
                        } else {
                            // No se puede añadir → quitar clase visual
                            slotElement.classList.remove('selected')
                        }
                    }
                    // Si ya está en el carrito, no hacer nada (mantener)
                })

                inst.keepSelection()
            })
    } catch (error) {
        console.warn('Error creating Selection:', error)
    }
}

// 🎯 updateSessionMap: Solo establecer fill y stroke base, NO tocar las seleccionadas
const updateSessionMap = () => {
    if (!currentSession.value?.is_numbered || !slotMapStore.isReady()) {
        const retryCount = (updateTimeout?._retryCount || 0) + 1

        if (retryCount > 5) {
            console.error('[SpaceLayout] SlotMap not ready after 5 retries')
            return
        }

        if (updateTimeout) clearTimeout(updateTimeout)
        updateTimeout = setTimeout(updateSessionMap, 500)
        updateTimeout._retryCount = retryCount
        return
    }

    if (updateTimeout) {
        clearTimeout(updateTimeout)
        updateTimeout = null
    }

    const slots = spaceLayoutContainer.value?.querySelectorAll('.slot')
    if (!slots || slots.length === 0) {
        debugLog('⚠️ No slots found')
        return
    }

    debugLog(`🎨 Updating ${slots.length} slots`)

    let count = 0

    requestAnimationFrame(() => {
        slots.forEach(slotElement => {
            const slotId = slotElement.dataset.slotId || slotElement.getAttribute('data-slot-id')
            if (!slotId) return

            const slot = slotMapStore.getSlot(parseInt(slotId))
            if (!slot) return

            if (slotElement.dataset.bsPopover) {
                try {
                    window.bootstrap?.Popover.getInstance(slotElement)?.dispose()
                } catch (e) {
                    // Ignore
                }
            }

            slotElement.classList.remove('free', 'border-slot')

            const hasInscription = getHasInscription(slot)
            const hasPackInscription = getHasPackInscription(slot)
            const hasAllPacksInscription = getHasAllPacksInscription(slot)

            // 🎯 FILL = color de zona (SIEMPRE)
            const slotFillColor = getZoneFillColor(slot)
            slotElement.style.fill = slotFillColor

            // STROKE según número de zonas
            if (hasSingleZone.value) {
                // Con zona única: stroke verde para libres
                slotElement.style.stroke = slot.lock_reason ? getStrokeColor(slot) : '#008102'
                slotElement.style.strokeWidth = STROKE_WIDTH
            } else {
                // Con múltiples zonas: stroke = color de zona (más fino)
                slotElement.style.stroke = getZoneStrokeColor(slot)
                slotElement.style.strokeWidth = 1  // Más fino para múltiples zonas
            }
            slotElement.style.strokeWidth = STROKE_WIDTH

            // 🎯 PRIORIDAD 1: Butacas con inscripción (vendidas o en carrito)
            if (hasInscription && props.typeModel === 'inscription') {
                // En nuestro carrito → seleccionable para quitar
                slotElement.classList.add('selected')
                slotElement.classList.add('free')
                count++
            } else if (hasPackInscription && props.typeModel === 'pack') {
                // En nuestro pack → seleccionable para quitar
                slotElement.classList.add('selected')
                slotElement.classList.add('free')
            } else if (hasInscription || hasPackInscription || hasAllPacksInscription) {
                // 🎯 CLAVE: Vendida por OTRO → BLOQUEADA sin importar lock_reason
                slotElement.style.fill = getZoneFillColor({ ...slot, lock_reason: 2 })
                if (hasSingleZone.value) {
                    slotElement.style.stroke = STATUS_STROKE_COLORS[2]
                }
                slotElement.style.cursor = 'not-allowed'
                removeSlotEvents(slotElement)
                count++
            } else {
                // Determinar si es seleccionable según lock_reason
                if (canSelectSlot(slot)) {
                    slotElement.classList.add('free')
                    slotElement.style.cursor = 'pointer'
                } else {
                    slotElement.style.cursor = 'not-allowed'
                    removeSlotEvents(slotElement)
                }

                if (slot.lock_reason === 5) {
                    slotElement.classList.add('border-slot')
                }

                if (slot.is_locked && slot.lock_reason === 2) {
                    count++
                }
            }
        })

        if (currentSession.value) {
            currentSession.value.sold = count
        }

        if (updateTimeout) {
            clearTimeout(updateTimeout)
            updateTimeout = null
        }

        debugLog('✅ Session map updated')
    })
}

const removeSlotEvents = (slotElement) => {
    slotElement.classList.remove('free')
    slotElement.style.cursor = 'not-allowed'

    const handlers = slotEventHandlers.get(slotElement)
    if (handlers) {
        slotElement.removeEventListener('click', handlers.click)
        slotElement.removeEventListener('pointerdown', handlers.pointerDown)
        slotElement.removeEventListener('pointermove', handlers.pointerMove)
        slotElement.removeEventListener('mouseenter', handlers.mouseEnter)
        slotElement.removeEventListener('mousemove', handlers.mouseMove)
        slotElement.removeEventListener('mouseleave', handlers.mouseLeave)
        slotEventHandlers.delete(slotElement)
    }
}

const getHasInscription = (slot) => {
    return inscriptionStore.hasInscription(currentSession.value, slot)
}

const getHasPackInscription = (slot) => {
    return packStore.hasInscription(currentSession.value, slot)
}

const getHasAllPacksInscription = (slot) => {
    return packStore.hasInscriptionAllPacks(currentSession.value, slot)
}

const addSelectedSlots = () => {
    const selectedSlots = document.querySelectorAll('.slot.selected')
    selectedSlots.forEach(slotElement => {
        const slotId = slotElement.dataset.slotId || slotElement.getAttribute('data-slot-id')
        if (slotId) {
            const slot = slotMapStore.getSlot(parseInt(slotId))
            // Solo si NO está ya en el carrito
            const hasInscription = getHasInscription(slot)
            if (slot && canSelectSlot(slot) && !hasInscription) {
                handleSlotClick(slotElement, slotId)
            }
        }
        slotElement.classList.remove('selected')
    })
}

const removeSelectedSlots = () => {
    const selectedSlots = document.querySelectorAll('.slot.selected')
    selectedSlots.forEach(slot => {
        const slotId = slot.dataset.slotId || slot.getAttribute('data-slot-id')
        if (slotId) {
            const slotData = slotMapStore.getSlot(parseInt(slotId))
            const hasInscription = getHasInscription(slotData)
            // Solo si YA está en el carrito
            if (hasInscription) {
                handleSlotClick(slot, slotId)
            }
        }
        slot.classList.remove('selected')
    })
}

if (typeof window !== 'undefined') {
    window.addSelectedSlots = addSelectedSlots
    window.removeSelectedSlots = removeSelectedSlots
}

watch(() => props.layoutUrl, (newUrl) => {
    if (hasInitialized.value) {
        updateLayout()
    }
})

watch(() => props.layoutSession, (newSession) => {
    if (!hasInitialized.value) return

    currentSession.value = newSession
    if (newSession?.id && newSession.is_numbered) {
        slotMapStore.loadSessionMap(newSession.id).then(() => {
            updateLayout()
        })
    } else if (newSession) {
        updateLayout()
    }
})

onMounted(() => {
    if (props.layoutSession) {
        currentSession.value = props.layoutSession

        if (currentSession.value?.id && currentSession.value.is_numbered) {
            slotMapStore.loadSessionMap(currentSession.value.id).then(() => {
                updateLayout().then(() => {
                    hasInitialized.value = true
                })
            })
        } else {
            updateLayout().then(() => {
                hasInitialized.value = true
            })
        }
    }
})

onUnmounted(() => {
    cleanupSlotEvents()

    if (zoomistInstance.value) {
        try {
            zoomistInstance.value.destroy()
        } catch (error) {
            console.warn('Error destroying Zoomist on unmount:', error)
        }
    }

    if (selectionInstance.value) {
        try {
            selectionInstance.value.destroy()
        } catch (error) {
            console.warn('Error destroying Selection on unmount:', error)
        }
    }

    if (updateTimeout) {
        clearTimeout(updateTimeout)
    }

    if (popoverEl.value) {
        popoverEl.value.remove()
        popoverEl.value = null
    }
    if (popoverTimeout.value) {
        clearTimeout(popoverTimeout.value)
    }
})
</script>

<style scoped>
.space-layout-container {
    width: 100%;
    height: 100%;
    position: relative;
    min-height: 400px;
}

.space-layout-container :deep(object),
.space-layout-container :deep(svg) {
    width: 100%;
    height: auto;
    max-height: 500px;
}

.selection-area {
    background: rgba(46, 115, 252, 0.11);
    border: 2px solid rgba(98, 155, 255, 0.81);
    border-radius: 0.25rem;
}

/* 🎯 CLAVE: Las butacas con clase "selected" tienen borde negro */
:deep(.slot.selected) {
    stroke: #000000 !important;
    stroke-width: 2 !important;
}

:deep(.slot.free) {
    cursor: pointer;
}

:deep(.slot.free:hover) {
    opacity: 0.8;
}

:deep(.border-slot) {
    stroke: #000 !important;
    stroke-width: 1 !important;
}

.pack :deep(.selection-wrap) {
    pointer-events: auto;
}

:deep(.zoomist-container) {
    width: 100%;
    max-height: 600px;
}

:deep(.zoomist-wrapper) {
    background: #f8f9fa;
    border-radius: 0.375rem;
}

:deep(.zoomist-image) {
    height: 100%;
    pointer-events: auto;
}
</style>