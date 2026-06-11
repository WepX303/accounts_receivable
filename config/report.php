<?php

return [
    'daily_report_emails' => array_values(array_filter(
        array_map('trim', explode(',', env('DAILY_REPORT_EMAILS', '')))
    )),
];