<?php

namespace App\Enums;

use Illuminate\Support\Facades\App;

enum UserRoleEnum: string
{
    case ADMIN = 'Admin';
    case USER = 'User';
    case ANALYST = 'Analyst';
    case CASHIER = 'Cashier';

    public function label(): string
    {
        return match (App::getLocale()) {
            'tr' => match ($this) {
                self::ADMIN => 'Yönetici',
                self::USER => 'Kullanıcı',
                self::ANALYST => 'Analist',
                self::CASHIER => 'Kasiyer',
            },
            'ru' => match ($this) {
                self::ADMIN => 'Администратор',
                self::USER => 'Пользователь',
                self::ANALYST => 'Аналитик',
                self::CASHIER => 'Кассир',
            },
            'tk' => match ($this) {
                self::ADMIN => 'Administrator',
                self::USER => 'Ulanyjy',
                self::ANALYST => 'Analitik',
                self::CASHIER => 'Kassir',
            },
            default => match ($this) { // tk
                self::ADMIN => 'Administrator',
                self::USER => 'User',
                self::ANALYST => 'Analyst',
                self::CASHIER => 'Cashier',
            },
        };
    }
}
