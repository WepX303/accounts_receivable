<?php

return [
    'validation_fix' => 'Please fix the following:',
    'search_placeholder' => 'Search name / phone / passport / contract / clientref...',

    'empty_title' => 'Search to find customers',
    'empty_desc' => 'Or select customers in :customers_info and click :payment.',
    'customers_info' => 'Customers Info',
    'payment' => 'Payment',

    'th' => [
        'name' => 'Name',
        'contract' => 'Contract',
        'remaining' => 'Remaining',
        'paid_local' => 'Paid Local',
        'last_paid' => 'Last Paid',
    ],

    'local_missing' => 'LOCAL MISSING',
    'no_records' => 'No records found.',
    'total' => 'Total',
    'page' => 'Page',

    'select_customer' => 'Select a customer to see details and save payment.',

    'detail' => [
        'total_local' => 'Total Local (amount_local)',
        'paid_local' => 'Paid Local',
        'remaining' => 'Remaining',
        'last_paid_by' => 'Last Paid By',
        'last_paid_at' => 'Last Paid At',
    ],

    'alert_local_missing' => 'Local fields are missing (amount_local / paid_local is NULL). Payment is disabled.',
    'alert_closed' => 'This customer has no remaining debt (paid_local >= amount_local). Payment is disabled.',

    'last_payment_detail' => 'Last Payment Detail',
    'payment_history_last_10' => 'Payment History (Last 10)',
    'no_payment_history' => 'No payment history.',

    'received' => 'Received',
    'applied' => 'Applied',
    'change' => 'Change',
    'cash' => 'Cash',
    'card' => 'Card',
    'by' => 'By',
    'remaining' => 'Remaining',

    'form' => [
        'received_amount' => 'Received Amount (Customer gives)',
        'remaining' => 'Remaining',
        'applied_to_debt' => 'Applied to debt',
        'change_to_customer' => 'Change (to customer)',

        'payment_method' => 'Payment Method',
        'method_cash' => 'Cash',
        'method_card' => 'Card',
        'method_mixed' => 'Mixed',
        'method_phone' => 'Phone',

        'all_cash' => 'All Cash',
        'all_card' => 'All Card',
        'half' => '50/50',
        'mixed_rule' => 'Cash + Card must equal Received Amount.',

        'note' => 'Note',
        'note_placeholder' => 'Optional...',
        'save_payment' => 'Save Payment',

        'payment_date' => 'Payment Date',
        'payment_date_help' => 'You can enter any past date. Future dates are not allowed.',
        'mixed_rule_pay_amount' => 'Mixed rule: Cash + Card must equal Pay Amount.',


    ],

    'detail_keys' => [
        'method' => 'Method',
        'received' => 'Received',
        'applied' => 'Applied',
        'change' => 'Change',
        'cash' => 'Cash',
        'card' => 'Card',
        'total_local' => 'Total Local',
        'old_paid_local' => 'Old Paid Local',
        'new_paid_local' => 'New Paid Local',
        'old_remaining' => 'Old Remaining',
        'new_remaining' => 'New Remaining',
        'note' => 'Note',

        'corrected' => 'Corrected',
        'corrected_from_payment_id' => 'Corrected from payment id',
        'payment_at' => 'Payment at',
        'entered_at' => 'Entered at',
        'backdated' => 'Backdated',
    ],

    'bool' => [
        'yes' => 'Yes',
        'no' => 'No',
    ],

    'method_values' => [
        'cash' => 'Cash',
        'card' => 'Card',
        'mixed' => 'Mixed',
        'phone' => 'Phone',
    ],

    'actions' => [
        'correct' => 'Correct',
        'void' => 'Void',
    ],
    'badges' => [
        'voided' => 'Voided',

    ],
    'voided_by' => 'Voided by',
    'corrected_by' => 'Corrected by',

    'common' => [
        'cancel' => 'Cancel',
    ],

    'modals' => [
        'void' => [
            'title' => 'Void Payment #:id',
            'desc' => 'This will reverse the applied amount from the customer debt and mark the payment as voided.',
            'reason_label' => 'Reason (required)',
            'confirm' => 'Void Payment',
        ],
        'correct' => [
            'title' => 'Correct Payment',
            'payment_date_help' => 'Past dates allowed. Future not allowed.',
            'received_amount' => 'Received Amount',
            'payment_method' => 'Payment Method',
            'note' => 'Note',
            'reason_label' => 'Reason (why correcting)',
            'reason_placeholder' => 'Example: wrong amount/date/method',
            'warning' => 'This will void the old payment and create a new payment.',
            'confirm' => 'Save Correction',
        ],
    ],


];
