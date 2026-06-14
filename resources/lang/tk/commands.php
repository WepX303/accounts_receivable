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
            'details' => 'Kod täzelenenden, sazlamalar üýtgedilenden ýa-da täze route goşulandan soň ulgamyň iň täze maglumatlary ulanmagyny üpjün edýär.',
            'confirm' => 'Ähli cacheleri arassalamak isleýäňizmi?',
            'success' => 'Ähli cacheler üstünlikli arassalandy.',
        ],

        'credits_resync_amount_local' => [
            'title' => 'Credits / Amount Local sinhronlaşdyrma',
            'description' => 'Gabatsyz ýazgylar üçin credits.amount_local meýdanyny credits.amount bilen deňeşdirýär.',
            'details' => 'amount_local bilen amount bahalary tapawutlanýan ýazgylary tapýar we amount_local meýdanyny häzirki amount bahasy bilen deňleýär.',
            'confirm' => 'amount_local bahalaryny gaýtadan sinhronlaşdyrmak isleýäňizmi?',
            'success' => 'Sinhronlaşdyrma tamamlandy. Täsir eden: :affected, Täzelenen: :updated',
        ],
        'sync_credits' => [
            'title' => 'Credits maglumatlaryny sinhronlaşdyr',
            'description' => 'Çeşme maglumat bazasyndaky Credits ýazgylaryny ýerli maglumat bazasy bilen sinhronlaşdyrýar.',
            'details' => 'Diňe täze ýa-da üýtgedilen ýazgylar geçirilýär. amount_local we paid_local ýaly ýerli meýdanlar saklanýar. Uly maglumat möçberlerinde amal biraz köp wagt alyp biler.',
            'confirm' => 'Credits sinhronlaşdyrmasyny başlatmak isleýäňizmi?',
            'success' => 'Credits sinhronlaşdyrmasy üstünlikli tamamlandy.',
            'error' => 'Credits sinhronlaşdyrmasyny ýerine ýetirip bolmady. Maglumat bazasynyň baglanyşygyny we ulgam loglaryny barlaň.',
        ],

        'sync_avshocrecat' => [
            'title' => 'AVSHOCRECAT hasabatyny täzele',
            'description' => 'AVSHOCRECAT hasabat maglumatlaryny çeşme ulgamyndan täzeden döredýär.',
            'details' => 'Bu amal häzirki hasabat tablisasyny arassalaýar we iň täze maglumatlary täzeden ýükläp doldurýar. Amal wagtynda hasabat maglumatlary wagtlaýynça elýeterli bolman biler.',
            'confirm' => 'AVSHOCRECAT hasabatyny täzeden döretmek isleýäňizmi?',
            'success' => 'AVSHOCRECAT hasabaty üstünlikli täzelendi.',
            'error' => 'AVSHOCRECAT hasabatyny täzelemek başartmady. Maglumat bazasynyň baglanyşygyny we ulgam loglaryny barlaň.',
        ],
    ],
];
