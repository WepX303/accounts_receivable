<?php

return [
    'subject' => 'Daily Collection Report — :date | Net :net :currency',

    'header' => [
        'title' => 'Daily Collection Report',
        'subtitle' => 'Full summary of credit collections for the selected reporting day.',
        'auto_notice' => 'This email was generated automatically by the WepX system. Please do not reply.',
    ],

    'meta' => [
        'report_date' => 'Report Day',
        'generated_at' => 'Generated',
    ],

    'hero' => [
        'net_collected' => 'Net Collected',
        'transactions' => 'transactions',
        'customers' => 'customers',
    ],

    'highlights' => [
        'title' => 'Highlights',
        'collected' => 'A total of :net :currency was collected from :customers customers across :count transactions.',
        'no_payments' => 'No collections were recorded on this day.',
        'vs_yesterday_up' => 'Collections are up :pct% versus yesterday (yesterday: :prev :currency).',
        'vs_yesterday_down' => 'Collections are down :pct% versus yesterday (yesterday: :prev :currency).',
        'vs_yesterday_flat' => 'Collections are level with yesterday (yesterday: :prev :currency).',
        'no_yesterday' => 'No comparison available — there were no collections yesterday.',
        'top_branch' => 'Highest collection at :branch: :net :currency (:share% of the day).',
        'audit_events' => 'Attention: :voids void(s), :corrections correction(s) and :backdated backdated entry/entries were made today. Details below.',
        'audit_clean' => 'No voids, corrections or backdated entries — the day is clean.',
        'overdue' => 'Total overdue receivables stand at :amount :currency (:share% of open balance, :count contracts).',
    ],

    'cash' => [
        'title' => 'Cash Summary',
        'gross' => 'Gross Collected',
        'change' => 'Change Given',
        'net' => 'Net Collected',
        'net_hint' => 'The amount that actually reduced customer debt',
        'cash' => 'Cash',
        'card' => 'Card',
        'phone' => 'Phone',
        'avg_ticket' => 'Average Transaction',
        'max_ticket' => 'Largest Transaction',
    ],

    'comparison' => [
        'title' => 'Comparison',
        'period' => 'Period',
        'amount' => 'Net Amount',
        'change' => 'Change',
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'avg7' => 'Trailing 7-Day Average',
        'mtd' => 'Month to Date (:days days)',
        'mtd_daily_avg' => 'Month-to-Date Daily Average',
        'prev_mtd' => 'Previous Month, Same Period (:label)',
    ],

    'trend' => [
        'title' => 'Last :days Days',
        'no_data' => 'No collections recorded in this period.',
    ],

    'methods' => [
        'title' => 'Payment Method Breakdown',
        'cash' => 'Cash',
        'card' => 'Card',
        'phone' => 'Phone',
        'mixed' => 'Mixed',
        'unknown' => 'Unspecified',
    ],

    'branches' => [
        'title' => 'Branch Breakdown',
    ],

    'cashiers' => [
        'title' => 'Cashier Breakdown',
    ],

    'top' => [
        'title' => 'Top :count Collections of the Day',
    ],

    'audit' => [
        'title' => 'Audit and Items Needing Attention',
        'all_clear' => 'No voids, corrections or backdated entries for this day.',

        'voids_title' => 'Voided Payments',
        'voids_desc' => 'Payments voided on this day. If the payment date belongs to another day, that day\'s cash closing has changed retroactively.',
        'voids_summary' => ':count void(s), :amount :currency in total',

        'corrections_title' => 'Corrected Payments',
        'corrections_desc' => 'Payments whose amount, payment date or method was changed. The difference is the net effect on collections.',
        'corrections_summary' => ':count correction(s), net effect :diff :currency',

        'corrections_date_moved' => ':count of these changed the payment date — the cash closing of the affected days changed retroactively.',

        'backdated_title' => 'Backdated Entries',
        'backdated_desc' => 'Entries made today that carry an earlier payment date. These change the figures of days already closed.',
        'backdated_summary' => ':count entry/entries, :amount :currency in total',

        'idle_title' => 'Branches With No Collections',
        'idle_desc' => 'Branches that carry an open balance but recorded no collection on this day.',
        'idle_summary' => ':count branch(es)',
    ],

    'portfolio' => [
        'title' => 'Portfolio Position',
        'desc' => 'The wider picture the day\'s collection sits inside.',
        'open_credits' => 'Open Contracts',
        'open_balance' => 'Total Open Balance',
        'overdue_credits' => 'Overdue Contracts',
        'overdue_amount' => 'Overdue Amount',
        'overdue_share' => 'Share of Open Balance',
    ],

    'table' => [
        'branch' => 'Branch',
        'method' => 'Method',
        'cashier' => 'Cashier',
        'count' => 'Count',
        'gross' => 'Gross',
        'change' => 'Change',
        'net' => 'Net',
        'cash' => 'Cash',
        'card' => 'Card',
        'phone' => 'Phone',
        'share' => 'Share',
        'customer' => 'Customer',
        'contract' => 'Contract',
        'time' => 'Time',
        'amount' => 'Amount',
        'actor' => 'Performed By',
        'reason' => 'Reason',
        'payment_at' => 'Payment Date',
        'entered_at' => 'Entry Time',
        'days_back' => 'Days Back',
        'payment_date' => 'Payment Date (old → new)',
        'amount_change' => 'Amount (old → new)',
        'method_change' => 'Method',
        'old_amount' => 'Old Amount',
        'new_amount' => 'New Amount',
        'diff' => 'Difference',
        'total' => 'TOTAL',
        'empty' => 'No records.',
        'more' => '… and :count more. See the reports in the system for the full list.',
    ],

    'weekdays' => [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ],

    'footer' => [
        'note' => 'Figures exclude voided payments. Net collected = gross collected − change given.',
        'generated' => 'This report was generated automatically by the WepX system.',
    ],
];
