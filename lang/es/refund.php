<?php

/**
 * Traducciones de reembolsos - Castellano
 * Ubicación: lang/es/refund.php
 */

return [
    // Estados
    'refunded' => 'Reembolsado',
    'pending' => 'Pendiente reembolso',
    'pending_full' => 'Pendiente de reembolso',

    // Alertas
    'alert_title' => '⚠️ REEMBOLSO PENDIENTE',
    'alert_description' => 'Este pago requiere reembolso manual desde el panel de Redsys.',
    'completed_title' => '💰 PAGO REEMBOLSADO',
    'refunded_on' => 'Reembolsado el :date',

    // Motivos
    'reason' => 'Motivo',
    'reasons' => [
        'duplicate_slots' => 'Asientos duplicados (race condition)',
        'customer_request' => 'Solicitud del cliente',
        'event_cancelled' => 'Evento cancelado',
        'duplicate_payment' => 'Pago duplicado',
        'admin_manual' => 'Reembolso manual por administrador',
        'other' => 'Otro motivo',
    ],

    // Solicitar reembolso
    'request_title' => 'Solicitar devolución',
    'request_description' => 'Marcar este pago para devolución. Después podrás procesarlo automáticamente con Redsys o hacerlo manualmente.',
    'request_button' => 'Solicitar devolución',
    'request_success' => 'Pago marcado para reembolso correctamente.',
    'select_reason' => 'Selecciona el motivo',
    'notes_label' => 'Notas adicionales',
    'notes_placeholder' => 'Ej: Cliente llamó para cancelar',

    // Procesar automático
    'process_auto_title' => 'Procesar con Redsys',
    'process_auto_description' => 'Enviar solicitud de devolución automática a Redsys.',
    'process_auto_button' => 'Procesar con Redsys',
    'process_auto_warning' => 'Esto enviará una solicitud de devolución a Redsys. El importe se devolverá a la tarjeta del cliente.',
    'auto_success' => 'Devolución procesada correctamente. Ref: :reference, Importe: :amount €. Carrito eliminado y butacas liberadas.',
    'auto_error' => 'Error al procesar la devolución: :message',
    'partial_amount' => 'Importe a devolver (€)',
    'partial_amount_help' => 'Dejar vacío para devolución total',

    // Marcar como reembolsado
    'mark_as_refunded' => 'Marcar como reembolsado',
    'mark_as_refunded_note' => '(Solo después de realizar la devolución en Redsys)',
    'mark_success' => 'Reembolso registrado correctamente. Carrito eliminado y butacas liberadas.',

    // Modal
    'modal_title' => 'Marcar pago como reembolsado',
    'modal_close' => 'Cerrar',
    'modal_warning' => 'Solo marca como reembolsado después de haber realizado la devolución desde el panel de Redsys.',
    'modal_important' => 'Importante:',

    // Campos
    'payment_code' => 'Código pago',
    'amount' => 'Importe',
    'reference' => 'Referencia',
    'refund_reference' => 'Referencia del reembolso',
    'refund_reference_help' => 'Código de operación de la devolución en Redsys.',
    'refund_reference_placeholder' => 'Ej: 123456789012',
    'additional_notes' => 'Notas adicionales',
    'additional_notes_placeholder' => 'Ej: Devolución realizada por duplicidad de asientos',
    'additional_notes_help' => 'Opcional. Se añadirá al comentario del carrito.',
    'show_details' => 'Ver detalles del reembolso',

    // Errores
    'not_paid' => 'Este carrito no tiene un pago confirmado.',
    'already_pending' => 'Este pago ya está marcado para reembolso.',
    'already_refunded' => 'Este pago ya fue reembolsado.',
    'no_permission' => 'No tienes permisos para gestionar reembolsos.',
    'no_permission_auto' => 'Solo los superadministradores pueden procesar devoluciones automáticas.',
    'gateway_not_supported' => 'Este método de pago no soporta devoluciones automáticas. Debe procesarse manualmente.',

    // Información
    'payment_info' => 'Información del pago',
    'original_amount' => 'Importe original',
    'payment_date' => 'Fecha de pago',
    'payment_gateway' => 'Pasarela de pago',

    // Botones
    'cancel' => 'Cancelar',
    'confirm_refund' => 'Confirmar reembolso',

    // Otros
    'external_application' => 'Aplicación externa (:name)',
    'steps' => 'Pasos: 1) Accede al panel de Redsys → 2) Realiza la devolución → 3) Marca como reembolsado aquí',
    // ───────────────────────────────────────────────────────────────────────────
    // Devolución parcial
    // ───────────────────────────────────────────────────────────────────────────
    
    'partial_refund_button' => 'Devolución parcial',
    'partial_refund_title' => 'Devolución Parcial',
    'partial_refund_submit' => 'Crear solicitud de devolución',
    'partial_refund_description' => 'Selecciona las inscripciones que deseas devolver. Las butacas se liberarán automáticamente.',
    
    // Estados de devolución parcial
    'partial_status' => [
        'pending' => 'Pendiente',
        'processing' => 'Procesando',
        'completed' => 'Completado',
        'failed' => 'Fallido',
    ],
    
    // Mensajes
    'partial_success' => 'Devolución parcial creada correctamente.',
    'partial_error_no_inscriptions' => 'Debe seleccionar al menos una inscripción para devolver.',
    'partial_error_all_inscriptions' => 'No puede devolver todas las inscripciones con devolución parcial. Use la opción de devolución completa.',
    'partial_error_invalid_inscriptions' => 'Las inscripciones seleccionadas no son válidas o ya fueron devueltas.',
    
    // Historial
    'partial_history_title' => 'Historial de devoluciones parciales',
    'partial_history_empty' => 'No hay devoluciones parciales registradas.',
    'partial_inscriptions_count' => ':count inscripciones',
    
    // Tabla
    'table_event_session' => 'Evento / Sesión',
    'table_seat' => 'Butaca',
    'table_rate' => 'Tarifa',
    'table_price' => 'Precio',
    'table_total_to_refund' => 'Total a devolver',
    'table_select_all' => 'Seleccionar todas',
    
    // Resumen
    'summary_original_amount' => 'Importe original',
    'summary_total_refunded' => 'Ya devuelto',
    'summary_remaining' => 'Restante',

    // Botones principales
    'partial_refund_button' => 'Devolución parcial',
    'partial_refund_title' => 'Devolución Parcial',
    'partial_refund_submit' => 'Crear solicitud de devolución',
    'cancel' => 'Cancelar',

    // Loading y estados
    'loading' => 'Cargando...',
    'loading_inscriptions' => 'Cargando inscripciones...',
    'processing' => 'Procesando...',

    // Resumen del carrito
    'code' => 'Código',
    'original_amount' => 'Importe original',
    'already_refunded' => 'Ya devuelto',

    // Instrucciones
    'instructions_title' => 'Instrucciones',
    'instructions_text' => 'Selecciona las inscripciones que deseas devolver. Las butacas se liberarán automáticamente.',

    // Tabla de inscripciones
    'select_all' => 'Seleccionar todas',
    'event_session' => 'Evento / Sesión',
    'seat' => 'Butaca',
    'rate' => 'Tarifa',
    'price' => 'Precio',
    'total_to_refund' => 'Total a devolver',

    // Formulario
    'select_reason' => 'Motivo de la devolución',
    'select_option' => '-- Seleccionar --',
    'notes_label' => 'Notas adicionales',
    'notes_placeholder' => 'Ej: Cliente llamó para cancelar',

    // Motivos de devolución
    'reasons' => [
        'customer_request' => 'Solicitud del cliente',
        'event_cancelled' => 'Evento cancelado',
        'duplicate_payment' => 'Pago duplicado',
        'admin_manual' => 'Reembolso manual por admin',
        'other' => 'Otro motivo',
    ],

    // Historial
    'refund_history_title' => 'Historial de devoluciones parciales',
    'view_inscriptions' => 'Ver inscripciones',
    'reference' => 'Ref',

    // Mensajes de validación y alertas
    'select_at_least_one' => 'Selecciona al menos una inscripción',
    'select_reason_required' => 'Selecciona un motivo para la devolución',
    'cannot_select_all' => 'No puede seleccionar todas las inscripciones. Para devolver todo el carrito, use la opción de devolución completa.',
    'confirm_partial_refund' => '¿Confirmar devolución parcial?',
    'inscriptions_count' => 'Inscripciones',
    'amount' => 'Importe',
    'seats_will_be_released' => 'Las butacas se liberarán automáticamente.',
    'error_prefix' => 'Error',
    'error_loading_data' => 'Error al cargar los datos',
    'error_processing_refund' => 'Error al procesar la devolución',
];
