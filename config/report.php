<?php

return [
    'daily_report_emails' => array_values(array_filter(
        array_map('trim', explode(',', env('DAILY_REPORT_EMAILS', '')))
    )),

    // Language the daily mail is rendered in. Falls back to the app locale.
    'daily_report_locale' => env('DAILY_REPORT_LOCALE', env('APP_LOCALE', 'tk')),
];
