<?php

/**
 * Traduccions de reemborsaments - Català
 * Ubicació: lang/ca/refund.php
 */

return [
    // Estats
    'refunded' => 'Reemborsat',
    'pending' => 'Pendent reemborsament',
    'pending_full' => 'Pendent de reemborsament',

    // Alerta
    'alert_title' => '⚠️ REEMBORSAMENT PENDENT',
    'alert_description' => 'Aquest pagament requereix reemborsament manual des del panell de Redsys.',

    // Motius
    'reason' => 'Motiu',
    'reason_duplicate_slots' => 'Els seients van ser venuts a un altre client mentre es processava el pagament (race condition).',
    'reason_duplicate_slots_short' => 'Seients duplicats (race condition)',
    'reason_not_specified' => 'No especificat',

    // Passos
    'steps' => 'Passos: 1) Accedeix al panell de Redsys → 2) Realitza la devolució → 3) Marca com a reemborsat aquí',

    // Completat
    'completed_title' => '💰 PAGAMENT REEMBORSAT',
    'refunded_on' => 'Reemborsat el :date',

    // Info
    'reference' => 'Referència',
    'info_title' => 'Informació de Reemborsament',
    'status' => 'Estat',
    'refund_date' => 'Data reemborsament',

    // Botó
    'mark_as_refunded' => 'Marcar com a reemborsat',
    'mark_as_refunded_note' => '(Només després de realitzar la devolució a Redsys)',

    // Modal
    'modal_title' => 'Marcar pagament com a reemborsat',
    'modal_close' => 'Tancar',
    'modal_warning' => 'Només marca com a reemborsat després d\'haver realitzat la devolució des del panell de Redsys.',
    'modal_important' => 'Important:',

    // Camps
    'payment_code' => 'Codi pagament',
    'amount' => 'Import',
    'refund_reference' => 'Referència de reemborsament',
    'refund_reference_help' => 'Codi d\'operació de la devolució a Redsys.',
    'refund_reference_placeholder' => 'Ex: 123456789012',
    'additional_notes' => 'Notes addicionals',
    'additional_notes_placeholder' => 'Ex: Devolució realitzada per duplicitat de seients',
    'additional_notes_help' => 'Opcional. S\'afegirà al comentari del carret.',

    // Botons
    'cancel' => 'Cancel·lar',
    'confirm_refund' => 'Confirmar reemborsament',

    // Altres
    'external_application' => 'Aplicació externa (:name)',
];
