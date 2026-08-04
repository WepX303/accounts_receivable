<?php

return [
    'subject' => 'Günlük Tahsilat Raporu — :date | Net :net :currency',

    'header' => [
        'title' => 'Günlük Tahsilat Raporu',
        'subtitle' => 'Seçilen rapor günü için kredi tahsilatlarının tam özeti.',
        'auto_notice' => 'Bu e-posta WepX sistemi tarafından otomatik olarak oluşturulmuştur. Lütfen yanıtlamayınız.',
    ],

    'meta' => [
        'report_date' => 'Rapor Günü',
        'branches' => 'Şubeler',
        'all_branches' => 'Tüm şubeler',
        'generated_at' => 'Oluşturulma',
    ],

    'hero' => [
        'net_collected' => 'Net Tahsilat',
        'transactions' => 'işlem',
        'customers' => 'müşteri',
    ],

    'highlights' => [
        'title' => 'Öne Çıkanlar',
        'collected' => 'Gün boyunca :count işlemde :customers müşteriden toplam :net :currency net tahsilat yapıldı.',
        'no_payments' => 'Bu gün hiç tahsilat kaydedilmedi.',
        'vs_yesterday_up' => 'Düne göre tahsilat %:pct arttı (dün: :prev :currency).',
        'vs_yesterday_down' => 'Düne göre tahsilat %:pct azaldı (dün: :prev :currency).',
        'vs_yesterday_flat' => 'Tahsilat dünkü seviyede (dün: :prev :currency).',
        'no_yesterday' => 'Dün hiç tahsilat olmadığı için karşılaştırma yapılamadı.',
        'top_branch' => 'En yüksek tahsilat :branch şubesinde: :net :currency (günün %:share\'i).',
        'audit_events' => 'Dikkat: bugün :voids iptal, :corrections düzeltme ve :backdated geriye dönük tarihli kayıt yapıldı. Detay aşağıda.',
        'audit_clean' => 'İptal, düzeltme veya geriye dönük tarihli kayıt yok — gün temiz.',
    ],

    'cash' => [
        'title' => 'Kasa Özeti',
        'gross' => 'Brüt Tahsilat',
        'change' => 'Verilen Para Üstü',
        'net' => 'Net Tahsilat',
        'net_hint' => 'Müşteri borcunu fiilen azaltan tutar',
        'cash' => 'Nakit',
        'card' => 'Kart',
        'phone' => 'Telefon',
        'avg_ticket' => 'Ortalama İşlem Tutarı',
        'max_ticket' => 'En Yüksek İşlem',
    ],

    'comparison' => [
        'title' => 'Karşılaştırma',
        'period' => 'Dönem',
        'amount' => 'Net Tutar',
        'change' => 'Değişim',
        'today' => 'Bugün',
        'yesterday' => 'Dün',
        'avg7' => 'Son 7 Gün Ortalaması',
        'mtd' => 'Ay Başından Bugüne (:days gün)',
        'mtd_daily_avg' => 'Aylık Günlük Ortalama',
        'prev_month_day' => 'Geçen ayın aynı günü (:label)',
    ],

    'trend' => [
        'title' => 'Son :days Günün Seyri',
        'no_data' => 'Bu dönemde tahsilat kaydı yok.',
    ],

    'methods' => [
        'title' => 'Ödeme Yöntemi Dağılımı — :date',
        'cash' => 'Nakit',
        'card' => 'Kart',
        'phone' => 'Telefon',
        'mixed' => 'Karma',
        'unknown' => 'Belirtilmemiş',
    ],

    'branches' => [
        'title' => 'Şube Kırılımı',
    ],

    'cashiers' => [
        'title' => 'Kasiyer Kırılımı',
    ],

    'top' => [
        'title' => 'Günün En Yüksek :count Tahsilatı',
    ],

    'schedule' => [
        'title' => 'Günün Ödeme Planı',
        'desc' => 'Günün parası, müşterinin bugün ödemesi gerekip gerekmediğine göre ayrıldı.',

        'due_title' => 'Bugün Ödemesi Gerekenler',
        'due_credits' => 'Ödemesi gereken sözleşme',
        'due_expected' => 'Beklenen tutar',
        'due_paid_credits' => 'Ödeyen sözleşme',
        'due_paid_payments' => 'Yaptıkları ödeme sayısı',
        'due_paid_amount' => 'Ödedikleri tutar',
        'due_unpaid_credits' => 'Ödemeyen sözleşme',
        'due_missing' => 'Tahsil edilemeyen tutar',
        'due_rate' => 'Gerçekleşme oranı',

        'not_due_title' => 'Bugün Ödemesi Gerekmeyenler',
        'not_due_desc' => 'Bugün vadesi olmadığı hâlde ödeme yapan müşteriler.',
        'not_due_credits' => 'Sözleşme sayısı',
        'not_due_payments' => 'Ödeme sayısı',
        'not_due_amount' => 'Toplam tutar',

        'early_title' => 'Vadesinden Önce Ödeyenler',
        'early_desc' => 'Vadesi geçmiş borcu bulunmayan, peşin ödeyen müşteriler.',
        'late_title' => 'Geç Kalıp Ödeyenler',
        'late_desc' => 'Bugüne kadar vadesi geçmiş borcu olan ve onu kapatan müşteriler.',
    ],

    'audit' => [
        'title' => 'Denetim ve Dikkat Gerektirenler',
        'all_clear' => 'Bu gün için iptal, düzeltme veya geriye dönük tarihli kayıt bulunmuyor.',

        'voids_title' => 'İptal Edilen Ödemeler',
        'voids_desc' => 'Bu gün iptal edilen ödemeler. Ödeme tarihi farklı bir güne aitse o günün kasa kapanışı geriye dönük değişmiştir.',
        'voids_summary' => ':count iptal, toplam :amount :currency',

        'corrections_title' => 'Düzeltilen Ödemeler',
        'corrections_desc' => 'Tutarı, ödeme tarihi veya yöntemi değiştirilen ödemeler. Fark, tahsilat toplamına net etkisidir.',
        'corrections_summary' => ':count düzeltme, net etki :diff :currency',

        'corrections_date_moved' => 'Bunlardan :count tanesinde ödeme tarihi değişti — ilgili günlerin kasa kapanışı geriye dönük etkilendi.',

        'backdated_title' => 'Geriye Dönük Tarihli Kayıtlar',
        'backdated_desc' => 'Bugün girilen fakat ödeme tarihi geçmişe ait olan kayıtlar. Bunlar kapanmış günlerin rakamlarını değiştirir.',
        'backdated_summary' => ':count kayıt, toplam :amount :currency',

        'idle_title' => 'Hiç Tahsilat Yapmayan Şubeler',
        'idle_desc' => 'Açık bakiyesi olan fakat bu gün hiç tahsilat kaydetmeyen şubeler.',
        'idle_summary' => ':count şube',
    ],

    'portfolio' => [
        'title' => 'Portföy Durumu',
        'desc' => 'Günün tahsilatının içinde bulunduğu genel tablo.',
        'open_credits' => 'Açık Sözleşme Sayısı',
        'open_balance' => 'Toplam Açık Bakiye',
        'overdue_credits' => 'Vadesi Geçen Sözleşme',
        'overdue_amount' => 'Vadesi Geçen Tutar',
        'overdue_share' => 'Açık Bakiyeye Oranı',
    ],

    'table' => [
        'branch' => 'Şube',
        'method' => 'Yöntem',
        'cashier' => 'Kasiyer',
        'count' => 'Adet',
        'gross' => 'Brüt',
        'change' => 'Para Üstü',
        'net' => 'Net',
        'cash' => 'Nakit',
        'card' => 'Kart',
        'phone' => 'Telefon',
        'share' => 'Pay',
        'customer' => 'Müşteri',
        'contract' => 'Sözleşme',
        'time' => 'Saat',
        'amount' => 'Tutar',
        'actor' => 'İşlemi Yapan',
        'reason' => 'Gerekçe',
        'payment_at' => 'Ödeme Tarihi',
        'entered_at' => 'Giriş Zamanı',
        'days_back' => 'Gün Farkı',
        'payment_date' => 'Ödeme Tarihi (eski → yeni)',
        'amount_change' => 'Tutar (eski → yeni)',
        'method_change' => 'Yöntem',
        'backdated_day' => 'Hangi güne yazıldı',
        'old_amount' => 'Eski Tutar',
        'new_amount' => 'Yeni Tutar',
        'diff' => 'Fark',
        'total' => 'TOPLAM',
        'empty' => 'Kayıt yok.',
        'more' => '… ve :count kayıt daha. Tamamı için sistemdeki raporlara bakınız.',
    ],

    'weekdays' => [
        1 => 'Pazartesi',
        2 => 'Salı',
        3 => 'Çarşamba',
        4 => 'Perşembe',
        5 => 'Cuma',
        6 => 'Cumartesi',
        7 => 'Pazar',
    ],

    'footer' => [
        'note' => 'Tutarlar iptal edilmiş ödemeler hariç tutularak hesaplanmıştır. Net tahsilat = brüt tahsilat − verilen para üstü.',
        'generated' => 'Bu rapor WepX sistemi tarafından otomatik olarak oluşturulmuştur.',
    ],
];
