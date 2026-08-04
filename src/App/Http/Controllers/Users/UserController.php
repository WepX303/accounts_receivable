<?php

namespace App\Http\Controllers\Users;

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\AuditLogger;

class UserController extends Controller
{
    // INDEX
    public function __invoke(Request $request)
    {
        /** @var \App\Models\User|null $currentUser */
        $currentUser = auth()->user();

        $query = User::orderBy('id', 'asc');

        if (!$currentUser?->isSuperAdmin()) {
            $query->where('role', '!=', UserRoleEnum::SUPER_ADMIN->value);
        }

        $users = $query->paginate(10);

        $roles = collect(UserRoleEnum::cases())
            ->filter(function ($role) use ($currentUser) {
                if ($currentUser?->isSuperAdmin()) {
                    return true;
                }

                return $role !== UserRoleEnum::SUPER_ADMIN;
            })
            ->map(fn($role) => $role->value)
            ->values()
            ->all();

        return view('pages.users.index', [
            'users' => $users,
            'roles' => $roles,
            'branches' => $this->assignableBranches(),
        ]);
    }

    // STORE
    public function store(Request $request)
    {
        /** @var \App\Models\User|null $currentUser */
        $currentUser = auth()->user();

        if (!$currentUser || !$currentUser->canManageUsers()) {
            return back()->with('warning', 'You do not have permission to create users.')->withInput();
        }
        $allowedRoles = collect(UserRoleEnum::cases())
            ->filter(function ($role) use ($currentUser) {
                if ($currentUser->isSuperAdmin()) {
                    return true;
                }

                return $role !== UserRoleEnum::SUPER_ADMIN;
            })
            ->map(fn($role) => $role->value)
            ->values()
            ->all();

        $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phonenumber' => 'required|unique:users,phonenumber',
            'password' => 'required|min:6',
            'role' => ['required', 'in:' . implode(',', $allowedRoles)],
            'branches' => 'nullable|array',
            'branches.*' => 'nullable|string|max:100',
            'daily_report' => 'nullable|boolean',
            'status' => 'required|boolean',
        ], [
            'firstname.required' => __('validations/validations.users.firstname_required'),
            'firstname.string'   => __('validations/validations.users.firstname_string'),
            'firstname.max'      => __('validations/validations.users.firstname_max'),

            'lastname.required' => __('validations/validations.users.lastname_required'),
            'lastname.string'   => __('validations/validations.users.lastname_string'),
            'lastname.max'      => __('validations/validations.users.lastname_max'),

            'email.required' => __('validations/validations.users.email_required'),
            'email.email'    => __('validations/validations.users.email_email'),
            'email.unique'   => __('validations/validations.users.email_unique'),

            'phonenumber.required' => __('validations/validations.users.phonenumber_required'),
            'phonenumber.unique'   => __('validations/validations.users.phonenumber_unique'),

            'password.required' => __('validations/validations.users.password_required'),
            'password.min'      => __('validations/validations.users.password_min'),

            'role.required' => __('validations/validations.users.role_required'),
            'role.enum'     => __('validations/validations.users.role_invalid'),

            'status.required' => __('validations/validations.users.status_required'),
            'status.boolean'  => __('validations/validations.users.status_boolean'),
        ]);

        /** @var \App\Models\User|null $currentUser */
        $currentUser = auth()->user();

        if (
            $request->role === UserRoleEnum::SUPER_ADMIN->value &&
            (!$currentUser || !$currentUser->isSuperAdmin())
        ) {
            return back()->with('warning', 'Only Super Admin can create Super Admin user.')->withInput();
        }

        if (!$currentUser || !$currentUser->canManageUsers()) {
            return back()->with('warning', 'You do not have permission to create users.')->withInput();
        }

        $audit = app(AuditLogger::class);

        // User::create([
        $user = User::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'phonenumber' => $request->phonenumber,
            'position' => $request->position,
            'role' => UserRoleEnum::from($request->role)->value,
            'branches' => $this->resolveBranches($request),
            'daily_report' => $currentUser->isSuperAdmin() && $request->boolean('daily_report'),
            'status' => $request->status,
            'password' => Hash::make($request->password),
        ]);

        $audit->log(
            action: 'user_created',
            category: 'user',
            subject: $user,
            oldValues: null,
            newValues: [
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'phonenumber' => $user->phonenumber,
                'position' => $user->position,
                'role' => $user->role?->value ?? $user->role,
                'branches' => $user->branches,
                'daily_report' => $user->daily_report,
                'status' => $user->status,
            ],
            extra: null,
            message: 'New user created',
            isSuccess: true,
            severity: 'info',
            isSuspicious: false
        );

        return redirect()->route('users.index')->with('success', __('validations/validations.users.created'));
    }

    // UPDATE
    public function update(Request $request, User $user)
    {

        /** @var \App\Models\User|null $currentUser */
        $currentUser = auth()->user();

        if (!$currentUser || !$currentUser->canManageUsers()) {
            return back()->with('warning', 'You do not have permission to update users.')->withInput();
        }

        if (
            $user->role === UserRoleEnum::SUPER_ADMIN &&
            !$currentUser->isSuperAdmin()
        ) {
            return back()->with('warning', 'You cannot modify Super Admin user.')->withInput();
        }

        $allowedRoles = collect(UserRoleEnum::cases())
            ->filter(function ($role) use ($currentUser) {
                if ($currentUser->isSuperAdmin()) {
                    return true;
                }

                return $role !== UserRoleEnum::SUPER_ADMIN;
            })
            ->map(fn($role) => $role->value)
            ->values()
            ->all();

        $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phonenumber' => 'required|unique:users,phonenumber,' . $user->id,
            'role' => ['required', 'in:' . implode(',', $allowedRoles)],
            'branches' => 'nullable|array',
            'branches.*' => 'nullable|string|max:100',
            'daily_report' => 'nullable|boolean',
            'status' => 'required|boolean',
        ], [
            'firstname.required' => __('validations/validations.users.firstname_required'),
            'firstname.string'   => __('validations/validations.users.firstname_string'),
            'firstname.max'      => __('validations/validations.users.firstname_max'),

            'lastname.required' => __('validations/validations.users.lastname_required'),
            'lastname.string'   => __('validations/validations.users.lastname_string'),
            'lastname.max'      => __('validations/validations.users.lastname_max'),

            'email.required' => __('validations/validations.users.email_required'),
            'email.email'    => __('validations/validations.users.email_email'),
            'email.unique'   => __('validations/validations.users.email_unique'),

            'phonenumber.required' => __('validations/validations.users.phonenumber_required'),
            'phonenumber.unique'   => __('validations/validations.users.phonenumber_unique'),

            'role.required' => __('validations/validations.users.role_required'),
            'role.enum'     => __('validations/validations.users.role_invalid'),

            'status.required' => __('validations/validations.users.status_required'),
            'status.boolean'  => __('validations/validations.users.status_boolean'),
        ]);

        /** @var \App\Models\User|null $currentUser */
        $currentUser = auth()->user();

        if (!$currentUser || !$currentUser->canManageUsers()) {
            return back()->with('warning', 'You do not have permission to update users.')->withInput();
        }

        if (
            $user->role === UserRoleEnum::SUPER_ADMIN &&
            !$currentUser->isSuperAdmin()
        ) {
            return back()->with('warning', 'You cannot modify Super Admin user.')->withInput();
        }

        if (
            $request->role === UserRoleEnum::SUPER_ADMIN->value &&
            !$currentUser->isSuperAdmin()
        ) {
            return back()->with('warning', 'Only Super Admin can assign Super Admin role.')->withInput();
        }

        $audit = app(AuditLogger::class);

        $old = $user->only([
            'firstname',
            'lastname',
            'email',
            'phonenumber',
            'position',
            'role',
            'branches',
            'daily_report',
            'status',
        ]);

        $oldRoleValue = $user->role?->value ?? $user->role;
        $old['role'] = $oldRoleValue;

        $data = $request->only([
            'firstname',
            'lastname',
            'email',
            'phonenumber',
            'position',
            'status',
        ]);

        $data['role'] = UserRoleEnum::from($request->role)->value;
        $data['branches'] = $this->resolveBranches($request);
        // Only a Super Admin decides who is on the nightly mailing list; an
        // Admin editing the same account leaves the flag as it was.
        $data['daily_report'] = $currentUser->isSuperAdmin()
            ? $request->boolean('daily_report')
            : (bool) $user->daily_report;

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        $freshUser = $user->fresh();

        $new = $freshUser->only([
            'firstname',
            'lastname',
            'email',
            'phonenumber',
            'position',
            'branches',
            'daily_report',
            'status',
        ]);

        $new['role'] = $freshUser->role?->value ?? $freshUser->role;

        $audit->log(
            action: 'user_updated',
            category: 'user',
            subject: $user,
            oldValues: $old,
            newValues: $new,
            extra: null,
            message: 'User updated',
            isSuccess: true,
            severity: 'warning',
            isSuspicious: ($old['role'] !== $new['role'])
                || ((bool) $old['status'] !== (bool) $new['status'])
                || ($old['branches'] !== $new['branches'])
        );

        if ($old['branches'] !== $new['branches']) {
            $audit->alert(
                alertType: 'user_branches_changed',
                riskLevel: 'high',
                message: 'A user branch access list was changed',
                meta: [
                    'user_id' => $user->id,
                    'old_branches' => $old['branches'],
                    'new_branches' => $new['branches'],
                ]
            );
        }

        if ($old['role'] !== $new['role']) {
            $audit->alert(
                alertType: 'user_role_changed',
                riskLevel: 'high',
                message: 'A user role was changed',
                meta: [
                    'user_id' => $user->id,
                    'old_role' => $old['role'],
                    'new_role' => $new['role'],
                ]
            );
        }

        if ((bool) $old['status'] !== (bool) $new['status']) {
            $audit->alert(
                alertType: 'user_status_changed',
                riskLevel: 'high',
                message: 'A user status was changed',
                meta: [
                    'user_id' => $user->id,
                    'old_status' => $old['status'],
                    'new_status' => $new['status'],
                ]
            );
        }

        return redirect()->route('users.index')->with('success', __('validations/validations.users.updated'));
    }

    // DELETE
    public function destroy(User $user)
    {

        /** @var \App\Models\User|null $currentUser */
        $currentUser = auth()->user();

        if (!$currentUser || !$currentUser->canManageUsers()) {
            return back()->with('warning', 'You do not have permission to delete users.');
        }

        if (
            $user->role === UserRoleEnum::SUPER_ADMIN &&
            !$currentUser->isSuperAdmin()
        ) {
            return back()->with('warning', 'You cannot delete Super Admin user.');
        }

        if ($currentUser->id === $user->id) {
            return back()->with('warning', 'You cannot delete your own account.');
        }

        $audit = app(AuditLogger::class);

        $old = $user->only([
            'id',
            'firstname',
            'lastname',
            'email',
            'phonenumber',
            'position',
            'role',
            'branches',
            'daily_report',
            'status',
        ]);

        $user->delete();

        $audit->log(
            action: 'user_deleted',
            category: 'user',
            subject: null,
            oldValues: $old,
            newValues: null,
            extra: [
                'deleted_user_id' => $old['id'],
            ],
            message: 'User deleted',
            isSuccess: true,
            severity: 'critical',
            isSuspicious: true
        );

        $audit->alert(
            alertType: 'user_deleted',
            riskLevel: 'critical',
            message: 'A user account was deleted',
            meta: $old
        );

        return redirect()->route('users.index')->with('success', __('validations/validations.users.deleted'));
    }

    /**
     * Branches the signed-in user is allowed to hand out.
     *
     * The query is branch-scoped already, so a Super Admin sees every branch
     * while a restricted Admin can only pass on what they hold themselves.
     */
    private function assignableBranches(): array
    {
        return Credit::query()
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch')
            ->all();
    }

    /**
     * Clean up the submitted branch list and drop anything the signed-in user
     * is not entitled to assign. An empty result is stored as null, which means
     * "every branch".
     *
     * @return string[]|null
     */
    private function resolveBranches(Request $request): ?array
    {
        $assignable = $this->assignableBranches();

        $branches = collect($request->input('branches', []))
            ->map(fn ($b) => trim((string) $b))
            ->filter()
            ->unique()
            ->filter(fn ($b) => in_array($b, $assignable, true))
            ->values()
            ->all();

        return $branches === [] ? null : $branches;
    }
}
