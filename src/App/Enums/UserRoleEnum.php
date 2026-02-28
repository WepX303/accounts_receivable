<?php

namespace App\Enums;

use Illuminate\Support\Facades\App;

enum UserRoleEnum: string
{
    case ADMIN = 'Admin';
    case USER = 'User';
    case MANAGER = 'Manager';
    case ANALYST = 'Analyst';
    case CASHIER = 'Cashier';
    case OPERATOR = 'Operator';

    public function label(): string
    {
        return match (App::getLocale()) {
            'tr' => match ($this) {
                self::ADMIN => 'Yönetici',
                self::USER => 'Kullanıcı',
                self::MANAGER => 'Manager',
                self::ANALYST => 'Analist',
                self::CASHIER => 'Kasiyer',
                self::OPERATOR => 'Operatör',
            },
            'ru' => match ($this) {
                self::ADMIN => 'Администратор',
                self::USER => 'Пользователь',
                self::MANAGER => 'Менеджер',
                self::ANALYST => 'Аналитик',
                self::CASHIER => 'Кассир',
                self::OPERATOR => 'Оператор',
            },
            'tk' => match ($this) {
                self::ADMIN => 'Administrator',
                self::USER => 'Ulanyjy',
                self::MANAGER => 'Menejer',
                self::ANALYST => 'Analitik',
                self::CASHIER => 'Kassir',
                self::OPERATOR => 'Operator', 
            },
            default => match ($this) { // tk
                self::ADMIN => 'Administrator',
                self::USER => 'User',
                self::MANAGER => 'Manager',
                self::ANALYST => 'Analyst',
                self::CASHIER => 'Cashier',
                self::OPERATOR => 'Operator',
            },
        };
    }
}
