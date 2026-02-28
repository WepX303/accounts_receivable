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
        // Token-based authentication
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

        // $validated = $request->validate([
        //     'firstname' => 'required|string|max:255',
        //     'lastname'  => 'required|string|max:255',
        //     'email'     => 'required|email|max:255|unique:users,email,' . $user->id,
        //     'phonenumber' => 'required|string|max:50|unique:users,phonenumber,' . $user->id,
        //     'old_password' => 'nullable|string',
        //     'new_password' => 'nullable|string|confirmed|min:6',
        // ]);

        $validated = $request->validate([
            'firstname'    => 'required|string|max:255',
            'lastname'     => 'required|string|max:255',
            'email'        => 'required|email|max:255|unique:users,email,' . $user->id,
            'phonenumber'  => 'required|string|max:50|unique:users,phonenumber,' . $user->id,
            'old_password' => 'nullable|string',
            'new_password' => 'nullable|string|confirmed|min:6',
        ], [
            // firstname
            'firstname.required' => __('validations/validations.profile.firstname_required'),
            'firstname.string'   => __('validations/validations.profile.firstname_string'),
            'firstname.max'      => __('validations/validations.profile.firstname_max'),

            // lastname
            'lastname.required' => __('validations/validations.profile.lastname_required'),
            'lastname.string'   => __('validations/validations.profile.lastname_string'),
            'lastname.max'      => __('validations/validations.profile.lastname_max'),

            // email
            'email.required' => __('validations/validations.profile.email_required'),
            'email.email'    => __('validations/validations.profile.email_email'),
            'email.max'      => __('validations/validations.profile.email_max'),
            'email.unique'   => __('validations/validations.profile.email_unique'),

            // phonenumber
            'phonenumber.required' => __('validations/validations.profile.phonenumber_required'),
            'phonenumber.string'   => __('validations/validations.profile.phonenumber_string'),
            'phonenumber.max'      => __('validations/validations.profile.phonenumber_max'),
            'phonenumber.unique'   => __('validations/validations.profile.phonenumber_unique'),

            // passwords
            'old_password.string' => __('validations/validations.profile.old_password_string'),

            'new_password.string'     => __('validations/validations.profile.new_password_string'),
            'new_password.confirmed'  => __('validations/validations.profile.new_password_confirmed'),
            'new_password.min'        => __('validations/validations.profile.new_password_min'),
        ]);


        $passwordChanged = false;
        $otherChanged = false;

        if (! empty($validated['new_password'])) {
            if (empty($validated['old_password'])) {
                return back()->withErrors(['old_password' => __('validations/validations.profile.old_password_required_for_change')]);
            }

            if (! Hash::check($validated['old_password'], $user->password)) {
                return back()->withErrors(['old_password' => __('validations/validations.profile.old_password_incorrect')]);
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
            return back()->with('info', __('validations/validations.profile.no_changes'));
        }

        $user->save();

        if ($passwordChanged && ! $otherChanged) {
            return back()->with('success', __('validations/validations.profile.password_updated'));
        }

        return back()->with('success', __('validations/validations.profile.profile_updated'));
    }

    // Helper function that returns the user logging in via token
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
