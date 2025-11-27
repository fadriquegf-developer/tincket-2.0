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

    // Alerta
    'alert_title' => '⚠️ REEMBOLSO PENDIENTE',
    'alert_description' => 'Este pago requiere reembolso manual desde el panel de Redsys.',

    // Motivos
    'reason' => 'Motivo',
    'reason_duplicate_slots' => 'Los asientos fueron vendidos a otro cliente mientras se procesaba el pago (race condition).',
    'reason_duplicate_slots_short' => 'Asientos duplicados (race condition)',
    'reason_not_specified' => 'No especificado',

    // Pasos
    'steps' => 'Pasos: 1) Accede al panel de Redsys → 2) Realiza la devolución → 3) Marca como reembolsado aquí',

    // Completado
    'completed_title' => '💰 PAGO REEMBOLSADO',
    'refunded_on' => 'Reembolsado el :date',

    // Info
    'reference' => 'Referencia',
    'info_title' => 'Información de Reembolso',
    'status' => 'Estado',
    'refund_date' => 'Fecha reembolso',

    // Botón
    'mark_as_refunded' => 'Marcar como reembolsado',
    'mark_as_refunded_note' => '(Solo después de realizar la devolución en Redsys)',

    // Modal
    'modal_title' => 'Marcar pago como reembolsado',
    'modal_close' => 'Cerrar',
    'modal_warning' => 'Solo marca como reembolsado después de haber realizado la devolución desde el panel de Redsys.',
    'modal_important' => 'Importante:',

    // Campos
    'payment_code' => 'Código pago',
    'amount' => 'Importe',
    'refund_reference' => 'Referencia de reembolso',
    'refund_reference_help' => 'Código de operación de la devolución en Redsys.',
    'refund_reference_placeholder' => 'Ej: 123456789012',
    'additional_notes' => 'Notas adicionales',
    'additional_notes_placeholder' => 'Ej: Devolución realizada por duplicidad de asientos',
    'additional_notes_help' => 'Opcional. Se añadirá al comentario del carrito.',

    // Botones
    'cancel' => 'Cancelar',
    'confirm_refund' => 'Confirmar reembolso',

    // Otros
    'external_application' => 'Aplicación externa (:name)',
];
