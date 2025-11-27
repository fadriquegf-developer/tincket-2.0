export const roundToDecimals = (number, decimals = 0) => {
    const factor = Math.pow(10, decimals)
    return Math.round(number * factor) / factor
}

export const getTranslationWithFallback = (translations, preferredLocale = null) => {
    if (typeof translations === 'string') {
        return translations
    }

    if (!translations || typeof translations !== 'object') {
        return 'Sin nombre'
    }

    const currentLocale = preferredLocale || document.documentElement.lang || 'es'
    const fallbackOrder = ['ca', 'es', 'en']

    // 1. Intentar idioma preferido
    if (translations[currentLocale]?.trim()) {
        return translations[currentLocale]
    }

    // 2. Fallback en orden de preferencia
    for (let locale of fallbackOrder) {
        if (translations[locale]?.trim()) {
            return translations[locale]
        }
    }

    // 3. Devolver cualquier traducción disponible
    for (let key in translations) {
        if (translations[key]?.trim()) {
            return translations[key]
        }
    }

    return 'Sin nombre'
}

export const formatPrice = (price) => {
    return parseFloat(price || 0).toFixed(2)
}

export const showNotification = (title, text, type = 'info') => {
    if (window.PNotify) {
        new window.PNotify({
            title,
            text,
            type
        })
    } else {
        // Fallback a alert nativo
        alert(`${title}: ${text}`)
    }
}
