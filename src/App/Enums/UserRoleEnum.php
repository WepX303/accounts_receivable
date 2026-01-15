<?php

namespace App\Enums;

enum UserRoleEnum: string
{
    case USER  = 'User';
    case ADMIN = 'Admin';
    case ANA   = 'Analyst';
}
