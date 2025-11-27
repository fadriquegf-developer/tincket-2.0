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

    // Alerts
    'alert_title' => '⚠️ REFUND PENDING',
    'alert_description' => 'This payment requires manual refund from the Redsys panel.',
    'completed_title' => '💰 PAYMENT REFUNDED',
    'refunded_on' => 'Refunded on :date',

    // Reasons
    'reason' => 'Reason',
    'reasons' => [
        'duplicate_slots' => 'Duplicate seats (race condition)',
        'customer_request' => 'Customer request',
        'event_cancelled' => 'Event cancelled',
        'duplicate_payment' => 'Duplicate payment',
        'admin_manual' => 'Manual refund by administrator',
        'other' => 'Other reason',
    ],

    // Request refund
    'request_title' => 'Request refund',
    'request_description' => 'Mark this payment for refund. You can then process it automatically with Redsys or do it manually.',
    'request_button' => 'Request refund',
    'request_success' => 'Payment marked for refund successfully.',
    'select_reason' => 'Select reason',
    'notes_label' => 'Additional notes',
    'notes_placeholder' => 'E.g.: Customer called to cancel',

    // Auto process
    'process_auto_title' => 'Process with Redsys',
    'process_auto_description' => 'Send automatic refund request to Redsys.',
    'process_auto_button' => 'Process with Redsys',
    'process_auto_warning' => 'This will send a refund request to Redsys. The amount will be returned to the customer\'s card.',
    'auto_success' => 'Refund processed successfully. Ref: :reference, Amount: :amount €. Cart deleted and seats released.',
    'auto_error' => 'Error processing refund: :message',
    'partial_amount' => 'Amount to refund (€)',
    'partial_amount_help' => 'Leave empty for full refund',

    // Mark as refunded
    'mark_as_refunded' => 'Mark as refunded',
    'mark_as_refunded_note' => '(Only after processing the refund in Redsys)',
    'mark_success' => 'Refund registered successfully. Cart deleted and seats released.',

    // Modal
    'modal_title' => 'Mark payment as refunded',
    'modal_close' => 'Close',
    'modal_warning' => 'Only mark as refunded after processing the refund from the Redsys panel.',
    'modal_important' => 'Important:',

    // Fields
    'payment_code' => 'Payment code',
    'amount' => 'Amount',
    'reference' => 'Reference',
    'refund_reference' => 'Refund reference',
    'refund_reference_help' => 'Refund operation code from Redsys.',
    'refund_reference_placeholder' => 'E.g.: 123456789012',
    'additional_notes' => 'Additional notes',
    'additional_notes_placeholder' => 'E.g.: Refund processed due to duplicate seats',
    'additional_notes_help' => 'Optional. Will be added to the cart comment.',
    'show_details' => 'Show refund details',

    // Errors
    'not_paid' => 'This cart does not have a confirmed payment.',
    'already_pending' => 'This payment is already marked for refund.',
    'already_refunded' => 'This payment has already been refunded.',
    'no_permission' => 'You do not have permission to manage refunds.',
    'no_permission_auto' => 'Only superadministrators can process automatic refunds.',
    'gateway_not_supported' => 'This payment method does not support automatic refunds. It must be processed manually.',

    // Information
    'payment_info' => 'Payment information',
    'original_amount' => 'Original amount',
    'payment_date' => 'Payment date',
    'payment_gateway' => 'Payment gateway',

    // Buttons
    'cancel' => 'Cancel',
    'confirm_refund' => 'Confirm refund',

    // Other
    'external_application' => 'External application (:name)',
    'steps' => 'Steps: 1) Access the Redsys panel → 2) Process the refund → 3) Mark as refunded here',
];
