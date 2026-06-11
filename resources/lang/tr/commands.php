<?php

return [
    'page_title' => 'Komut Merkezi',
    'page_description' => 'Alacak yönetimi işlemleri için yönetim komutları.',

    'run_command' => 'Komutu Çalıştır',

    'risk' => [
        'low' => 'Düşük Risk',
        'medium' => 'Orta Risk',
        'high' => 'Yüksek Risk',
    ],

    'commands' => [
        'clear_all_caches' => [
            'title' => 'Tüm Cacheleri Temizle',
            'description' => 'View, config, route ve uygulama cachelerini temizler.',
            'confirm' => 'Tüm cacheleri temizlemek istediğinize emin misiniz?',
            'success' => 'Tüm cacheler başarıyla temizlendi.',
        ],
        'credits_resync_amount_local' => [
            'title' => 'Credits / Amount Local Senkronizasyon',
            'description' => 'Uyumsuz kayıtlar için credits.amount_local alanını credits.amount ile eşitler.',
            'confirm' => 'amount_local değerlerini yeniden senkronize etmek istediğinize emin misiniz?',
            'success' => 'Senkronizasyon tamamlandı. Etkilenen: :affected, Güncellenen: :updated',
        ],
    ],
];