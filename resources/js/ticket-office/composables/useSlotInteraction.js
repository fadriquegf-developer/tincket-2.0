import { ref } from 'vue'

export function useSlotInteraction() {
  const touchState = ref({
    hasMoved: false,
    startX: 0,
    startY: 0
  })

  const handlePointerDown = (event) => {
    touchState.value.hasMoved = false
    touchState.value.startX = event.clientX
    touchState.value.startY = event.clientY
  }

  const handlePointerMove = (event) => {
    const deltaX = Math.abs(event.clientX - touchState.value.startX)
    const deltaY = Math.abs(event.clientY - touchState.value.startY)

    if (!touchState.value.hasMoved && (deltaX > 10 || deltaY > 10)) {
      touchState.value.hasMoved = true
    }
  }

  const handlePointerUp = (callback) => {
    if (!touchState.value.hasMoved && typeof callback === 'function') {
      callback()
    }
  }

  const setupSlotEvents = (slotElement, clickHandler) => {
    const pointerDownHandler = (e) => handlePointerDown(e)
    const pointerMoveHandler = (e) => handlePointerMove(e)
    const pointerUpHandler = () => handlePointerUp(clickHandler)

    slotElement.addEventListener('pointerdown', pointerDownHandler)
    slotElement.addEventListener('pointermove', pointerMoveHandler)
    slotElement.addEventListener('pointerup', pointerUpHandler)

    // También manejar click normal como fallback
    slotElement.addEventListener('click', (e) => {
      e.preventDefault()
      clickHandler()
    })

    // Función para limpiar eventos
    return () => {
      slotElement.removeEventListener('pointerdown', pointerDownHandler)
      slotElement.removeEventListener('pointermove', pointerMoveHandler)
      slotElement.removeEventListener('pointerup', pointerUpHandler)
    }
  }

  return {
    touchState,
    handlePointerDown,
    handlePointerMove,
    handlePointerUp,
    setupSlotEvents
  }
}