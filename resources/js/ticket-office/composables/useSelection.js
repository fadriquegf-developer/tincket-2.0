import { ref } from 'vue'

export function useSelection() {
  const selectedElements = ref([])

  const clearSelection = (elements) => {
    elements.forEach(el => {
      el.classList.remove('selected')
    })
    selectedElements.value = []
  }

  const addToSelection = (element) => {
    if (!selectedElements.value.includes(element)) {
      selectedElements.value.push(element)
      element.classList.add('selected')
    }
  }

  const removeFromSelection = (element) => {
    const index = selectedElements.value.indexOf(element)
    if (index > -1) {
      selectedElements.value.splice(index, 1)
      element.classList.remove('selected')
    }
  }

  const toggleSelection = (element) => {
    if (selectedElements.value.includes(element)) {
      removeFromSelection(element)
    } else {
      addToSelection(element)
    }
  }

  return {
    selectedElements,
    clearSelection,
    addToSelection,
    removeFromSelection,
    toggleSelection
  }
}