<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\UserRoleEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'phonenumber',
        'position',
        'role',
        'status',
        'password',
        'token',
        'token_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'token',

    ];

    protected $casts = [
        'role' => UserRoleEnum::class,
        'token_expires_at' => 'datetime',
    ];

    public function getFullNameAttribute(): string
    {
        return trim("{$this->firstname} {$this->lastname}");
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRoleEnum::SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRoleEnum::ADMIN;
    }

    public function isAdminLike(): bool
    {
        return in_array($this->role, [
            UserRoleEnum::SUPER_ADMIN,
            UserRoleEnum::ADMIN,
        ], true);
    }

    public function hasRole(UserRoleEnum ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function canManageUsers(): bool
    {
        return $this->hasRole(
            UserRoleEnum::SUPER_ADMIN,
            UserRoleEnum::ADMIN
        );
    }

    public function canVoidPayments(): bool
    {
        return $this->hasRole(
            UserRoleEnum::SUPER_ADMIN,
            UserRoleEnum::ADMIN,
            // UserRoleEnum::OPERATOR

        );
    }

    public function canCorrectPayments(): bool
    {
        return $this->hasRole(
            UserRoleEnum::SUPER_ADMIN,
            UserRoleEnum::ADMIN,
            // UserRoleEnum::OPERATOR
        );
    }
}
