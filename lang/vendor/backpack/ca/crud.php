<?php

return [

    // Formularis
    'save_action_save_and_new' => 'Desar i nou element',
    'save_action_save_and_edit' => 'Desar i editar aquest element',
    'save_action_save_and_back' => 'Desar i tornar',
    'save_action_save_and_preview' => 'Desar i previsualitzar',
    'save_action_changed_notification' => 'El comportament per defecte després de desar ha canviat.',

    // Formulari de creació
    'add' => 'Afegir',
    'back_to_all' => 'Tornar a tots ',
    'cancel' => 'Cancel·lar',
    'add_a_new' => 'Afegir un nou ',

    // Formulari d'edició
    'edit' => 'Editar',
    'save' => 'Desar',

    // Models traduïbles
    'edit_translations' => 'Traducció',
    'language' => 'Idioma',

    // Vista de taula CRUD
    'all' => 'Tots ',
    'in_the_database' => 'a la base de dades',
    'list' => 'Llista',
    'reset' => 'Reiniciar',
    'actions' => 'Accions',
    'preview' => 'Previsualitzar',
    'delete' => 'Eliminar',
    'admin' => 'Administrador',
    'details_row' => 'Aquesta és la fila de detalls. Modifica-la com vulguis.',
    'details_row_loading_error' => 'S\'ha produït un error en carregar els detalls. Torna-ho a provar.',
    'clone' => 'Clonar',
    'clone_success' => '<strong>Entrada clonada</strong><br>S\'ha afegit una nova entrada amb la mateixa informació.',
    'clone_failure' => '<strong>Error en clonar</strong><br>No s\'ha pogut crear la nova entrada. Torna-ho a provar.',

    // Missatges de confirmació
    'delete_confirm' => 'Estàs segur que vols eliminar aquest element?',
    'delete_confirmation_title' => 'Element eliminat',
    'delete_confirmation_message' => 'L\'element s\'ha eliminat correctament.',
    'delete_confirmation_not_title' => 'NO eliminat',
    'delete_confirmation_not_message' => 'Hi ha hagut un error. Potser l\'element no s\'ha eliminat.',
    'delete_confirmation_not_deleted_title' => 'No eliminat',
    'delete_confirmation_not_deleted_message' => 'No ha passat res. L\'element és segur.',

    // Accions massives
    'bulk_no_entries_selected_title' => 'No s\'ha seleccionat cap entrada',
    'bulk_no_entries_selected_message' => 'Selecciona un o més elements per realitzar una acció massiva.',

    // Eliminació massiva
    'bulk_delete_are_you_sure' => 'Estàs segur que vols eliminar aquestes :number entrades?',
    'bulk_delete_sucess_title' => 'Entrades eliminades',
    'bulk_delete_sucess_message' => ' elements eliminats',
    'bulk_delete_error_title' => 'Error en eliminar',
    'bulk_delete_error_message' => 'Un o més elements no s\'han pogut eliminar',

    // Clonació massiva
    'bulk_clone_are_you_sure' => 'Estàs segur que vols clonar aquestes :number entrades?',
    'bulk_clone_sucess_title' => 'Entrades clonades',
    'bulk_clone_sucess_message' => ' elements clonats.',
    'bulk_clone_error_title' => 'Error en clonar',
    'bulk_clone_error_message' => 'No s\'han pogut crear una o més entrades. Torna-ho a provar.',

    // Errors Ajax
    'ajax_error_title' => 'Error',
    'ajax_error_text' => 'Error en carregar la pàgina. Actualitza-la.',

    // Traduccions DataTables
    'emptyTable' => 'No hi ha dades disponibles a la taula',
    'info' => 'Mostrant de _START_ a _END_ de _TOTAL_ entrades',
    'infoEmpty' => 'Sense entrades',
    'infoFiltered' => '(filtrat de _MAX_ entrades totals)',
    'infoPostFix' => '.',
    'thousands' => '.',
    'lengthMenu' => '_MENU_ entrades per pàgina',
    'loadingRecords' => 'Carregant...',
    'processing' => 'Processant...',
    'search' => 'Cercar',
    'zeroRecords' => 'No s\'han trobat coincidències',
    'paginate' => [
        'first' => 'Primer',
        'last' => 'Últim',
        'next' => 'Següent',
        'previous' => 'Anterior',
    ],
    'aria' => [
        'sortAscending' => ': activa per ordenar ascendentment',
        'sortDescending' => ': activa per ordenar descendentment',
    ],
    'export' => [
        'export' => 'Exportar',
        'copy' => 'Copiar',
        'excel' => 'Excel',
        'csv' => 'CSV',
        'pdf' => 'PDF',
        'print' => 'Imprimir',
        'column_visibility' => 'Visibilitat de columna',
    ],
    'custom_views' => [
        'title' => 'vistes personalitzades',
        'title_short' => 'vistes',
        'default' => 'per defecte',
    ],

    // CRUD global - errors
    'unauthorized_access' => 'Accés no autoritzat - no tens els permisos necessaris per veure aquesta pàgina.',
    'please_fix' => 'Corregeix els següents errors:',

    // CRUD global - notificacions d’èxit/error
    'insert_success' => 'L\'element s\'ha afegit correctament.',
    'update_success' => 'L\'element s\'ha modificat correctament.',

    // Vista reordenar CRUD
    'reorder' => 'Reordenar',
    'reorder_text' => 'Utilitza arrossegar i deixar anar per reordenar.',
    'reorder_success_title' => 'Fet',
    'reorder_success_message' => 'L\'ordre s\'ha desat.',
    'reorder_error_title' => 'Error',
    'reorder_error_message' => 'L\'ordre no s\'ha desat.',

    // CRUD sí/no
    'yes' => 'Sí',
    'no' => 'No',

    // Filtres
    'filters' => 'Filtres',
    'toggle_filters' => 'Mostrar/ocultar filtres',
    'remove_filters' => 'Eliminar filtres',
    'apply' => 'Aplicar',

    // Traduccions dels filtres
    'today' => 'Avui',
    'yesterday' => 'Ahir',
    'last_7_days' => 'Últims 7 dies',
    'last_30_days' => 'Últims 30 dies',
    'this_month' => 'Aquest mes',
    'last_month' => 'Mes passat',
    'custom_range' => 'Interval personalitzat',
    'weekLabel' => 'S',

    // Camps
    'browse_uploads' => 'Explorar arxius pujats',
    'select_all' => 'Seleccionar-ho tot',
    'unselect_all' => 'Deseleccionar-ho tot',
    'select_files' => 'Seleccionar arxius',
    'select_file' => 'Seleccionar arxiu',
    'clear' => 'Netejar',
    'page_link' => 'Enllaç de pàgina',
    'page_link_placeholder' => 'http://exemple.com/la-teva-pagina',
    'internal_link' => 'Enllaç intern',
    'internal_link_placeholder' => 'Slug intern. Ex: \'admin/page\' per \':url\'',
    'external_link' => 'Enllaç extern',
    'choose_file' => 'Seleccionar arxiu',
    'new_item' => 'Nou Element',
    'select_entry' => 'Selecciona una entrada',
    'select_entries' => 'Selecciona entrades',
    'upload_multiple_files_selected' => 'Arxius seleccionats. Després de desar, apareixeran a dalt.',

    // Camp de taula
    'table_cant_add' => 'No es pot afegir un nou :entity',
    'table_max_reached' => 'S\'ha assolit el màxim de :max',

    // Google Maps
    'google_map_locate' => 'Obtenir la meva ubicació',

    // Gestor de fitxers
    'file_manager' => 'Gestor d\'arxius',

    // InlineCreateOperation
    'related_entry_created_success' => 'Entrada relacionada creada i seleccionada.',
    'related_entry_created_error' => 'No s\'ha pogut crear l\'entrada relacionada.',
    'inline_saving' => 'Desant...',

    // Sense traduccions trobades
    'empty_translations' => '(buit)',

    // Validació de selector de pivot
    'pivot_selector_required_validation_message' => 'El camp pivot és obligatori.',

    // Missatges de botó ràpid
    'quick_button_ajax_error_title' => 'Error en la petició!',
    'quick_button_ajax_error_message' => 'S\'ha produït un error en processar la petició.',
    'quick_button_ajax_success_title' => 'Petició completada!',
    'quick_button_ajax_success_message' => 'La petició s\'ha completat amb èxit.',

    // Traduccions
    'no_attributes_translated' => 'Aquesta entrada no està traduïda a :locale.',
    'no_attributes_translated_href_text' => 'Omple els camps des de :locale',
];
