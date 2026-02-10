<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Login formu göster
    public function showLoginForm(Request $request)
    {
        $token = $request->cookie('auth_token');

        if ($token) {
            $user = User::where('token', $token)
                ->where('token_expires_at', '>', now())
                ->first();

            if ($user) {
                return redirect()->route('dashboard');
            } else {
                // Geçersiz token varsa cookie silinsin
                return redirect()->route('login')->withCookie(cookie()->forget('auth_token'));
            }
        }

        return view('auth.login');
    }

    // Login işlemi
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required',
            'password' => 'required',
        ], [
            'login.required' => 'Login alanı zorunludur.',
            'password.required' => 'Şifre alanı zorunludur.',
        ]);

        $login = $request->login;

        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);
        $isPhone = preg_match('/^[0-9\+\-\s]{6,20}$/', $login);

        $user = User::where('email', $login)
            ->orWhere('phonenumber', $login)
            ->first();

        if (! $user) {
            $errorMessage = $isEmail
                ? 'Bu email adresi ile kayıt bulunamadı.'
                : ($isPhone ? 'Bu telefon numarası ile kayıt bulunamadı.' : 'Geçerli bir email veya telefon numarası giriniz.');

            return back()->withErrors(['login' => $errorMessage])->withInput();
        }

        // Status kontrolü
        if (! $user->status) {
            return back()->withErrors(['login' => 'Hesabınız aktif değil, lütfen yönetici ile iletişime geçin.'])->withInput();
        }

        if (! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Şifreniz hatalı!'])->withInput();
        }

        // Yeni token oluştur
        $token = bin2hex(random_bytes(32));
        $user->token = $token;
        $user->token_expires_at = now()->addDay();
        $user->save();

        // Cookie oluştur ve redirect et
        return redirect()->route('dashboard')->withCookie(
            cookie(
                'auth_token',
                $token,
                60 * 24,    // 1 gün
                '/',        // tüm pathler için geçerli
                null,
                false,
                true        // httpOnly
            )
        );
    }

    // Logout işlemi
    public function logout(Request $request)
    {
        $token = $request->cookie('auth_token');

        if ($token) {
            $user = User::where('token', $token)->first();
            if ($user) {
                $user->token = null;
                $user->token_expires_at = null;
                $user->save();
            }
        }

        // Cookie'yi tüm path ve domain için sil ve cache kontrol header ekle
        $cookie = cookie()->forget('auth_token');

        return redirect()->route('login')
            ->withCookie($cookie)
            ->withHeaders([
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
    }
}
