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

    // Partial refund
    'partial_refund_button' => 'Partial refund',
    'partial_refund_title' => 'Partial Refund',
    'partial_refund_submit' => 'Create refund request',
    'partial_refund_description' => 'Select the inscriptions you want to refund. The seats will be released automatically.',
    'partial_refund_instructions' => 'Select the inscriptions you want to refund. The seats will be released automatically.',
    'partial_refund_loading' => 'Loading inscriptions...',
    'partial_refund_confirm' => 'Confirm partial refund?',
    'partial_refund_processing' => 'Processing...',

    // Partial refund statuses
    'partial_status' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'failed' => 'Failed',
    ],

    // Messages
    'partial_success' => 'Partial refund created successfully.',
    'partial_error_no_inscriptions' => 'You must select at least one inscription to refund.',
    'partial_error_all_inscriptions' => 'You cannot refund all inscriptions with partial refund. Use the full refund option.',
    'partial_error_invalid_inscriptions' => 'The selected inscriptions are not valid or have already been refunded.',
    'partial_error_load' => 'Error loading data',

    // History
    'partial_history_title' => 'Partial refunds history',
    'partial_history_empty' => 'No partial refunds registered.',
    'partial_view_inscriptions' => 'View inscriptions',

    // Table
    'table_event_session' => 'Event / Session',
    'table_seat' => 'Seat',
    'table_rate' => 'Rate',
    'table_price' => 'Price',
    'table_total_to_refund' => 'Total to refund',
    'table_select_all' => 'Select all',

    // Summary
    'summary_code' => 'Code',
    'summary_original_amount' => 'Original amount',
    'summary_total_refunded' => 'Already refunded',
    'summary_remaining' => 'Remaining',

    // Confirmation
    'confirm_inscriptions' => 'Inscriptions',
    'confirm_amount' => 'Amount',
    'confirm_seats_released' => 'The seats will be released automatically.',

    // Main buttons
    'partial_refund_button' => 'Partial refund',
    'partial_refund_title' => 'Partial Refund',
    'partial_refund_submit' => 'Create refund request',
    'cancel' => 'Cancel',

    // Loading and states
    'loading' => 'Loading...',
    'loading_inscriptions' => 'Loading inscriptions...',
    'processing' => 'Processing...',

    // Cart summary
    'code' => 'Code',
    'original_amount' => 'Original amount',
    'already_refunded' => 'Already refunded',

    // Instructions
    'instructions_title' => 'Instructions',
    'instructions_text' => 'Select the inscriptions you want to refund. Seats will be released automatically.',

    // Inscriptions table
    'select_all' => 'Select all',
    'event_session' => 'Event / Session',
    'seat' => 'Seat',
    'rate' => 'Rate',
    'price' => 'Price',
    'total_to_refund' => 'Total to refund',

    // Form
    'select_reason' => 'Refund reason',
    'select_option' => '-- Select --',
    'notes_label' => 'Additional notes',
    'notes_placeholder' => 'E.g.: Customer called to cancel',

    // Refund reasons
    'reasons' => [
        'customer_request' => 'Customer request',
        'event_cancelled' => 'Event cancelled',
        'duplicate_payment' => 'Duplicate payment',
        'admin_manual' => 'Manual refund by admin',
        'other' => 'Other reason',
    ],

    // History
    'refund_history_title' => 'Partial refund history',
    'view_inscriptions' => 'View inscriptions',
    'reference' => 'Ref',

    // Validation messages and alerts
    'select_at_least_one' => 'Select at least one inscription',
    'select_reason_required' => 'Select a reason for the refund',
    'cannot_select_all' => 'You cannot select all inscriptions. To refund the entire cart, use the full refund option.',
    'confirm_partial_refund' => 'Confirm partial refund?',
    'inscriptions_count' => 'Inscriptions',
    'amount' => 'Amount',
    'seats_will_be_released' => 'Seats will be released automatically.',
    'error_prefix' => 'Error',
    'error_loading_data' => 'Error loading data',
    'error_processing_refund' => 'Error processing refund',
];
