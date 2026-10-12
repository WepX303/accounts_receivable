<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\Profile\ProfileException;
use App\Services\Profile\ProfileService;

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

    public function update(Request $request, ProfileService $profiles)
    {

        $user = $this->getAuthenticatedUser($request);
        if (! $user) {
            return redirect()->route('login')->withCookie(cookie()->forget('auth_token'));
        }

        $validated = $request->validate(ProfileService::rules($user), ProfileService::messages());

        try {
            $result = $profiles->update($user, $validated);
        } catch (ProfileException $e) {
            return back()->withErrors([$e->field => $e->getMessage()]);
        }

        return match ($result) {
            ProfileService::UNCHANGED => back()->with('info', __('validations/validations.profile.no_changes')),
            ProfileService::PASSWORD_UPDATED => back()->with('success', __('validations/validations.profile.password_updated')),
            default => back()->with('success', __('validations/validations.profile.profile_updated')),
        };
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
