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
];
