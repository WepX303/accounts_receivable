<?php

return [
    'validation_fix' => 'Aşakdaky ýalňyşlary düzedip görüň:',
    'search_placeholder' => 'Gözleg: ad / telefon / pasport / şertnama / clientref...',
    'view_monthly_payments' => 'Aýlyk tölegleri gör',
    'empty_title' => 'Müşderi tapmak üçin gözleg ediň',
    'empty_desc' => 'Ýa-da :customers_info bölüminde müşderileri saýlap :payment düwmesine basyň.',
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

        'payment_date' => 'Töleg senesi',
        'payment_date_help' => 'Islendik geçen senäni girizip bolýar. Geljek seneler bolmaýar.',
        'mixed_rule_pay_amount' => 'Garyşyk düzgün: Nagt + Kard, töleg möçberine deň bolmaly.',

        'receiver_phone_number' => 'Tölegi kabul eden telefon belgisi',
        'receiver_phone_number_placeholder' => 'Diňe san giriziň',
        'receiver_phone_number_help' => 'Töleg gelen kompaniýa/topar telefon belgisini giriziň.',
        'receiver_phone_number_required' => 'Tölegi kabul eden telefon belgisi boş bolup bilmez.',
        'receiver_phone_number_digits' => 'Tölegi kabul eden telefon belgisinde diňe san bolmaly.',

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

        'corrected' => 'Düzedildi',
        'corrected_from_payment_id' => 'Haýsy tölegden düzedildi (ID)',
        'payment_at' => 'Töleg senesi',
        'entered_at' => 'Girizilen wagty',
        'backdated' => 'Soňkyra ýazylan (backdate)',
    ],

    'bool' => [
        'yes' => 'Hawa',
        'no' => 'Ýok',
    ],

    'method_values' => [
        'cash' => 'Nagt',
        'card' => 'Kard',
        'mixed' => 'Garyşyk',
        'phone' => 'Telefon',
    ],

    'actions' => [
        'correct' => 'Düzelt',
        'void' => 'Ýatyr',
    ],
    'badges' => [
        'voided' => 'Ýatyrylan',
    ],
    'voided_by' => 'Ýatyran',
    'corrected_by' => 'Düzeten',

    'common' => [
        'cancel' => 'Ýatyr',
    ],

    'modals' => [
        'void' => [
            'title' => 'Tölegi ýatyr # :id',
            'desc' => 'Bu amal müşderiniň bergisinden düşen möçberi yzyna alar we tölegi ýatyr hökmünde bellär.',
            'reason_label' => 'Sebäp (hökmany)',
            'confirm' => 'Tölegi ýatyr',
        ],
        'correct' => [
            'title' => 'Tölegi düzet',
            'payment_date_help' => 'Geçen seneler bolýar. Geljek seneler bolmaýar.',
            'received_amount' => 'Alnan möçber',
            'payment_method' => 'Töleg görnüşi',
            'note' => 'Bellik',
            'reason_label' => 'Sebäp (näme üçin düzedilýär)',
            'reason_placeholder' => 'Mysal: nädogry möçber/sene/görnüş',
            'warning' => 'Köne töleg ýatyrylar we täze töleg dörediler.',
            'confirm' => 'Düzedişi ýaz',
        ],
    ],

];
