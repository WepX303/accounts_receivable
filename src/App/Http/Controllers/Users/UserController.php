<?php

namespace App\Http\Controllers\Users;

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Enum;

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
        // $request->validate([
        //     'firstname' => 'required|string|max:255',
        //     'lastname' => 'required|string|max:255',
        //     'email' => 'required|email|unique:users,email',
        //     'phonenumber' => 'required|unique:users,phonenumber',
        //     'password' => 'required|min:6',
        //     'role' => ['required', new Enum(UserRoleEnum::class)],
        //     'status' => 'required|boolean',
        // ]);
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

        User::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'phonenumber' => $request->phonenumber,
            'position' => $request->position,
            'role' => UserRoleEnum::from($request->role)->value,
            'status' => $request->status,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('users.index')->with('success', __('validations/validations.users.created'));
    }

    // UPDATE
    public function update(Request $request, User $user)
    {
        // $request->validate([
        //     'firstname' => 'required|string|max:255',
        //     'lastname' => 'required|string|max:255',
        //     'email' => 'required|email|unique:users,email,' . $user->id,
        //     'phonenumber' => 'required|unique:users,phonenumber,' . $user->id,
        //     'role' => ['required', new Enum(UserRoleEnum::class)],
        //     'status' => 'required|boolean',
        // ]);

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

        return redirect()->route('users.index')->with('success', __('validations/validations.users.updated'));
    }

    // DELETE
    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('users.index')->with('success', __('validations/validations.users.deleted'));
    }
}
