<?php

namespace App\Enums;

use Illuminate\Support\Facades\App;

enum UserRoleEnum: string
{
    case ADMIN = 'Admin';
    case MANAGER = 'MANAGER';
    case ANALYST = 'Analyst';
    case CASHIER = 'Cashier';

    public function label(): string
    {
        return match (App::getLocale()) {
            'tr' => match ($this) {
                self::ADMIN => 'Yönetici',
                self::MANAGER => 'Manager',
                self::ANALYST => 'Analist',
                self::CASHIER => 'Kasiyer',
            },
            'ru' => match ($this) {
                self::ADMIN => 'Администратор',
                self::MANAGER => 'Менеджер',
                self::ANALYST => 'Аналитик',
                self::CASHIER => 'Кассир',
            },
            'tk' => match ($this) {
                self::ADMIN => 'Administrator',
                self::MANAGER => 'Menejer',
                self::ANALYST => 'Analitik',
                self::CASHIER => 'Kassir',
            },
            default => match ($this) { // tk
                self::ADMIN => 'Administrator',
                self::MANAGER => 'Manager',
                self::ANALYST => 'Analyst',
                self::CASHIER => 'Cashier',
            },
        };
    }
}
