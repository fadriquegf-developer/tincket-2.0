<?php

/**
 * Refund translations - English
 * Location: lang/en/refund.php
 */

return [
    // States
    'refunded' => 'Refunded',
    'pending' => 'Refund pending',
    'pending_full' => 'Pending refund',

    // Alert
    'alert_title' => '⚠️ REFUND PENDING',
    'alert_description' => 'This payment requires a manual refund from the Redsys panel.',

    // Reasons
    'reason' => 'Reason',
    'reason_duplicate_slots' => 'The seats were sold to another customer while the payment was being processed (race condition).',
    'reason_duplicate_slots_short' => 'Duplicate seats (race condition)',
    'reason_not_specified' => 'Not specified',

    // Steps
    'steps' => 'Steps: 1) Access the Redsys panel → 2) Process the refund → 3) Mark as refunded here',

    // Completed
    'completed_title' => '💰 PAYMENT REFUNDED',
    'refunded_on' => 'Refunded on :date',

    // Info
    'reference' => 'Reference',
    'info_title' => 'Refund Information',
    'status' => 'Status',
    'refund_date' => 'Refund date',

    // Button
    'mark_as_refunded' => 'Mark as refunded',
    'mark_as_refunded_note' => '(Only after processing the refund in Redsys)',

    // Modal
    'modal_title' => 'Mark payment as refunded',
    'modal_close' => 'Close',
    'modal_warning' => 'Only mark as refunded after processing the refund from the Redsys panel.',
    'modal_important' => 'Important:',

    // Fields
    'payment_code' => 'Payment code',
    'amount' => 'Amount',
    'refund_reference' => 'Refund reference',
    'refund_reference_help' => 'Refund operation code from Redsys.',
    'refund_reference_placeholder' => 'E.g.: 123456789012',
    'additional_notes' => 'Additional notes',
    'additional_notes_placeholder' => 'E.g.: Refund processed due to duplicate seats',
    'additional_notes_help' => 'Optional. Will be added to the cart comment.',

    // Buttons
    'cancel' => 'Cancel',
    'confirm_refund' => 'Confirm refund',

    // Other
    'external_application' => 'External application (:name)',
];
