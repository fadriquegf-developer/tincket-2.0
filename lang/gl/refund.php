<?php

/**
 * Traducións de reembolsos - Galego
 * Ubicación: lang/gl/refund.php
 */

return [
    // Estados
    'refunded' => 'Reembolsado',
    'pending' => 'Pendente de reembolso',
    'pending_full' => 'Pendente de reembolso',

    // Alertas
    'alert_title' => '⚠️ REEMBOLSO PENDENTE',
    'alert_description' => 'Este pago require reembolso manual desde o panel de Redsys.',
    'completed_title' => '💰 PAGO REEMBOLSADO',
    'refunded_on' => 'Reembolsado o :date',

    // Motivos
    'reason' => 'Motivo',
    'reasons' => [
        'duplicate_slots' => 'Asentos duplicados (race condition)',
        'customer_request' => 'Solicitude do cliente',
        'event_cancelled' => 'Evento cancelado',
        'duplicate_payment' => 'Pago duplicado',
        'admin_manual' => 'Reembolso manual por administrador',
        'other' => 'Outro motivo',
    ],

    // Solicitar reembolso
    'request_title' => 'Solicitar devolución',
    'request_description' => 'Marcar este pago para devolución. Despois poderás procesalo automaticamente con Redsys ou facelo manualmente.',
    'request_button' => 'Solicitar devolución',
    'request_success' => 'Pago marcado para reembolso correctamente.',
    'select_reason' => 'Selecciona o motivo',
    'notes_label' => 'Notas adicionais',
    'notes_placeholder' => 'Ex: Cliente chamou para cancelar',

    // Procesar automático
    'process_auto_title' => 'Procesar con Redsys',
    'process_auto_description' => 'Enviar solicitude de devolución automática a Redsys.',
    'process_auto_button' => 'Procesar con Redsys',
    'process_auto_warning' => 'Isto enviará unha solicitude de devolución a Redsys. O importe devolverase á tarxeta do cliente.',
    'auto_success' => 'Devolución procesada correctamente. Ref: :reference, Importe: :amount €. Carro eliminado e asentos liberados.',
    'auto_error' => 'Erro ao procesar a devolución: :message',
    'partial_amount' => 'Importe a devolver (€)',
    'partial_amount_help' => 'Deixar baleiro para devolución total',

    // Marcar como reembolsado
    'mark_as_refunded' => 'Marcar como reembolsado',
    'mark_as_refunded_note' => '(Só despois de realizar a devolución en Redsys)',
    'mark_success' => 'Reembolso rexistrado correctamente. Carro eliminado e asentos liberados.',

    // Modal
    'modal_title' => 'Marcar pago como reembolsado',
    'modal_close' => 'Pechar',
    'modal_warning' => 'Só marca como reembolsado despois de ter realizado a devolución desde o panel de Redsys.',
    'modal_important' => 'Importante:',

    // Campos
    'payment_code' => 'Código pago',
    'amount' => 'Importe',
    'reference' => 'Referencia',
    'refund_reference' => 'Referencia do reembolso',
    'refund_reference_help' => 'Código de operación da devolución en Redsys.',
    'refund_reference_placeholder' => 'Ex: 123456789012',
    'additional_notes' => 'Notas adicionais',
    'additional_notes_placeholder' => 'Ex: Devolución realizada por duplicidade de asentos',
    'additional_notes_help' => 'Opcional. Engadirase ao comentario do carro.',
    'show_details' => 'Ver detalles do reembolso',

    // Erros
    'not_paid' => 'Este carro non ten un pago confirmado.',
    'already_pending' => 'Este pago xa está marcado para reembolso.',
    'already_refunded' => 'Este pago xa foi reembolsado.',
    'no_permission' => 'Non tes permisos para xestionar reembolsos.',
    'no_permission_auto' => 'Só os superadministradores poden procesar devolucións automáticas.',
    'gateway_not_supported' => 'Este método de pago non soporta devolucións automáticas. Debe procesarse manualmente.',

    // Información
    'payment_info' => 'Información do pago',
    'original_amount' => 'Importe orixinal',
    'payment_date' => 'Data de pago',
    'payment_gateway' => 'Pasarela de pago',

    // Botóns
    'cancel' => 'Cancelar',
    'confirm_refund' => 'Confirmar reembolso',

    // Outros
    'external_application' => 'Aplicación externa (:name)',
    'steps' => 'Pasos: 1) Accede ao panel de Redsys → 2) Realiza a devolución → 3) Marca como reembolsado aquí',
];
