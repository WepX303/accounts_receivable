<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\UserRoleEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'phonenumber',
        'position',
        'role',
        'branches',
        'daily_report',
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
        'branches' => 'array',
        'daily_report' => 'boolean',
        'token_expires_at' => 'datetime',
    ];

    public function getFullNameAttribute(): string
    {
        return trim("{$this->firstname} {$this->lastname}");
    }

    /*
    |--------------------------------------------------------------------------
    | Branch access
    |--------------------------------------------------------------------------
    | An empty branch list means "every branch", so accounts that predate this
    | feature keep working unchanged. A Super Admin is never restricted — that
    | is what keeps an assignment mistake from locking everyone out.
    */

    /**
     * The branches this user may see, or null when unrestricted.
     *
     * @return string[]|null
     */
    public function allowedBranches(): ?array
    {
        if ($this->isSuperAdmin()) {
            return null;
        }

        $branches = collect($this->branches ?? [])
            ->map(fn ($b) => trim((string) $b))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $branches === [] ? null : $branches;
    }

    public function isBranchRestricted(): bool
    {
        return $this->allowedBranches() !== null;
    }

    /**
     * Branches the nightly report is built for, or null for every branch.
     *
     * Deliberately different from allowedBranches(): this reads the stored list
     * as it is, so a Super Admin with branches assigned receives a report for
     * exactly those branches. Panel access still ignores the list for a Super
     * Admin — that exemption exists to prevent a lockout, and a mailed report
     * cannot lock anyone out.
     *
     * @return string[]|null
     */
    public function dailyReportBranches(): ?array
    {
        $branches = collect($this->branches ?? [])
            ->map(fn ($b) => trim((string) $b))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $branches === [] ? null : $branches;
    }

    public function canAccessBranch(?string $branch): bool
    {
        $allowed = $this->allowedBranches();

        if ($allowed === null) {
            return true;
        }

        return $branch !== null && in_array($branch, $allowed, true);
    }

    /**
     * Stable identifier for the user's branch access, for cache keys that hold
     * branch-dependent data.
     */
    public function branchCacheKey(): string
    {
        $allowed = $this->allowedBranches();

        if ($allowed === null) {
            return 'all';
        }

        sort($allowed);

        return md5(implode('|', $allowed));
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
