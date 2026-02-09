<?php

namespace App\Enums;

enum UserRoleEnum: string
{
    case ADMIN = 'Admin';
    case USER  = 'User';
    case ANA   = 'Analyst';
    case CASHIER = 'Cashier';
}
