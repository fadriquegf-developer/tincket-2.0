<?php

return [
    'tickets_office' => 'Box Office',
    'all_sessions' => '(includes session history)',
    'client' => 'Client',
    'email' => 'Email',
    'create' => 'Create',
    'firstname' => 'First name',
    'lastname' => 'Last name',
    'inscriptions_set' => 'Inscriptions',
    'rate' => 'Rate',
    'slot' => 'Position on map',
    'add_inscription' => 'Add Inscription',
    'there_is_only' => 'There are only ',
    'zoom_help' => 'To zoom, use the mouse wheel, and it will zoom on the area you are hovering over with the mouse',
    'free_slots_in_session' => ' free seats in the session.',
    'tickets_sold' => 'Tickets sold',
    'add_to_cart' => 'Add to cart',
    'session' => 'Session',
    'sessions' => 'Sessions',
    'packs' => 'Packages',
    'pack' => 'Package',
    'inscriptions' => 'Inscriptions',
    'new_pack' => 'Add package',
    'select_the_pack' => 'Select the package',
    'show_all_packs' => 'Show all packs',
    'select_the_sessions' => 'Select the sessions that will be in the package',
    'select_slots_for' => 'Select seats for',
    'select_at_least' => 'Select at least ',
    'sessions_to_sell_this_pack' => 'sessions to sell in this package',
    'pendent' => 'Pending',
    'sessio_no_numerada' => 'Unnumbered session',
    'reset' => 'Reset',
    'how_many_packs' => 'How many packages',
    'payment' => 'Payment',
    'payment_code' => 'Payment code',
    'paid_at' => 'Payment Date',
    'payment_platform' => 'Payment platform',
    'price' => 'Price',
    'delete' => 'Delete',
    'delete_item' => 'Delete item',
    'deleteitem' => 'Delete item',
    'confirm_cart' => 'Confirm cart',
    'add' => 'Add selection',
    'remove' => 'Remove selection',
    'end_selection' => 'End selection',
    'select_session' => 'Select a session',
    'show_all_sessions' => 'Show all sessions',
    'payment_type' => [
        'cash' => 'Cash',
        'card' => 'Credit card'
    ],
    'total' => 'Total',

    // Gift Cards
    'gift_cards' => 'Gift cards',
    'gift_card' => [
        'gift_cards' => 'Gift cards',
        'validate' => 'Validate',
        'code' => 'Code',
        'select_the_sessions' => 'Select the session',
        // NEW:
        'validation_error' => 'Validation error',
        'code_already_in_cart' => 'You already have this code in your cart',
        'code_not_found' => 'Code not found or already claimed',
        'validating' => 'Validating...'
    ],

    // SVG Layout Legend
    'svg_layout' => [
        'legend' => [
            'available' => 'Available',
            'selected' => 'Selected',
            'sold' => 'Sold',
            'booked' => 'Reserved',
            'booked_packs' => 'Reserved by packages',
            'hidden' => 'Hidden',
            'locked' => 'Locked',
            'covid19' => 'COVID-19',
            'disability' => 'Reduced mobility'
        ],
        'help' => 'You can select multiple seats by holding Ctrl (Cmd on Mac) while clicking, or by dragging to create a selection.'
    ],

    // General missing items
    'loading' => 'Loading...',
    'next' => 'Next',
    'previous' => 'Previous',
    'close' => 'Close',
    'save' => 'Save',
    'cancel' => 'Cancel',

    // Specific modals
    'select_session' => 'Select a session',
    'layout_modal' => [
        'title' => 'Select seats',
        'session_info' => 'Session information',
        'selection_help' => 'Selection help'
    ],

    // Specific pack modal that may be missing
    'pack_modal' => [
        'title' => 'Configure package',
        'step_1' => 'Step 1: Select package',
        'step_2' => 'Step 2: Select sessions',
        'step_3' => 'Step 3: Select seats'
    ],

    // Loading states
    'loading_states' => [
        'loading_app' => 'Loading application...',
        'loading_sessions' => 'Loading sessions...',
        'loading_layout' => 'Loading map...',
        'validating_code' => 'Validating code...',
        'processing' => 'Processing...'
    ],

    // Common errors
    'errors' => [
        'generic' => 'An error occurred',
        'network' => 'Network error',
        'loading_failed' => 'Loading failed',
        'session_not_found' => 'Session not found',
        'slot_not_available' => 'This seat is no longer available'
    ],

    // Confirmation messages
    'confirmations' => [
        'remove_inscription' => 'Are you sure you want to remove this inscription?',
        'remove_pack' => 'Are you sure you want to remove this package?',
        'clear_selection' => 'Are you sure you want to reset the selection?'
    ],

    // Accessibility
    'accessibility' => [
        'close_modal' => 'Close modal',
        'previous_session' => 'Previous session',
        'next_session' => 'Next session',
        'zoom_in' => 'Zoom in',
        'zoom_out' => 'Zoom out',
        'reset_zoom' => 'Reset zoom'
    ],

    // Help information
    'help' => [
        'multiple_selection' => 'Hold Ctrl to select multiple seats',
        'drag_selection' => 'Drag to select multiple seats',
        'zoom_controls' => 'Use zoom controls to navigate the map'
    ]
];