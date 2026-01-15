<?php

namespace App\Http\Controllers\Users;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Enum;
use App\Enums\UserRoleEnum;

class UserController extends Controller
{
    public function __invoke(Request $request)
    {
        // Tüm kullanıcıları al, status filtrelemesini Blade'de yapacağız
        $users = User::orderBy('id', 'asc')->paginate(10);

        $roles = array_map(fn($role) => $role->value, UserRoleEnum::cases());

        return view('pages.users.index', compact('users', 'roles'));
    }

    // STORE
    public function store(Request $request)
    {
        $request->validate([
            'firstname'   => 'required|string|max:255',
            'lastname'    => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'phonenumber' => 'required|unique:users,phonenumber',
            'password'    => 'required|min:6',
            'role'        => ['required', new Enum(UserRoleEnum::class)],
            'status'      => 'required|boolean',
        ]);

        User::create([
            'firstname'   => $request->firstname,
            'lastname'    => $request->lastname,
            'email'       => $request->email,
            'phonenumber' => $request->phonenumber,
            'position'    => $request->position,
            'role'        => UserRoleEnum::from($request->role)->value, // enum’dan değer al
            'status'      => $request->status,
            'password'    => Hash::make($request->password),
        ]);

        return redirect()->route('users.index')->with('success', 'Kullanıcı eklendi');
    }

    // UPDATE
    public function update(Request $request, User $user)
    {
        $request->validate([
            'firstname'   => 'required|string|max:255',
            'lastname'    => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email,' . $user->id,
            'phonenumber' => 'required|unique:users,phonenumber,' . $user->id,
            'role'        => ['required', new Enum(UserRoleEnum::class)],
            'status'      => 'required|boolean',
        ]);

        $data = $request->only([
            'firstname',
            'lastname',
            'email',
            'phonenumber',
            'position',
            'status'
        ]);

        $data['role'] = UserRoleEnum::from($request->role)->value;


        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Kullanıcı güncellendi');
    }

    // DELETE
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')->with('success', 'Kullanıcı silindi');
    }
}
