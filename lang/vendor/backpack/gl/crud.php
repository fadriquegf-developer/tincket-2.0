<?php

return [

    // Formularios
    'save_action_save_and_new' => 'Gardar e novo elemento',
    'save_action_save_and_edit' => 'Gardar e editar este elemento',
    'save_action_save_and_back' => 'Gardar e volver',
    'save_action_save_and_preview' => 'Gardar e previsualizar',
    'save_action_changed_notification' => 'O comportamento por defecto tras gardar foi cambiado.',

    // Formulario de creación
    'add' => 'Engadir',
    'back_to_all' => 'Volver a todos ',
    'cancel' => 'Cancelar',
    'add_a_new' => 'Engadir un novo ',

    // Formulario de edición
    'edit' => 'Editar',
    'save' => 'Gardar',

    // Modelos traducibles
    'edit_translations' => 'Tradución',
    'language' => 'Idioma',

    // Vista de táboa CRUD
    'all' => 'Todos ',
    'in_the_database' => 'na base de datos',
    'list' => 'Lista',
    'reset' => 'Restablecer',
    'actions' => 'Accións',
    'preview' => 'Previsualizar',
    'delete' => 'Eliminar',
    'admin' => 'Administrador',
    'details_row' => 'Esta é a fila de detalles. Modifícaa ao teu gusto.',
    'details_row_loading_error' => 'Houbo un erro ao cargar os detalles. Inténtao de novo.',
    'clone' => 'Clonar',
    'clone_success' => '<strong>Entrada clonada</strong><br>Engadiuse unha nova entrada coa mesma información.',
    'clone_failure' => '<strong>Erro ao clonar</strong><br>Non se puido crear a nova entrada. Inténtao de novo.',

    // Mensaxes de confirmación
    'delete_confirm' => 'Estás seguro de que queres eliminar este elemento?',
    'delete_confirmation_title' => 'Elemento eliminado',
    'delete_confirmation_message' => 'O elemento foi eliminado correctamente.',
    'delete_confirmation_not_title' => 'NON eliminado',
    'delete_confirmation_not_message' => 'Houbo un erro. O elemento pode non terse eliminado.',
    'delete_confirmation_not_deleted_title' => 'Non eliminado',
    'delete_confirmation_not_deleted_message' => 'Non pasou nada. O teu elemento está a salvo.',

    // Accións en masa
    'bulk_no_entries_selected_title' => 'Non se seleccionaron entradas',
    'bulk_no_entries_selected_message' => 'Selecciona un ou máis elementos para realizar unha acción en masa.',

    // Eliminación en masa
    'bulk_delete_are_you_sure' => 'Estás seguro de que queres eliminar estas :number entradas?',
    'bulk_delete_sucess_title' => 'Entradas eliminadas',
    'bulk_delete_sucess_message' => ' elementos foron eliminados',
    'bulk_delete_error_title' => 'Erro ao eliminar',
    'bulk_delete_error_message' => 'Un ou máis elementos non se puideron eliminar',

    // Clonado en masa
    'bulk_clone_are_you_sure' => 'Estás seguro de que queres clonar estas :number entradas?',
    'bulk_clone_sucess_title' => 'Entradas clonadas',
    'bulk_clone_sucess_message' => ' elementos foron clonados.',
    'bulk_clone_error_title' => 'Erro ao clonar',
    'bulk_clone_error_message' => 'Non se puideron crear unha ou máis entradas. Inténtao de novo.',

    // Erros Ajax
    'ajax_error_title' => 'Erro',
    'ajax_error_text' => 'Erro ao cargar a páxina. Actualízaa.',

    // Traduccións de DataTables
    'emptyTable' => 'Non hai datos dispoñibles na táboa',
    'info' => 'Mostrando do _START_ ao _END_ de _TOTAL_ entradas',
    'infoEmpty' => 'Sen entradas',
    'infoFiltered' => '(filtrado de _MAX_ entradas totais)',
    'infoPostFix' => '.',
    'thousands' => '.',
    'lengthMenu' => '_MENU_ entradas por páxina',
    'loadingRecords' => 'Cargando...',
    'processing' => 'Procesando...',
    'search' => 'Buscar',
    'zeroRecords' => 'Non se atoparon coincidencias',
    'paginate' => [
        'first' => 'Primeira',
        'last' => 'Última',
        'next' => 'Seguinte',
        'previous' => 'Anterior',
    ],
    'aria' => [
        'sortAscending' => ': activar para ordenar ascendentemente',
        'sortDescending' => ': activar para ordenar descendentemente',
    ],
    'export' => [
        'export' => 'Exportar',
        'copy' => 'Copiar',
        'excel' => 'Excel',
        'csv' => 'CSV',
        'pdf' => 'PDF',
        'print' => 'Imprimir',
        'column_visibility' => 'Visibilidade das columnas',
    ],
    'custom_views' => [
        'title' => 'vistas personalizadas',
        'title_short' => 'vistas',
        'default' => 'por defecto',
    ],

    // CRUD global - erros
    'unauthorized_access' => 'Acceso non autorizado - non tes os permisos necesarios para ver esta páxina.',
    'please_fix' => 'Corrixe os seguintes erros:',

    // CRUD global - notificacións
    'insert_success' => 'O elemento foi engadido correctamente.',
    'update_success' => 'O elemento foi modificado correctamente.',

    // Vista de reordenación
    'reorder' => 'Reordenar',
    'reorder_text' => 'Usa arrastrar e soltar para reordenar.',
    'reorder_success_title' => 'Feito',
    'reorder_success_message' => 'A túa orde foi gardada.',
    'reorder_error_title' => 'Erro',
    'reorder_error_message' => 'A túa orde non foi gardada.',

    // CRUD si/non
    'yes' => 'Si',
    'no' => 'Non',

    // Filtros
    'filters' => 'Filtros',
    'toggle_filters' => 'Mostrar/agochar filtros',
    'remove_filters' => 'Eliminar filtros',
    'apply' => 'Aplicar',

    // Traducións de filtros
    'today' => 'Hoxe',
    'yesterday' => 'Onte',
    'last_7_days' => 'Últimos 7 días',
    'last_30_days' => 'Últimos 30 días',
    'this_month' => 'Este mes',
    'last_month' => 'Mes pasado',
    'custom_range' => 'Intervalo personalizado',
    'weekLabel' => 'S',

    // Campos
    'browse_uploads' => 'Explorar ficheiros subidos',
    'select_all' => 'Seleccionar todo',
    'unselect_all' => 'Deseleccionar todo',
    'select_files' => 'Seleccionar ficheiros',
    'select_file' => 'Seleccionar ficheiro',
    'clear' => 'Limpar',
    'page_link' => 'Ligazón da páxina',
    'page_link_placeholder' => 'http://exemplo.com/a-tua-paxina',
    'internal_link' => 'Ligazón interna',
    'internal_link_placeholder' => 'Slug interno. Ex: \'admin/page\' para \':url\'',
    'external_link' => 'Ligazón externa',
    'choose_file' => 'Elixir ficheiro',
    'new_item' => 'Novo Elemento',
    'select_entry' => 'Selecciona unha entrada',
    'select_entries' => 'Selecciona entradas',
    'upload_multiple_files_selected' => 'Ficheiros seleccionados. Despois de gardar, aparecerán enriba.',

    // Campo de táboa
    'table_cant_add' => 'Non se pode engadir un novo :entity',
    'table_max_reached' => 'Acadouse o número máximo de :max',

    // Google Maps
    'google_map_locate' => 'Obter a miña localización',

    // Xestor de ficheiros
    'file_manager' => 'Xestor de ficheiros',

    // InlineCreateOperation
    'related_entry_created_success' => 'A entrada relacionada foi creada e seleccionada.',
    'related_entry_created_error' => 'Non se puido crear a entrada relacionada.',
    'inline_saving' => 'Gardando...',

    // Cando non hai traducións en selects
    'empty_translations' => '(baleiro)',

    // Validación para campos pivot
    'pivot_selector_required_validation_message' => 'O campo pivot é obrigatorio.',

    // Mensaxes de botón rápido
    'quick_button_ajax_error_title' => 'Petición fallida!',
    'quick_button_ajax_error_message' => 'Produciuse un erro ao procesar a túa petición.',
    'quick_button_ajax_success_title' => 'Petición completada!',
    'quick_button_ajax_success_message' => 'A túa petición foi completada con éxito.',

    // Traducións
    'no_attributes_translated' => 'Esta entrada non está traducida ao :locale.',
    'no_attributes_translated_href_text' => 'Cubrir os campos desde :locale',
];
