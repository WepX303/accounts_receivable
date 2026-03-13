<?php

namespace App\Http\Controllers\Api\V1\Users;

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiUserController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User|null $currentUser */
        $currentUser = $request->user();

        if (!$currentUser || !$currentUser->canManageUsers()) {
            return ApiResponse::error(
                'You do not have permission to view users.',
                403,
                'USER_VIEW_FORBIDDEN'
            );
        }

        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $q = trim((string) $request->input('q', ''));
        $perPage = (int) $request->input('per_page', 20);

        $query = User::query()->orderBy('id', 'asc');

        if (!$currentUser->isSuperAdmin()) {
            $query->where('role', '!=', UserRoleEnum::SUPER_ADMIN->value);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('firstname', 'ILIKE', "%{$q}%")
                    ->orWhere('lastname', 'ILIKE', "%{$q}%")
                    ->orWhere('email', 'ILIKE', "%{$q}%")
                    ->orWhere('phonenumber', 'ILIKE', "%{$q}%")
                    ->orWhere('position', 'ILIKE', "%{$q}%");
            });
        }

        $users = $query
            ->paginate($perPage)
            ->appends($request->query());

        $allowedRoles = collect(UserRoleEnum::cases())
            ->filter(function ($role) use ($currentUser) {
                if ($currentUser->isSuperAdmin()) {
                    return true;
                }

                return $role !== UserRoleEnum::SUPER_ADMIN;
            })
            ->map(fn($role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ])
            ->values()
            ->all();

        return ApiResponse::paginated(
            $users,
            [
                'users' => UserResource::collection($users->getCollection())->resolve(),
                'allowed_roles' => $allowedRoles,
            ],
            'Users fetched successfully.'
        );
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User|null $currentUser */
        $currentUser = $request->user();

        if (!$currentUser || !$currentUser->canManageUsers()) {
            return ApiResponse::error(
                'You do not have permission to create users.',
                403,
                'USER_CREATE_FORBIDDEN'
            );
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

        $validated = $request->validate([
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phonenumber' => ['required', 'string', 'max:50', 'unique:users,phonenumber'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'in:' . implode(',', $allowedRoles)],
            'position' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ]);

        if (
            $validated['role'] === UserRoleEnum::SUPER_ADMIN->value &&
            !$currentUser->isSuperAdmin()
        ) {
            return ApiResponse::error(
                'Only Super Admin can create Super Admin user.',
                403,
                'SUPER_ADMIN_CREATE_FORBIDDEN'
            );
        }

        $audit = app(AuditLogger::class);

        $user = User::create([
            'firstname' => $validated['firstname'],
            'lastname' => $validated['lastname'],
            'email' => $validated['email'],
            'phonenumber' => $validated['phonenumber'],
            'password' => Hash::make($validated['password']),
            'role' => UserRoleEnum::from($validated['role'])->value,
            'position' => $validated['position'] ?? null,
            'status' => $validated['status'],
        ]);

        $audit->log(
            action: 'api_user_created',
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
                'status' => $user->status,
            ],
            extra: null,
            message: 'User created via API',
            isSuccess: true,
            severity: 'info',
            isSuspicious: false
        );

        return ApiResponse::success(
            new UserResource($user),
            'User created successfully.',
            201
        );
    }

    public function update(Request $request, User $user)
    {
        /** @var \App\Models\User|null $currentUser */
        $currentUser = $request->user();

        if (!$currentUser || !$currentUser->canManageUsers()) {
            return ApiResponse::error(
                'You do not have permission to update users.',
                403,
                'USER_UPDATE_FORBIDDEN'
            );
        }

        if (
            $user->role === UserRoleEnum::SUPER_ADMIN &&
            !$currentUser->isSuperAdmin()
        ) {
            return ApiResponse::error(
                'You cannot modify Super Admin user.',
                403,
                'SUPER_ADMIN_MODIFY_FORBIDDEN'
            );
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

        $validated = $request->validate([
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phonenumber' => ['required', 'string', 'max:50', 'unique:users,phonenumber,' . $user->id],
            'role' => ['required', 'in:' . implode(',', $allowedRoles)],
            'position' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        if (
            $validated['role'] === UserRoleEnum::SUPER_ADMIN->value &&
            !$currentUser->isSuperAdmin()
        ) {
            return ApiResponse::error(
                'Only Super Admin can assign Super Admin role.',
                403,
                'SUPER_ADMIN_ASSIGN_FORBIDDEN'
            );
        }

        $audit = app(AuditLogger::class);

        $old = $user->only([
            'firstname',
            'lastname',
            'email',
            'phonenumber',
            'position',
            'role',
            'status',
        ]);

        $old['role'] = $user->role?->value ?? $user->role;

        $data = [
            'firstname' => $validated['firstname'],
            'lastname' => $validated['lastname'],
            'email' => $validated['email'],
            'phonenumber' => $validated['phonenumber'],
            'position' => $validated['position'] ?? null,
            'status' => $validated['status'],
            'role' => UserRoleEnum::from($validated['role'])->value,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        $freshUser = $user->fresh();

        $new = $freshUser->only([
            'firstname',
            'lastname',
            'email',
            'phonenumber',
            'position',
            'status',
        ]);

        $new['role'] = $freshUser->role?->value ?? $freshUser->role;

        $audit->log(
            action: 'api_user_updated',
            category: 'user',
            subject: $user,
            oldValues: $old,
            newValues: $new,
            extra: null,
            message: 'User updated via API',
            isSuccess: true,
            severity: 'warning',
            isSuspicious: ($old['role'] !== $new['role']) || ((bool) $old['status'] !== (bool) $new['status'])
        );

        if ($old['role'] !== $new['role']) {
            $audit->alert(
                alertType: 'api_user_role_changed',
                riskLevel: 'high',
                message: 'A user role was changed via API',
                meta: [
                    'user_id' => $user->id,
                    'old_role' => $old['role'],
                    'new_role' => $new['role'],
                ]
            );
        }

        if ((bool) $old['status'] !== (bool) $new['status']) {
            $audit->alert(
                alertType: 'api_user_status_changed',
                riskLevel: 'high',
                message: 'A user status was changed via API',
                meta: [
                    'user_id' => $user->id,
                    'old_status' => $old['status'],
                    'new_status' => $new['status'],
                ]
            );
        }

        return ApiResponse::success(
            new UserResource($freshUser),
            'User updated successfully.'
        );
    }

    public function destroy(Request $request, User $user)
    {
        /** @var \App\Models\User|null $currentUser */
        $currentUser = $request->user();

        if (!$currentUser || !$currentUser->canManageUsers()) {
            return ApiResponse::error(
                'You do not have permission to delete users.',
                403,
                'USER_DELETE_FORBIDDEN'
            );
        }

        if (
            $user->role === UserRoleEnum::SUPER_ADMIN &&
            !$currentUser->isSuperAdmin()
        ) {
            return ApiResponse::error(
                'You cannot delete Super Admin user.',
                403,
                'SUPER_ADMIN_DELETE_FORBIDDEN'
            );
        }

        if ($currentUser->id === $user->id) {
            return ApiResponse::error(
                'You cannot delete your own account.',
                422,
                'SELF_DELETE_FORBIDDEN'
            );
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
            'status',
        ]);

        $old['role'] = $user->role?->value ?? $user->role;

        $user->delete();

        $audit->log(
            action: 'api_user_deleted',
            category: 'user',
            subject: null,
            oldValues: $old,
            newValues: null,
            extra: [
                'deleted_user_id' => $old['id'],
            ],
            message: 'User deleted via API',
            isSuccess: true,
            severity: 'critical',
            isSuspicious: true
        );

        $audit->alert(
            alertType: 'api_user_deleted',
            riskLevel: 'critical',
            message: 'A user account was deleted via API',
            meta: $old
        );

        return ApiResponse::success(
            null,
            'User deleted successfully.'
        );
    }
}