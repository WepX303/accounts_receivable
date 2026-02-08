<?php

return [
    'validation_fix' => 'Aşakdaky ýalňyşlary düzedip görüň:',
    'search_placeholder' => 'Gözleg: ad / telefon / pasport / şertnama / clientref...',

    'empty_title' => 'Müşderi tapmak üçin gözleg ediň',
    'empty_desc'  => 'Ýa-da :customers_info bölüminde müşderileri saýlap :payment düwmesine basyň.',
    'customers_info' => 'Müşderi Maglumatlary',
    'payment' => 'Töleg',

    'th' => [
        'name' => 'Ady',
        'contract' => 'Şertnama',
        'remaining' => 'Galyndy',
        'paid_local' => 'Tölenen (Local)',
        'last_paid' => 'Soňky töleg',
    ],

    'local_missing' => 'LOCAL ÝOK',
    'no_records' => 'Ýazgy tapylmady.',
    'total' => 'Jemi',
    'page' => 'Sahypa',

    'select_customer' => 'Maglumat görmek we töleg ýazmak üçin müşderi saýlaň.',

    'detail' => [
        'total_local' => 'Jemi Local (amount_local)',
        'paid_local' => 'Tölenen Local',
        'remaining' => 'Galyndy',
        'last_paid_by' => 'Soňky tölegi alan',
        'last_paid_at' => 'Soňky töleg wagty',
    ],

    'alert_local_missing' => 'Local maglumatlar ýok (amount_local / paid_local NULL). Töleg ýapyk.',
    'alert_closed' => 'Bu müşderiniň bergisi ýok (paid_local >= amount_local). Töleg ýapyk.',

    'last_payment_detail' => 'Soňky töleg maglumatlary',
    'payment_history_last_10' => 'Töleg taryhy (Soňky 10)',
    'no_payment_history' => 'Töleg taryhy ýok.',

    'received' => 'Alnan',
    'applied' => 'Bergä düşen',
    'change' => 'Gaýtargy',
    'cash' => 'Nagt',
    'card' => 'Kard',
    'by' => 'Kim',
    'remaining' => 'Galyndy',

    'form' => [
        'received_amount' => 'Alnan möçber (müşderi berýär)',
        'remaining' => 'Galyndy',
        'applied_to_debt' => 'Bergä düşen',
        'change_to_customer' => 'Gaýtargy (müşderä)',

        'payment_method' => 'Töleg görnüşi',
        'method_cash' => 'Nagt',
        'method_card' => 'Kard',
        'method_mixed' => 'Garyşyk',
        'method_phone' => 'Telefon',

        'all_cash' => 'Ählisi nagt',
        'all_card' => 'Ählisi kard',
        'half' => '50/50',
        'mixed_rule' => 'Nagt + Kard, alnan möçbere deň bolmaly.',

        'note' => 'Bellik',
        'note_placeholder' => 'Islege görä...',
        'save_payment' => 'Tölegi ýaz',
    ],

    'detail_keys' => [
        'method' => 'Görnüşi',
        'received' => 'Alnan',
        'applied' => 'Bergä Düşen',
        'change' => 'Gaýtargy',
        'cash' => 'Nagt',
        'card' => 'Kard',
        'total_local' => 'Jemi Local',
        'old_paid_local' => 'Öňki Tölenen (Local)',
        'new_paid_local' => 'Täze Tölenen (Local)',
        'old_remaining' => 'Öňki Galyndy',
        'new_remaining' => 'Täze Galyndy',
        'note' => 'Bellik',
    ],

    'method_values' => [
        'cash'  => 'Nagt',
        'card'  => 'Kard',
        'mixed' => 'Garyşyk',
        'phone' => 'Telefon',
    ],
];
