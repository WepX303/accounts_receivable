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
            'details' => 'Kod güncellemesi, yapılandırma değişikliği veya yeni route eklemelerinden sonra sistemin güncel verileri kullanmasını sağlar.',
            'confirm' => 'Tüm cacheleri temizlemek istediğinize emin misiniz?',
            'success' => 'Tüm cacheler başarıyla temizlendi.',
        ],

        'credits_resync_amount_local' => [
            'title' => 'Credits / Amount Local Senkronizasyon',
            'description' => 'Uyumsuz kayıtlar için credits.amount_local alanını credits.amount ile eşitler.',
            'details' => 'amount_local değeri ile amount değeri farklı olan kayıtları tespit eder ve amount_local alanını güncel amount değeri ile yeniden eşitler.',
            'confirm' => 'amount_local değerlerini yeniden senkronize etmek istediğinize emin misiniz?',
            'success' => 'Senkronizasyon tamamlandı. Etkilenen: :affected, Güncellenen: :updated',
        ],
        'sync_credits' => [
            'title' => 'Credits Verilerini Senkronize Et',
            'description' => 'Kaynak veritabanındaki Credits kayıtlarını yerel veritabanı ile senkronize eder.',
            'details' => 'Bu işlem yalnızca yeni veya güncellenmiş kayıtları aktarır. Yerel olarak düzenlenmiş amount_local ve paid_local alanları korunur. Büyük veri hacimlerinde işlem süresi uzayabilir.',
            'confirm' => 'Credits senkronizasyonunu başlatmak istediğinize emin misiniz?',
            'success' => 'Credits senkronizasyonu başarıyla tamamlandı.',
            'error' => 'Credits senkronizasyonu tamamlanamadı. Lütfen veritabanı bağlantısını ve sistem loglarını kontrol edin.',
        ],

        'sync_avshocrecat' => [
            'title' => 'AVSHOCRECAT Raporunu Yenile',
            'description' => 'AVSHOCRECAT rapor verilerini kaynak sistemden yeniden oluşturur.',
            'details' => 'Bu işlem mevcut rapor tablosunu temizler ve en güncel verileri yeniden yükler. İşlem süresince rapor verileri geçici olarak boş görünebilir.',
            'confirm' => 'AVSHOCRECAT raporunu yeniden oluşturmak istediğinize emin misiniz?',
            'success' => 'AVSHOCRECAT raporu başarıyla yenilendi.',
            'error' => 'AVSHOCRECAT raporu yenilenemedi. Lütfen veritabanı bağlantısını ve sistem loglarını kontrol edin.',
        ],
    ],
];
