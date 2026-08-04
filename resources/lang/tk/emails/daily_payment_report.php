<?php

return [
    'subject' => 'Gündelik Ýygym Hasabaty — :date | Arassa :net :currency',

    'header' => [
        'title' => 'Gündelik Ýygym Hasabaty',
        'subtitle' => 'Saýlanan hasabat güni üçin karz tölegleriniň doly jemi.',
        'auto_notice' => 'Bu hat WepX ulgamy tarapyndan awtomatiki döredildi. Jogap bermäň.',
    ],

    'meta' => [
        'report_date' => 'Hasabat güni',
        'branches' => 'Şahamçalar',
        'all_branches' => 'Ähli şahamçalar',
        'generated_at' => 'Döredilen wagty',
    ],

    'hero' => [
        'net_collected' => 'Arassa ýygym',
        'transactions' => 'amal',
        'customers' => 'müşderi',
    ],

    'highlights' => [
        'title' => 'Esasy görkezijiler',
        'collected' => 'Gün dowamynda :count amalda :customers müşderiden jemi :net :currency arassa ýygym edildi.',
        'no_payments' => 'Bu gün hiç hili töleg hasaba alynmady.',
        'vs_yesterday_up' => 'Düýn bilen deňeşdirilende ýygym :pct% artdy (düýn: :prev :currency).',
        'vs_yesterday_down' => 'Düýn bilen deňeşdirilende ýygym :pct% azaldy (düýn: :prev :currency).',
        'vs_yesterday_flat' => 'Ýygym düýnki derejede (düýn: :prev :currency).',
        'no_yesterday' => 'Düýn ýygym bolmandygy üçin deňeşdirme geçirilmedi.',
        'top_branch' => 'Iň ýokary ýygym :branch şahamçasynda: :net :currency (günüň :share%-i).',
        'audit_events' => 'Üns beriň: bu gün :voids ýatyrma, :corrections düzediş we :backdated yzky sene bilen ýazgy edildi. Jikme-jikligi aşakda.',
        'audit_clean' => 'Ýatyrma, düzediş ýa-da yzky sene bilen ýazgy ýok — gün arassa.',
    ],

    'cash' => [
        'title' => 'Kassa jemi',
        'gross' => 'Umumy ýygym',
        'change' => 'Berlen gaýtargy',
        'net' => 'Arassa ýygym',
        'net_hint' => 'Müşderiniň bergisini hakykatda azaldan möçber',
        'cash' => 'Nagt',
        'card' => 'Kart',
        'phone' => 'Telefon',
        'avg_ticket' => 'Ortaça amal möçberi',
        'max_ticket' => 'Iň ýokary amal',
    ],

    'comparison' => [
        'title' => 'Deňeşdirme',
        'period' => 'Döwür',
        'amount' => 'Arassa möçber',
        'change' => 'Üýtgeme',
        'today' => 'Bu gün',
        'yesterday' => 'Düýn',
        'avg7' => 'Soňky 7 günüň ortaçasy',
        'mtd' => 'Aýyň başyndan bäri (:days gün)',
        'mtd_daily_avg' => 'Aýyň günlük ortaçasy',
        'prev_month_day' => 'Geçen aýyň şol güni (:label)',
    ],

    'trend' => [
        'title' => 'Soňky :days günüň barşy',
        'no_data' => 'Bu döwürde ýygym ýazgysy ýok.',
    ],

    'methods' => [
        'title' => 'Töleg usuly boýunça paýlanyş — :date',
        'cash' => 'Nagt',
        'card' => 'Kart',
        'phone' => 'Telefon',
        'mixed' => 'Garyşyk',
        'unknown' => 'Görkezilmedik',
    ],

    'branches' => [
        'title' => 'Şahamçalar boýunça',
    ],

    'cashiers' => [
        'title' => 'Kassirler boýunça',
    ],

    'top' => [
        'title' => 'Günüň iň ýokary :count ýygymy',
    ],

    'schedule' => [
        'title' => 'Günüň töleg tertibi',
        'desc' => 'Günüň puly, müşderiniň şu gün tölemeli bolandygyna görä bölündi.',

        'due_title' => 'Şu gün tölemeli bolanlar',
        'due_credits' => 'Tölemeli şertnama sany',
        'due_expected' => 'Garaşylýan möçber',
        'due_paid_credits' => 'Tölän şertnama sany',
        'payments_suffix' => ':count töleg',
        'due_paid_amount' => 'Olaryň tölän möçberi',
        'due_unpaid_credits' => 'Tölemedik şertnama sany',
        'due_missing' => 'Ýygnalmadyk möçber',
        'due_rate' => 'Ýerine ýetiriş göterimi',

        'not_due_title' => 'Şu gün tölemeli bolmadyklar',
        'not_due_desc' => 'Şu gün möhleti gelmedik, ýöne şonda-da töleg eden müşderiler.',
        'not_due_credits' => 'Şertnama sany',
        'not_due_payments' => 'Töleg sany',
        'not_due_amount' => 'Jemi möçber',

        'early_title' => 'Möhletinden öň tölänler',
        'early_desc' => 'Geçen möhletli bergisi bolmadyk, öňünden töleýän müşderiler.',
        'late_title' => 'Gijä galyp tölänler',
        'late_desc' => 'Şu güne çenli möhleti geçen bergisi bolan we ony ýapýan müşderiler.',
    ],

    'audit' => [
        'title' => 'Barlag we üns talap edýänler',
        'all_clear' => 'Bu gün üçin ýatyrma, düzediş ýa-da yzky sene bilen ýazgy ýok.',

        'voids_title' => 'Ýatyrylan tölegler',
        'voids_desc' => 'Bu gün ýatyrylan tölegler. Töleg senesi başga güne degişli bolsa, şol günüň kassa ýapylyşy yzky tertipde üýtgändir.',
        'voids_summary' => ':count ýatyrma, jemi :amount :currency',

        'corrections_title' => 'Düzedilen tölegler',
        'corrections_desc' => 'Möçberi, töleg senesi ýa-da usuly üýtgedilen tölegler. Tapawut — ýygym jemine arassa täsiri.',
        'corrections_summary' => ':count düzediş, arassa täsiri :diff :currency',

        'corrections_date_moved' => 'Şolaryň :count sanysynda töleg senesi üýtgedi — degişli günleriň kassa ýapylyşy yzky tertipde täsirlendi.',

        'backdated_title' => 'Yzky sene bilen ýazgylar',
        'backdated_desc' => 'Bu gün girizilen, ýöne töleg senesi geçmişe degişli ýazgylar. Bular ýapylan günleriň sanlaryny üýtgedýär.',
        'backdated_summary' => ':count ýazgy, jemi :amount :currency',

        'idle_title' => 'Ýygym etmedik şahamçalar',
        'idle_desc' => 'Açyk galyndysy bar bolan, ýöne bu gün hiç hili ýygym hasaba almadyk şahamçalar.',
        'idle_summary' => ':count şahamça',
    ],

    'table' => [
        'branch' => 'Şahamça',
        'method' => 'Usul',
        'cashier' => 'Kassir',
        'count' => 'Sany',
        'gross' => 'Umumy',
        'change' => 'Gaýtargy',
        'net' => 'Arassa',
        'cash' => 'Nagt',
        'card' => 'Kart',
        'phone' => 'Telefon',
        'share' => 'Paý',
        'customer' => 'Müşderi',
        'contract' => 'Şertnama',
        'time' => 'Sagat',
        'amount' => 'Möçber',
        'actor' => 'Amaly ýerine ýetiren',
        'reason' => 'Sebäbi',
        'payment_at' => 'Töleg senesi',
        'entered_at' => 'Giriziş wagty',
        'days_back' => 'Gün tapawudy',
        'payment_date' => 'Töleg senesi (öňki → täze)',
        'amount_change' => 'Möçber (öňki → täze)',
        'method_change' => 'Usul',
        'backdated_day' => 'Haýsy güne ýazyldy',
        'old_amount' => 'Öňki möçber',
        'new_amount' => 'Täze möçber',
        'diff' => 'Tapawut',
        'total' => 'JEMI',
        'empty' => 'Ýazgy ýok.',
        'more' => '… we ýene :count ýazgy. Doly sanaw üçin ulgamdaky hasabatlara serediň.',
    ],

    'weekdays' => [
        1 => 'Duşenbe',
        2 => 'Sişenbe',
        3 => 'Çarşenbe',
        4 => 'Penşenbe',
        5 => 'Anna',
        6 => 'Şenbe',
        7 => 'Ýekşenbe',
    ],

    'footer' => [
        'note' => 'Möçberler ýatyrylan tölegler hasaba alynman hasaplandy. Arassa ýygym = umumy ýygym − berlen gaýtargy.',
        'generated' => 'Bu hasabat WepX ulgamy tarapyndan awtomatiki döredildi.',
    ],
];
