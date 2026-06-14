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
            'details' => 'Ensures the system uses the latest data after code deployments, configuration updates, or route changes.',
            'confirm' => 'Are you sure you want to clear all caches?',
            'success' => 'All caches cleared successfully.',
        ],

        'credits_resync_amount_local' => [
            'title' => 'Credits / Resync Amount Local',
            'description' => 'Syncs credits.amount_local with credits.amount for mismatched records.',
            'details' => 'Finds records where amount_local differs from amount and updates amount_local to match the current amount value.',
            'confirm' => 'Are you sure you want to resync amount_local values?',
            'success' => 'Resync completed. Affected: :affected, Updated: :updated',
        ],
        'sync_credits' => [
            'title' => 'Synchronize Credits Data',
            'description' => 'Synchronizes Credits records from the source database to the local database.',
            'details' => 'Only new or updated records are transferred. Locally maintained fields such as amount_local and paid_local are preserved. Processing may take longer for large datasets.',
            'confirm' => 'Are you sure you want to start Credits synchronization?',
            'success' => 'Credits synchronization completed successfully.',
            'error' => 'Credits synchronization could not be completed. Please check the database connection and system logs.',
        ],

        'sync_avshocrecat' => [
            'title' => 'Refresh AVSHOCRECAT Report',
            'description' => 'Rebuilds AVSHOCRECAT report data from the source system.',
            'details' => 'This operation clears the current report table and reloads the latest available data. Report information may be temporarily unavailable during processing.',
            'confirm' => 'Are you sure you want to rebuild the AVSHOCRECAT report?',
            'success' => 'AVSHOCRECAT report refreshed successfully.',
            'error' => 'AVSHOCRECAT report could not be refreshed. Please check the database connection and system logs.',
        ],
    ],
];
