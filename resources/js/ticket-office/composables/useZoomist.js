import {
    ref,
    onUnmounted
} from 'vue'

export function useZoomist() {
    const zoomistInstance = ref(null)
    const middleMouseActive = ref(false)

    const initZoomist = (containerSelector, options = {}) => {
        if (!window.Zoomist) {
            console.warn('Zoomist library not loaded')
            return null
        }

        // Destruir instancia anterior
        destroyZoomist()

        const defaultOptions = {
            maxScale: 7,
            bounds: true,
            slider: true,
            zoomer: false,
            draggable: false, // 🎯 Desactivar drag con botón izquierdo
            on: {
                ready() {
                    setupMiddleMouseDrag(containerSelector)
                }
            }
        }

        try {
            zoomistInstance.value = new window.Zoomist(containerSelector, {
                ...defaultOptions,
                ...options
            })
            return zoomistInstance.value
        } catch (error) {
            console.error('Error initializing Zoomist:', error)
            return null
        }
    }

    const setupMiddleMouseDrag = (containerSelector) => {
        const container = document.querySelector(containerSelector)
        if (!container) return

        const zoomistWrapper = container.querySelector('.zoomist-wrapper')
        if (!zoomistWrapper) return

        let isDragging = false
        let startX = 0
        let startY = 0
        let scrollLeft = 0
        let scrollTop = 0

        // Prevenir el comportamiento por defecto del botón central
        zoomistWrapper.addEventListener('mousedown', (e) => {
            if (e.button === 1) { // Botón central
                e.preventDefault()
                isDragging = true
                startX = e.pageX
                startY = e.pageY

                // Obtener posición actual del contenido
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
            }
        })

        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return

            e.preventDefault()
            const x = e.pageX - startX
            const y = e.pageY - startY

            // Usar la API de Zoomist si está disponible
            if (zoomistInstance.value && zoomistInstance.value.move) {
                zoomistInstance.value.move({
                    x: scrollLeft + x,
                    y: scrollTop + y
                })
            }
        })

        document.addEventListener('mouseup', (e) => {
            if (e.button === 1 && isDragging) {
                isDragging = false
                zoomistWrapper.style.cursor = 'default'
            }
        })

        // Prevenir el scroll automático del navegador con botón central
        zoomistWrapper.addEventListener('auxclick', (e) => {
            if (e.button === 1) {
                e.preventDefault()
            }
        })
    }

    const destroyZoomist = () => {
        if (zoomistInstance.value) {
            try {
                zoomistInstance.value.destroy()
            } catch (error) {
                console.warn('Error destroying Zoomist:', error)
            } finally {
                zoomistInstance.value = null
            }
        }
    }

    const resetZoomist = () => {
        if (zoomistInstance.value) {
            try {
                zoomistInstance.value.reset()
            } catch (error) {
                console.warn('Error resetting Zoomist:', error)
            }
        }
    }

    onUnmounted(() => {
        destroyZoomist()
    })

    return {
        zoomistInstance,
        initZoomist,
        destroyZoomist,
        resetZoomist
    }
}
