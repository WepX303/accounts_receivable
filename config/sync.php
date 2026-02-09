<?php

return [
    'mssql' => [
        'credits_table' => env('MSSQL_CREDITS_TABLE', 'dbo.CREDITS_TEST'),

        'avshocrecat_proc' => env('MSSQL_AVSHOCRECAT_PROC', 'dbo.AVSHOCRECAT_TEST'),
        'avshocrecat_pasport' => env('MSSQL_AVSHOCRECAT_PASPORT', ''),
    ],

    'pgsql' => [
        'credits_table' => env('PG_CREDITS_TABLE', 'credits_test'),
        'avshocrecat_table' => env('PG_AVSHOCRECAT_TABLE', 'avshocrecat_report'),
    ],

    'chunk_size' => (int) env('SYNC_CHUNK_SIZE', 1000),
];
