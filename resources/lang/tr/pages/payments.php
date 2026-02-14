<?php

return [
    'validation_fix' => 'Lütfen aşağıdaki hataları düzeltin:',
    'search_placeholder' => 'Ara: ad / telefon / pasaport / sözleşme / clientref...',

    'empty_title' => 'Müşteri bulmak için arama yapın',
    'empty_desc' => 'Veya :customers_info sayfasında müşterileri seçip :payment butonuna tıklayın.',
    'customers_info' => 'Müşteri Bilgisi',
    'payment' => 'Ödeme',

    'th' => [
        'name' => 'Ad',
        'contract' => 'Sözleşme',
        'remaining' => 'Kalan',
        'paid_local' => 'Ödenen (Local)',
        'last_paid' => 'Son Ödeme',
    ],

    'local_missing' => 'LOCAL EKSİK',
    'no_records' => 'Kayıt bulunamadı.',
    'total' => 'Toplam',
    'page' => 'Sayfa',

    'select_customer' => 'Detay ve ödeme kaydı için bir müşteri seçin.',

    'detail' => [
        'total_local' => 'Toplam Local (amount_local)',
        'paid_local' => 'Ödenen Local',
        'remaining' => 'Kalan',
        'last_paid_by' => 'Son Ödemeyi Alan',
        'last_paid_at' => 'Son Ödeme Tarihi',
    ],

    'alert_local_missing' => 'Local alanlar eksik (amount_local / paid_local NULL). Ödeme kapatıldı.',
    'alert_closed' => 'Bu müşterinin borcu kalmadı (paid_local >= amount_local). Ödeme kapatıldı.',

    'last_payment_detail' => 'Son Ödeme Detayı',
    'payment_history_last_10' => 'Ödeme Geçmişi (Son 10)',
    'no_payment_history' => 'Ödeme geçmişi yok.',

    'received' => 'Alınan',
    'applied' => 'Borçtan düşen',
    'change' => 'Para üstü',
    'cash' => 'Nakit',
    'card' => 'Kart',
    'by' => 'Yapan',
    'remaining' => 'Kalan',

    'form' => [
        'received_amount' => 'Alınan Tutar (Müşterinin verdiği)',
        'remaining' => 'Kalan',
        'applied_to_debt' => 'Borçtan düşen',
        'change_to_customer' => 'Para üstü (müşteriye)',

        'payment_method' => 'Ödeme Yöntemi',
        'method_cash' => 'Nakit',
        'method_card' => 'Kart',
        'method_mixed' => 'Karma',
        'method_phone' => 'Telefon',

        'all_cash' => 'Hepsi Nakit',
        'all_card' => 'Hepsi Kart',
        'half' => '50/50',
        'mixed_rule' => 'Nakit + Kart, Alınan Tutara eşit olmalı.',

        'note' => 'Not',
        'note_placeholder' => 'İsteğe bağlı...',
        'save_payment' => 'Ödemeyi Kaydet',
    ],

    'detail_keys' => [
        'method' => 'Yöntem',
        'received' => 'Alınan',
        'applied' => 'Borca Düşen',
        'change' => 'Para Üstü',
        'cash' => 'Nakit',
        'card' => 'Kart',
        'total_local' => 'Toplam Local',
        'old_paid_local' => 'Eski Ödenen (Local)',
        'new_paid_local' => 'Yeni Ödenen (Local)',
        'old_remaining' => 'Eski Kalan',
        'new_remaining' => 'Yeni Kalan',
        'note' => 'Not',
    ],

    'method_values' => [
        'cash' => 'Nakit',
        'card' => 'Kart',
        'mixed' => 'Karışık',
        'phone' => 'Telefon',
    ],
];
