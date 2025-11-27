import { ref } from 'vue'

const translations = ref(window.translations || {})
const currentLocale = ref(window.currentLocale || 'es')

export function useTranslations() {
  /**
   * Obtener traducción por clave con soporte para claves anidadas
   * @param {string} key - Clave de traducción (ej: 'client', 'gift_card.validate')
   * @param {object} replacements - Reemplazos para placeholders
   * @returns {string}
   */
  const $t = (key, replacements = {}) => {
    // Dividir la clave por puntos para navegar objetos anidados
    const keys = key.split('.')
    let value = translations.value
    
    // Navegar por el objeto de traducciones
    for (const k of keys) {
      if (value && typeof value === 'object' && value.hasOwnProperty(k)) {
        value = value[k]
      } else {
        // Si no se encuentra la traducción, devolver la clave
        console.warn(`Translation key not found: ${key}`)
        return key
      }
    }
    
    // Si el valor final no es string, devolver la clave
    if (typeof value !== 'string') {
      console.warn(`Translation value is not a string for key: ${key}`)
      return key
    }
    
    // Realizar reemplazos si existen
    let result = value
    for (const [placeholder, replacement] of Object.entries(replacements)) {
      const regex = new RegExp(`:${placeholder}`, 'g')
      result = result.replace(regex, replacement)
    }
    
    return result
  }
  
  /**
   * Verificar si existe una traducción
   * @param {string} key 
   * @returns {boolean}
   */
  const hasTranslation = (key) => {
    const keys = key.split('.')
    let value = translations.value
    
    for (const k of keys) {
      if (value && typeof value === 'object' && value.hasOwnProperty(k)) {
        value = value[k]
      } else {
        return false
      }
    }
    
    return typeof value === 'string'
  }
  
  /**
   * Obtener todas las traducciones
   * @returns {object}
   */
  const getAllTranslations = () => {
    return translations.value
  }
  
  /**
   * Obtener locale actual
   * @returns {string}
   */
  const getLocale = () => {
    return currentLocale.value
  }
  
  return {
    $t,
    hasTranslation,
    getAllTranslations,
    getLocale,
    translations,
    currentLocale
  }
}