<?php

return [
    'page_title' => 'Command Center',
    'page_description' => 'Administrative maintenance commands for accounts receivable operations.',

    'run_command' => 'Run Command',

    'risk' => [
        'low' => 'Low Risk',
        'medium' => 'Medium Risk',
        'high' => 'High Risk',
    ],

    'commands' => [
        'clear_all_caches' => [
            'title' => 'Clear All Caches',
            'description' => 'Clears view, config, route and application caches.',
            'confirm' => 'Are you sure you want to clear all caches?',
            'success' => 'All caches cleared successfully.',
        ],
        'credits_resync_amount_local' => [
            'title' => 'Credits / Resync Amount Local',
            'description' => 'Syncs credits.amount_local with credits.amount for mismatched records.',
            'confirm' => 'Are you sure you want to resync amount_local values?',
            'success' => 'Resync completed. Affected: :affected, Updated: :updated',
        ],
    ],
];