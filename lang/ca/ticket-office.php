<?php

return [
    'tickets_office' => 'Taquilla',
    'all_sessions' => '(inclou històric de sessions)',
    'client' => 'Client',
    'email' => 'Correu Electrònic',
    'create' => 'Crear',
    'firstname' => 'Nom',
    'lastname' => 'Cognom',
    'inscriptions_set' => 'Inscripcions',
    'rate' => 'Tarifa',
    'slot' => 'Posició en mapa',
    'add_inscription' => 'Afegir Inscripció',
    'there_is_only' => 'Només hi ha ',
    'zoom_help' => 'Per fer zoom, utilitza la roda del ratolí, i es farà zoom sobre la zona que estiguis marcant amb el ratolí',
    'free_slots_in_session' => ' butaques lliures en la sessió.',
    'tickets_sold' => 'Entrades venudes',
    'add_to_cart' => 'Afegir la cistella',
    'session' => 'Sessió',
    'sessions' => 'Sessions',
    'packs' => 'Paquets',
    'pack' => 'Paquet',
    'inscriptions' => 'Inscripcions',
    'new_pack' => 'Afegir paquet',
    'select_the_pack' => 'Selecciona el paquet',
    'show_all_packs' => 'Mostrar tots els packs',
    'select_the_sessions' => 'Selecciona les sessions que estaran en el paquet',
    'select_slots_for' => 'Selecciona butaques per a',
    'select_at_least' => 'Selecciona, com a mínim ',
    'sessions_to_sell_this_pack' => 'sessions per a vendre en aquest paquet',
    'pendent' => 'Pendent',
    'sessio_no_numerada' => 'Sessió no numerada',
    'reset' => 'Reiniciar',
    'how_many_packs' => 'Quants paquets',
    'payment' => 'Pagament',
    'payment_code' => 'Codi de pagament',
    'paid_at' => 'Data Pagament',
    'payment_platform' => 'Plataforma de pagament',
    'price' => 'Preu',
    'delete' => 'Eliminar',
    'delete_item' => 'Eliminar element',
    'deleteitem' => 'Eliminar element',
    'confirm_cart' => 'Confirmar cistella',
    'add' => 'Afegir selecció',
    'remove' => 'Treure selecció',
    'end_selection' => 'Finalitzar selecció',
    'select_session' => 'Selecciona una sessió',
    'show_all_sessions' => 'Mostrar totes les sessions',
    'payment_type' => [
        'cash' => 'En efectiu',
        'card' => 'Targeta de crèdit'
    ],
    'total' => 'Total',

    // Gift Cards
    'gift_cards' => 'Targetes regal',
    'gift_card' => [
        'gift_cards' => 'Targetes regal',
        'validate' => 'Validar',
        'code' => 'Codi',
        'select_the_sessions' => 'Selecciona la sessió'
    ],

    // SVG Layout Legend
    'svg_layout' => [
        'legend' => [
            'available' => 'Disponible',
            'selected' => 'Seleccionat',
            'sold' => 'Venut',
            'booked' => 'Reservat',
            'booked_packs' => 'Reservat per paquets',
            'hidden' => 'Ocult',
            'locked' => 'Bloquejat',
            'covid19' => 'COVID-19',
            'disability' => 'Mobilitat reduïda'
        ],
        'help' => 'Pots seleccionar múltiples butaques mantenint premut Ctrl (Cmd en Mac) mentre fas clic, o arrossegant per crear una selecció.'
    ],
    // Generales que faltan
    'loading' => 'Carregant...',
    'next' => 'Següent',
    'previous' => 'Anterior',
    'close' => 'Tancar',
    'save' => 'Guardar',
    'cancel' => 'Cancel·lar',

    // Modal específicos
    'select_session' => 'Selecciona una sessió',
    'layout_modal' => [
        'title' => 'Seleccionar assentaments',
        'session_info' => 'Informació de la sessió',
        'selection_help' => 'Ajuda per a la selecció'
    ],

    // Pack modal específicos que pueden faltar
    'pack_modal' => [
        'title' => 'Configurar paquet',
        'step_1' => 'Pas 1: Seleccionar paquet',
        'step_2' => 'Pas 2: Seleccionar sessions',
        'step_3' => 'Pas 3: Seleccionar assentaments'
    ],

    // Gift card específicos
    'gift_card' => [
        'gift_cards' => 'Targetes regal',
        'validate' => 'Validar',
        'code' => 'Codi',
        'select_the_sessions' => 'Selecciona la sessió',
        // NUEVOS:
        'validation_error' => 'Error de validació',
        'code_already_in_cart' => 'Ja tens aquest codi a la cistella',
        'code_not_found' => 'No s\'ha trobat el codi o ja s\'ha reclamat',
        'validating' => 'Validant...'
    ],

    // Estados de carga
    'loading_states' => [
        'loading_app' => 'Carregant aplicació...',
        'loading_sessions' => 'Carregant sessions...',
        'loading_layout' => 'Carregant mapa...',
        'validating_code' => 'Validant codi...',
        'processing' => 'Processant...'
    ],

    // Errores comunes
    'errors' => [
        'generic' => 'S\'ha produït un error',
        'network' => 'Error de xarxa',
        'loading_failed' => 'Error en carregar',
        'session_not_found' => 'Sessió no trobada',
        'slot_not_available' => 'Aquest assentament ja no està disponible',
        'limit_per_user_exceeded' => 'Límit assolit per ":session". Aquest usuari ja ha comprat :buyed entrada(es). Màxim permès: :max. Només en pot comprar :available més.',
        'session_no_web_rates' =>   "La sessió ':name' (ID: :id) no té tarifes web vàlides configurades.\n\n" .
                                    "Per solucionar-ho:\n" .
                                    "1. Ves a Admin > Sessions\n" .
                                    "2. Edita la sessió ':name'\n" .
                                    "3. A la secció 'Tarifes', marca’n almenys una com a 'Disponible per al Client Web'\n" .
                                    "4. Desa els canvis",
    ],

    // Mensajes de confirmación
    'confirmations' => [
        'remove_inscription' => 'Estàs segur que vols eliminar aquesta inscripció?',
        'remove_pack' => 'Estàs segur que vols eliminar aquest paquet?',
        'clear_selection' => 'Estàs segur que vols reiniciar la selecció?'
    ],

    // Accesibilidad
    'accessibility' => [
        'close_modal' => 'Tancar modal',
        'previous_session' => 'Sessió anterior',
        'next_session' => 'Sessió següent',
        'zoom_in' => 'Fer zoom',
        'zoom_out' => 'Reduir zoom',
        'reset_zoom' => 'Reiniciar zoom'
    ],

    // Información de ayuda
    'help' => [
        'multiple_selection' => 'Mantén premut Ctrl per seleccionar múltiples assentaments',
        'drag_selection' => 'Arrossega per seleccionar múltiples assentaments',
        'zoom_controls' => 'Utilitza els controls de zoom per navegar pel mapa'
    ]
];
