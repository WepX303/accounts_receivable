<?php

namespace App\Support;

use App\Enums\UserRoleEnum as Role;
use App\Models\User;

/**
 * Which roles may do what through the API. Mirrors the role groups in
 * routes/web.php; a change there belongs here as well.
 */
class Access
{
    private const MAP = [
        'customers.info' => [Role::SUPER_ADMIN, Role::ADMIN, Role::CASHIER, Role::OPERATOR],
        'payments.take' => [Role::SUPER_ADMIN, Role::ADMIN, Role::CASHIER, Role::OPERATOR],
        'payments.void' => [Role::SUPER_ADMIN, Role::ADMIN],
        'payments.correct' => [Role::SUPER_ADMIN, Role::ADMIN],
        'customers.list' => [Role::SUPER_ADMIN, Role::ADMIN, Role::MANAGER, Role::ANALYST, Role::OPERATOR],
        'reports.avshocrecat' => [Role::SUPER_ADMIN, Role::ADMIN, Role::CASHIER, Role::ANALYST, Role::OPERATOR],
        'reports' => [Role::SUPER_ADMIN, Role::ADMIN, Role::ANALYST],
        'sms' => [Role::SUPER_ADMIN, Role::ADMIN, Role::OPERATOR],
        'users' => [Role::SUPER_ADMIN, Role::ADMIN],
        'logs' => [Role::SUPER_ADMIN],
        'commands' => [Role::SUPER_ADMIN],
        'contracts.create' => [Role::SUPER_ADMIN, Role::ADMIN, Role::CASHIER, Role::OPERATOR],
        'contracts.approve' => [Role::SUPER_ADMIN, Role::ADMIN, Role::OPERATOR],
        'contracts.cancel' => [Role::SUPER_ADMIN, Role::ADMIN],
    ];

    /**
     * Whether the user holds an ability, for checks made outside the routes.
     */
    public static function allows(User $user, string $ability): bool
    {
        return $user->hasRole(...self::MAP[$ability]);
    }

    /**
     * The route middleware that guards an ability, e.g. "role:SuperAdmin,Admin".
     */
    public static function middleware(string $ability): string
    {
        return 'role:' . implode(',', array_map(fn (Role $role) => $role->value, self::MAP[$ability]));
    }

    /**
     * Every ability with whether the user holds it, for the client to build its menu from.
     *
     * @return array<string, bool>
     */
    public static function for(User $user): array
    {
        return array_map(fn (array $roles) => $user->hasRole(...$roles), self::MAP);
    }
}
