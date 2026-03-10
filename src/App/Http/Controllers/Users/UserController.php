<?php

namespace App\Http\Controllers\Users;

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Enum;
use App\Services\AuditLogger;

class UserController extends Controller
{
    // INDEX
    public function __invoke(Request $request)
    {
        $users = User::orderBy('id', 'asc')->paginate(10);
        $roles = array_map(fn($role) => $role->value, UserRoleEnum::cases());

        return view('pages.users.index', compact('users', 'roles'));
    }

    // STORE
    public function store(Request $request)
    {

        $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phonenumber' => 'required|unique:users,phonenumber',
            'password' => 'required|min:6',
            'role' => ['required', new Enum(UserRoleEnum::class)],
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

        $audit = app(AuditLogger::class);

        // User::create([
        $user = User::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'phonenumber' => $request->phonenumber,
            'position' => $request->position,
            'role' => UserRoleEnum::from($request->role)->value,
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


        $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phonenumber' => 'required|unique:users,phonenumber,' . $user->id,
            'role' => ['required', new Enum(UserRoleEnum::class)],
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
            isSuspicious: ($old['role'] !== $new['role']) || ((bool) $old['status'] !== (bool) $new['status'])
        );

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
}
