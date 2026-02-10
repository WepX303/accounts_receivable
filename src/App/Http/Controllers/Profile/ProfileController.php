<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        // Token bazlı auth
        $user = $this->getAuthenticatedUser($request);
        if (! $user) {
            return redirect()->route('login')->withCookie(cookie()->forget('auth_token'));
        }

        return view('pages.profile.profile-settings', compact('user'));
    }

    public function update(Request $request)
    {

        $user = $this->getAuthenticatedUser($request);
        if (! $user) {
            return redirect()->route('login')->withCookie(cookie()->forget('auth_token'));
        }


        $validated = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname'  => 'required|string|max:255',
            'email'     => 'required|email|max:255|unique:users,email,' . $user->id,
            'phonenumber' => 'required|string|max:50|unique:users,phonenumber,' . $user->id,
            'old_password' => 'nullable|string',
            'new_password' => 'nullable|string|confirmed|min:6',
        ]);

        $passwordChanged = false;
        $otherChanged = false;

        if (! empty($validated['new_password'])) {
            if (empty($validated['old_password'])) {
                return back()->withErrors(['old_password' => 'Please enter your current password.']);
            }

            if (! Hash::check($validated['old_password'], $user->password)) {
                return back()->withErrors(['old_password' => 'Your current password is incorrect.']);
            }

            $user->password = Hash::make($validated['new_password']);
            $passwordChanged = true;
        }


        foreach (['firstname', 'lastname', 'email', 'phonenumber'] as $field) {
            $newValue = $validated[$field] ?? null;
            if ($newValue !== $user->$field) {
                $user->$field = $newValue;
                $otherChanged = true;
            }
        }

        if (! $passwordChanged && ! $otherChanged) {
            return back()->with('info', 'No changes detected.');
        }

        $user->save();

        if ($passwordChanged && ! $otherChanged) {
            return back()->with('success', 'Password updated successfully.');
        }

        return back()->with('success', 'Profile updated successfully.');
    }

    // Token üzerinden giriş yapan kullanıcıyı döndüren yardımcı fonksiyon
    private function getAuthenticatedUser(Request $request)
    {
        $token = $request->cookie('auth_token');
        if (! $token) {
            return null;
        }

        return User::where('token', $token)
            ->where('token_expires_at', '>', now())
            ->first();
    }
}
