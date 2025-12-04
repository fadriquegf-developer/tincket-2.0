<?php

return [
    'tickets_office' => 'Taquilla',
    'all_sessions' => '(incluye histórico de sesiones)',
    'client' => 'Cliente',
    'email' => 'Correo electrónico',
    'create' => 'Crear',
    'firstname' => 'Nombre',
    'lastname' => 'Apellido',
    'inscriptions_set' => 'Inscripciones',
    'rate' => 'Tarifa',
    'slot' => 'Posición en mapa',
    'add_inscription' => 'Añadir inscripción',
    'there_is_only' => 'Solo hay ',
    'zoom_help' => 'Para hacer zoom, utiliza la rueda del ratón, y se hará zoom sobre la zona que estés marcando con el ratón',
    'free_slots_in_session' => ' asientos libres en la sesión.',
    'tickets_sold' => 'Entradas vendidas',
    'add_to_cart' => 'Añadir a la cesta',
    'session' => 'Sesión',
    'sessions' => 'Sesiones',
    'packs' => 'Paquetes',
    'pack' => 'Paquete',
    'inscriptions' => 'Inscripciones',
    'new_pack' => 'Añadir paquete',
    'select_the_pack' => 'Selecciona el paquete',
    'show_all_packs' => 'Mostrar todos los paquetes',
    'select_the_sessions' => 'Selecciona las sesiones que estarán en el paquete',
    'select_slots_for' => 'Selecciona asientos para',
    'select_at_least' => 'Selecciona, como mínimo ',
    'sessions_to_sell_this_pack' => 'sesiones para vender en este paquete',
    'pendent' => 'Pendiente',
    'sessio_no_numerada' => 'Sesión no numerada',
    'reset' => 'Reiniciar',
    'how_many_packs' => 'Cuántos paquetes',
    'payment' => 'Pago',
    'payment_code' => 'Código de pago',
    'paid_at' => 'Fecha de pago',
    'payment_platform' => 'Plataforma de pago',
    'price' => 'Precio',
    'delete' => 'Eliminar',
    'delete_item' => 'Eliminar elemento',
    'deleteitem' => 'Eliminar elemento',
    'confirm_cart' => 'Confirmar cesta',
    'add' => 'Añadir selección',
    'remove' => 'Quitar selección',
    'end_selection' => 'Finalizar selección',
    'select_session' => 'Selecciona una sesión',
    'show_all_sessions' => 'Mostrar todas las sesiones',
    'payment_type' => [
        'cash' => 'En efectivo',
        'card' => 'Tarjeta de crédito'
    ],
    'total' => 'Total',

    // Gift Cards
    'gift_cards' => 'Tarjetas regalo',
    'gift_card' => [
        'gift_cards' => 'Tarjetas regalo',
        'validate' => 'Validar',
        'code' => 'Código',
        'select_the_sessions' => 'Selecciona la sesión',
        'validation_error' => 'Error de validación',
        'code_already_in_cart' => 'Ya tienes este código en la cesta',
        'code_not_found' => 'No se ha encontrado el código o ya se ha reclamado',
        'validating' => 'Validando...'
    ],

    // SVG Layout Legend
    'svg_layout' => [
        'legend' => [
            'available' => 'Disponible',
            'selected' => 'Seleccionado',
            'sold' => 'Vendido',
            'booked' => 'Reservado',
            'booked_packs' => 'Reservado por paquetes',
            'hidden' => 'Oculto',
            'locked' => 'Bloqueado',
            'covid19' => 'COVID-19',
            'disability' => 'Movilidad reducida'
        ],
        'help' => 'Puedes seleccionar múltiples asientos manteniendo pulsado Ctrl (Cmd en Mac) mientras haces clic, o arrastrando para crear una selección.'
    ],

    // Generales
    'loading' => 'Cargando...',
    'next' => 'Siguiente',
    'previous' => 'Anterior',
    'close' => 'Cerrar',
    'save' => 'Guardar',
    'cancel' => 'Cancelar',

    // Modal específicos
    'layout_modal' => [
        'title' => 'Seleccionar asientos',
        'session_info' => 'Información de la sesión',
        'selection_help' => 'Ayuda para la selección'
    ],

    // Pack modal
    'pack_modal' => [
        'title' => 'Configurar paquete',
        'step_1' => 'Paso 1: Seleccionar paquete',
        'step_2' => 'Paso 2: Seleccionar sesiones',
        'step_3' => 'Paso 3: Seleccionar asientos'
    ],

    // Estados de carga
    'loading_states' => [
        'loading_app' => 'Cargando aplicación...',
        'loading_sessions' => 'Cargando sesiones...',
        'loading_layout' => 'Cargando mapa...',
        'validating_code' => 'Validando código...',
        'processing' => 'Procesando...'
    ],

    // Errores comunes
    'errors' => [
        'generic' => 'Se ha producido un error',
        'network' => 'Error de red',
        'loading_failed' => 'Error al cargar',
        'session_not_found' => 'Sesión no encontrada',
        'slot_not_available' => 'Este asiento ya no está disponible',
        'limit_per_user_exceeded' => 'Límite alcanzado para ":session". Este usuario ya ha comprado :buyed entrada(s). Máximo permitido: :max. Solo puede comprar :available más.',
        'session_no_web_rates' =>   "La sesión ':name' (ID: :id) no tiene tarifas web válidas configuradas.\n\n" .
                                    "Para solucionar esto:\n" .
                                    "1. Ve a Admin > Sesiones\n" .
                                    "2. Edita la sesión ':name'\n" .
                                    "3. En la sección 'Tarifas', marca al menos una como 'Disponible para el Cliente Web'\n" .
                                    "4. Guarda los cambios",
    ],

    // Confirmaciones
    'confirmations' => [
        'remove_inscription' => '¿Estás seguro de que quieres eliminar esta inscripción?',
        'remove_pack' => '¿Estás seguro de que quieres eliminar este paquete?',
        'clear_selection' => '¿Estás seguro de que quieres reiniciar la selección?'
    ],

    // Accesibilidad
    'accessibility' => [
        'close_modal' => 'Cerrar modal',
        'previous_session' => 'Sesión anterior',
        'next_session' => 'Sesión siguiente',
        'zoom_in' => 'Ampliar',
        'zoom_out' => 'Reducir',
        'reset_zoom' => 'Reiniciar zoom'
    ],

    // Ayuda
    'help' => [
        'multiple_selection' => 'Mantén pulsado Ctrl para seleccionar múltiples asientos',
        'drag_selection' => 'Arrastra para seleccionar múltiples asientos',
        'zoom_controls' => 'Utiliza los controles de zoom para navegar por el mapa'
    ]
];
