<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AUTH
    |--------------------------------------------------------------------------
    */
    'auth' => [
        'login_required'    => 'Giriş alanı zorunludur.',
        'password_required' => 'Şifre alanı zorunludur.',

        'email_not_found'   => 'Bu email adresi ile kayıt bulunamadı.',
        'phone_not_found'   => 'Bu telefon numarası ile kayıt bulunamadı.',
        'invalid_login'     => 'Geçerli bir email veya telefon numarası giriniz.',

        'account_inactive'  => 'Hesabınız aktif değil, lütfen yönetici ile iletişime geçin.',
        'wrong_password'    => 'Şifreniz hatalı!',
    ],

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        'period_invalid' => 'Geçersiz dönem seçimi.',
        'start_date_invalid' => 'Başlangıç tarihi geçersiz.',
        'end_date_invalid' => 'Bitiş tarihi geçersiz.',
        'end_before_start' => 'Bitiş tarihi başlangıç tarihinden önce olamaz.',
    ],

    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */
    'profile' => [
        'firstname_required' => 'Ad alanı zorunludur.',
        'firstname_string' => 'Ad alanı metin olmalıdır.',
        'firstname_max' => 'Ad alanı en fazla :max karakter olabilir.',

        'lastname_required' => 'Soyad alanı zorunludur.',
        'lastname_string' => 'Soyad alanı metin olmalıdır.',
        'lastname_max' => 'Soyad alanı en fazla :max karakter olabilir.',

        'email_required' => 'Email alanı zorunludur.',
        'email_email' => 'Lütfen geçerli bir email adresi giriniz.',
        'email_max' => 'Email alanı en fazla :max karakter olabilir.',
        'email_unique' => 'Bu email adresi zaten kullanılıyor.',

        'phonenumber_required' => 'Telefon alanı zorunludur.',
        'phonenumber_string' => 'Telefon alanı metin olmalıdır.',
        'phonenumber_max' => 'Telefon alanı en fazla :max karakter olabilir.',
        'phonenumber_unique' => 'Bu telefon numarası zaten kullanılıyor.',

        'old_password_string' => 'Mevcut şifre metin olmalıdır.',

        'new_password_string' => 'Yeni şifre metin olmalıdır.',
        'new_password_confirmed' => 'Yeni şifre doğrulaması eşleşmiyor.',
        'new_password_min' => 'Yeni şifre en az :min karakter olmalıdır.',

        'old_password_required_for_change' => 'Şifre değiştirmek için mevcut şifrenizi giriniz.',
        'old_password_incorrect' => 'Mevcut şifreniz hatalı.',

        'no_changes' => 'Herhangi bir değişiklik tespit edilmedi.',
        'password_updated' => 'Şifre başarıyla güncellendi.',
        'profile_updated' => 'Profil başarıyla güncellendi.',
    ],

    /*
    |--------------------------------------------------------------------------
    | USERS
    |--------------------------------------------------------------------------
    */
    'users' => [
        'firstname_required' => 'Ad alanı zorunludur.',
        'firstname_string' => 'Ad alanı metin olmalıdır.',
        'firstname_max' => 'Ad alanı en fazla :max karakter olabilir.',

        'lastname_required' => 'Soyad alanı zorunludur.',
        'lastname_string' => 'Soyad alanı metin olmalıdır.',
        'lastname_max' => 'Soyad alanı en fazla :max karakter olabilir.',

        'email_required' => 'Email alanı zorunludur.',
        'email_email' => 'Lütfen geçerli bir email adresi giriniz.',
        'email_unique' => 'Bu email adresi zaten kullanılıyor.',

        'phonenumber_required' => 'Telefon alanı zorunludur.',
        'phonenumber_unique' => 'Bu telefon numarası zaten kullanılıyor.',

        'password_required' => 'Şifre alanı zorunludur.',
        'password_min' => 'Şifre en az :min karakter olmalıdır.',

        'role_required' => 'Rol alanı zorunludur.',
        'role_invalid' => 'Geçersiz rol seçimi.',

        'status_required' => 'Durum alanı zorunludur.',
        'status_boolean' => 'Durum alanı geçersiz.',

        'created' => 'Kullanıcı eklendi',
        'updated' => 'Kullanıcı güncellendi',
        'deleted' => 'Kullanıcı silindi',
    ],

    /*
    |--------------------------------------------------------------------------
    | CUSTOMERS
    |--------------------------------------------------------------------------
    */
    'customers' => [
        'q_string' => 'Arama alanı metin olmalıdır.',
        'q_max' => 'Arama metni en fazla :max karakter olabilir.',
        'quick_invalid' => 'Geçersiz filtre seçimi.',
    ],

    /*
    |--------------------------------------------------------------------------
    | CUSTOMERS INFO
    |--------------------------------------------------------------------------
    */
    'customers_info' => [
        'q_string' => 'Arama alanı metin olmalıdır.',
        'q_max' => 'Arama metni en fazla :max karakter olabilir.',
        'id_integer' => 'Seçilen kayıt bilgisi geçersiz.',
        'id_min' => 'Seçilen kayıt bilgisi geçersiz.',
    ],

    /*
    |--------------------------------------------------------------------------
    | PAYMENTS
    |--------------------------------------------------------------------------
    */
    'payments' => [
        'auth_required' => 'Ödeme kaydetmek için giriş yapmalısınız.',
        'customer_not_selected' => 'Müşteri seçilmedi.',
        'method_invalid' => 'Ödeme yöntemi geçersiz.',
        'pay_amount_zero' => 'Pay Amount 0 olamaz.',
        'mixed_negative' => 'Mixed: Cash/Card negatif olamaz.',
        'mixed_sum_must_equal' => 'Mixed: Cash + Card toplamı Pay Amount ile eşit olmalı.',
        'invalid_payment_date' => 'Geçersiz ödeme tarihi.',
        'future_payment_date_not_allowed' => 'Gelecek tarihli ödeme yapılamaz.',
        'payment_save_failed' => 'Ödeme kaydedilemedi.',
        'payment_saved' => 'Ödeme başarıyla kaydedildi.',

        'local_debt_missing' => 'Local borç verisi eksik (amount_local / paid_local NULL). Ödeme alınamaz.',
        'debt_closed_paid_ge_total' => 'Bu müşterinin borcu kapanmış (paid_local >= amount_local). Ödeme alınamaz.',
        'debt_closed_remaining_zero' => 'Bu müşterinin borcu kapanmış (kalan 0). Ödeme alınamaz.',
        'overpayment_too_high' => 'Fazla ödeme çok yüksek. Maksimum para üstü: :max',
        'mixed_sum_must_equal_tx' => 'Mixed: Cash + Card toplamı Pay Amount ile eşit olmalı.',
    ],

    /*
    |--------------------------------------------------------------------------
    | PAYMENT CORRECT
    |--------------------------------------------------------------------------
    */
    'payment_correct' => [
        'admin_only' => 'Sadece Admin ödemeleri düzeltebilir.',
        'already_voided' => 'Bu ödeme zaten iptal edilmiş (voided).',

        'payment_method_required' => 'Ödeme yöntemi zorunludur.',
        'payment_method_invalid' => 'Ödeme yöntemi geçersiz.',
        'pay_amount_required' => 'Pay Amount zorunludur.',
        'pay_amount_numeric' => 'Pay Amount sayı olmalıdır.',
        'pay_amount_min' => 'Pay Amount en az :min olmalıdır.',
        'cash_total_numeric' => 'Cash Total sayı olmalıdır.',
        'cash_total_min' => 'Cash Total negatif olamaz.',
        'card_total_numeric' => 'Card Total sayı olmalıdır.',
        'card_total_min' => 'Card Total negatif olamaz.',
        'note_string' => 'Not metin olmalıdır.',
        'note_max' => 'Not en fazla :max karakter olabilir.',
        'reason_string' => 'Sebep metin olmalıdır.',
        'reason_max' => 'Sebep en fazla :max karakter olabilir.',
        'payment_at_date' => 'Ödeme tarihi geçersiz.',

        'future_payment_date_not_allowed' => 'Gelecek tarihli ödeme yapılamaz.',
        'mixed_sum_must_equal' => 'Mixed: Cash + Card toplamı Pay Amount ile eşit olmalı.',

        'local_debt_missing' => 'Local borç verisi eksik. Düzeltme yapılamaz.',
        'debt_closed_use_void_only' => 'Borç zaten kapanmış. Düzeltme ile yeni ödeme girilemez. Sadece VOID kullanın.',
        'overpayment_too_high' => 'Fazla ödeme çok yüksek. Maksimum para üstü: :max',

        'corrected_default_reason' => 'Düzeltildi',

        'correction_failed' => 'Düzeltme başarısız.',
        'corrected_success' => 'Ödeme başarıyla düzeltildi.',
    ],

    /*
    |--------------------------------------------------------------------------
    | PAYMENT VOID
    |--------------------------------------------------------------------------
    */
    'payment_void' => [
        'admin_only' => 'Sadece Admin ödemeleri iptal edebilir.',
        'void_reason_required' => 'İptal sebebi zorunludur.',
        'already_voided' => 'Bu ödeme zaten iptal edilmiş (voided).',
        'local_debt_missing' => 'Local borç verisi eksik (amount_local/paid_local NULL).',
        'void_failed' => 'Ödeme iptal edilemedi.',
        'void_success' => 'Ödeme başarıyla iptal edildi.',
    ],

];
