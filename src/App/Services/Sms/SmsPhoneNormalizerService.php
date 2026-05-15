<?php

namespace App\Services\Sms;

class SmsPhoneNormalizerService
{
    public function normalize(?string $phone): array
    {
        $original = trim((string) $phone);

        if ($original === '') {
            return [
                'valid' => false,
                'phone' => null,
                'reason' => 'empty',
                'original' => $original,
            ];
        }

        // Harf var mı kontrol et
        if (preg_match('/[a-zA-Z\p{L}]/u', $original)) {
            return [
                'valid' => false,
                'phone' => null,
                'reason' => 'contains_letters',
                'original' => $original,
            ];
        }

        // Boşluk, +, -, (, ) gibi karakterleri temizle
        $cleaned = preg_replace('/[^0-9]/', '', $original);

        if ($cleaned === '') {
            return [
                'valid' => false,
                'phone' => null,
                'reason' => 'contains_non_digit',
                'original' => $original,
            ];
        }

        // 8 ile başlıyorsa: 8 kaldır, yerine 993 koy
        if (str_starts_with($cleaned, '8')) {
            return [
                'valid' => true,
                'phone' => '993' . substr($cleaned, 1),
                'reason' => null,
                'original' => $original,
            ];
        }

        // 8 ile başlamıyor ve 6 haneliyse: başına 99365 koy
        if (strlen($cleaned) === 6) {
            return [
                'valid' => true,
                'phone' => '99365' . $cleaned,
                'reason' => null,
                'original' => $original,
            ];
        }

        // Zaten tam sayıysa onu kullan
        return [
            'valid' => true,
            'phone' => $cleaned,
            'reason' => null,
            'original' => $original,
        ];
    }
}