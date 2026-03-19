<?php

return [
    'page_title' => 'Buýruk merkezi',
    'page_description' => 'Debitor hasaplary üçin dolandyryş hyzmat buýruklary.',

    'run_command' => 'Buýrugy ýerine ýetir',

    'risk' => [
        'low' => 'Pes howp',
        'medium' => 'Orta howp',
        'high' => 'Ýokary howp',
    ],

    'commands' => [
        'clear_all_caches' => [
            'title' => 'Ähli cacheleri arassala',
            'description' => 'View, config, route we programma cachelerini arassalaýar.',
            'confirm' => 'Ähli cacheleri arassalamak isleýäňizmi?',
            'success' => 'Ähli cacheler üstünlikli arassalandy.',
        ],
        'credits_resync_amount_local' => [
            'title' => 'Credits / Amount Local sinhronlaşdyrma',
            'description' => 'Gabatsyz ýazgylar üçin credits.amount_local meýdanyny credits.amount bilen deňeşdirýär.',
            'confirm' => 'amount_local bahalaryny gaýtadan sinhronlaşdyrmak isleýäňizmi?',
            'success' => 'Sinhronlaşdyrma tamamlandy. Täsir eden: :affected, Täzelenen: :updated',
        ],
    ],
];