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

    // Alerta
    'alert_title' => '⚠️ REEMBOLSO PENDENTE',
    'alert_description' => 'Este pago require reembolso manual desde o panel de Redsys.',

    // Motivos
    'reason' => 'Motivo',
    'reason_duplicate_slots' => 'Os asentos foron vendidos a outro cliente mentres se procesaba o pago (race condition).',
    'reason_duplicate_slots_short' => 'Asentos duplicados (race condition)',
    'reason_not_specified' => 'Non especificado',

    // Pasos
    'steps' => 'Pasos: 1) Accede ao panel de Redsys → 2) Realiza a devolución → 3) Marca como reembolsado aquí',

    // Completado
    'completed_title' => '💰 PAGO REEMBOLSADO',
    'refunded_on' => 'Reembolsado o :date',

    // Info
    'reference' => 'Referencia',
    'info_title' => 'Información de Reembolso',
    'status' => 'Estado',
    'refund_date' => 'Data reembolso',

    // Botón
    'mark_as_refunded' => 'Marcar como reembolsado',
    'mark_as_refunded_note' => '(Só despois de realizar a devolución en Redsys)',

    // Modal
    'modal_title' => 'Marcar pago como reembolsado',
    'modal_close' => 'Pechar',
    'modal_warning' => 'Só marca como reembolsado despois de ter realizado a devolución desde o panel de Redsys.',
    'modal_important' => 'Importante:',

    // Campos
    'payment_code' => 'Código pago',
    'amount' => 'Importe',
    'refund_reference' => 'Referencia de reembolso',
    'refund_reference_help' => 'Código de operación da devolución en Redsys.',
    'refund_reference_placeholder' => 'Ex: 123456789012',
    'additional_notes' => 'Notas adicionais',
    'additional_notes_placeholder' => 'Ex: Devolución realizada por duplicidade de asentos',
    'additional_notes_help' => 'Opcional. Engadirase ao comentario do carro.',

    // Botóns
    'cancel' => 'Cancelar',
    'confirm_refund' => 'Confirmar reembolso',

    // Outros
    'external_application' => 'Aplicación externa (:name)',
];
