<?php

return [
    'validation_fix' => 'Please fix the following:',
    'search_placeholder' => 'Search name / phone / passport / contract / clientref...',

    'empty_title' => 'Search to find customers',
    'empty_desc'  => 'Or select customers in :customers_info and click :payment.',
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
    ],

    'method_values' => [
        'cash'  => 'Cash',
        'card'  => 'Card',
        'mixed' => 'Mixed',
        'phone' => 'Phone',
    ],
];
