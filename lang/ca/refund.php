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

    // Alertes
    'alert_title' => '⚠️ REEMBORSAMENT PENDENT',
    'alert_description' => 'Aquest pagament requereix reemborsament manual des del panell de Redsys.',
    'completed_title' => '💰 PAGAMENT REEMBORSAT',
    'refunded_on' => 'Reemborsat el :date',

    // Motius
    'reason' => 'Motiu',
    'reasons' => [
        'duplicate_slots' => 'Seients duplicats (race condition)',
        'customer_request' => 'Sol·licitud del client',
        'event_cancelled' => 'Esdeveniment cancel·lat',
        'duplicate_payment' => 'Pagament duplicat',
        'admin_manual' => 'Reemborsament manual per administrador',
        'other' => 'Altre motiu',
    ],

    // Sol·licitar reemborsament
    'request_title' => 'Sol·licitar devolució',
    'request_description' => 'Marcar aquest pagament per a devolució. Després podràs processar-lo automàticament amb Redsys o fer-ho manualment.',
    'request_button' => 'Sol·licitar devolució',
    'request_success' => 'Pagament marcat per a reemborsament correctament.',
    'select_reason' => 'Selecciona el motiu',
    'notes_label' => 'Notes addicionals',
    'notes_placeholder' => 'Ex: Client va trucar per cancel·lar',

    // Processar automàtic
    'process_auto_title' => 'Processar amb Redsys',
    'process_auto_description' => 'Enviar sol·licitud de devolució automàtica a Redsys.',
    'process_auto_button' => 'Processar amb Redsys',
    'process_auto_warning' => 'Això enviarà una sol·licitud de devolució a Redsys. L\'import es retornarà a la targeta del client.',
    'auto_success' => 'Devolució processada correctament. Ref: :reference, Import: :amount €. Carret eliminat i butaques alliberades.',
    'auto_error' => 'Error al processar la devolució: :message',
    'partial_amount' => 'Import a retornar (€)',
    'partial_amount_help' => 'Deixar buit per devolució total',

    // Marcar com a reemborsat
    'mark_as_refunded' => 'Marcar com a reemborsat',
    'mark_as_refunded_note' => '(Només després de realitzar la devolució a Redsys)',
    'mark_success' => 'Reemborsament registrat correctament. Carret eliminat i butaques alliberades.',

    // Modal
    'modal_title' => 'Marcar pagament com a reemborsat',
    'modal_close' => 'Tancar',
    'modal_warning' => 'Només marca com a reemborsat després d\'haver realitzat la devolució des del panell de Redsys.',
    'modal_important' => 'Important:',

    // Camps
    'payment_code' => 'Codi pagament',
    'amount' => 'Import',
    'reference' => 'Referència',
    'refund_reference' => 'Referència del reemborsament',
    'refund_reference_help' => 'Codi d\'operació de la devolució a Redsys.',
    'refund_reference_placeholder' => 'Ex: 123456789012',
    'additional_notes' => 'Notes addicionals',
    'additional_notes_placeholder' => 'Ex: Devolució realitzada per duplicitat de seients',
    'additional_notes_help' => 'Opcional. S\'afegirà al comentari del carret.',
    'show_details' => 'Veure detalls del reemborsament',

    // Errors
    'not_paid' => 'Aquest carret no té un pagament confirmat.',
    'already_pending' => 'Aquest pagament ja està marcat per a reemborsament.',
    'already_refunded' => 'Aquest pagament ja va ser reemborsat.',
    'no_permission' => 'No tens permisos per gestionar reemborsaments.',
    'no_permission_auto' => 'Només els superadministradors poden processar devolucions automàtiques.',
    'gateway_not_supported' => 'Aquest mètode de pagament no suporta devolucions automàtiques. S\'ha de processar manualment.',

    // Informació
    'payment_info' => 'Informació del pagament',
    'original_amount' => 'Import original',
    'payment_date' => 'Data de pagament',
    'payment_gateway' => 'Passarel·la de pagament',

    // Botons
    'cancel' => 'Cancel·lar',
    'confirm_refund' => 'Confirmar reemborsament',

    // Altres
    'external_application' => 'Aplicació externa (:name)',
    'steps' => 'Passos: 1) Accedeix al panell de Redsys → 2) Realitza la devolució → 3) Marca com a reemborsat aquí',
];
