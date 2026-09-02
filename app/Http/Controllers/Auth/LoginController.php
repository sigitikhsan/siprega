<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    /**
     * Menampilkan halaman login.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Proses login.
     */
    public function login(Request $request)
    {   
        
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'remember' => 'nullable|boolean',
        ]);

        $normalizedUsername = Str::lower($credentials['username']);
        $accountKey = 'login:account:'.$normalizedUsername;
        $ipKey = 'login:ip:'.$request->ip();
        $limitedKeys = [
            [$accountKey, 5],
            [$ipKey, 20],
        ];

        foreach ($limitedKeys as [$key, $maximumAttempts]) {
            if (!RateLimiter::tooManyAttempts($key, $maximumAttempts)) continue;

            $seconds = RateLimiter::availableIn($key);
            return redirect()->route('login')
                ->withErrors(['throttle' => 'Terlalu banyak aksi. Silakan coba lagi setelah waktu tunggu berakhir.'])
                ->with('retry_after', $seconds)
                ->onlyInput('username');
        }

        $remember = $request->boolean('remember');
        unset($credentials['remember']);

        $credentials['status'] = 'active';

        if (Auth::attempt($credentials, $remember)) {
            RateLimiter::clear($accountKey);
            $request->session()->regenerate();
            $request->session()->forget('url.intended');

            if (Auth::user()->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            if (Auth::user()->role === 'employee') {
                return redirect()->route('employee.dashboard');
            }

            Auth::logout();

            return redirect()->route('login')->withErrors([
                'username' => 'Role pengguna tidak valid.',
            ])->onlyInput('username');
        }

        RateLimiter::hit($accountKey, 300);
        RateLimiter::hit($ipKey, 300);

        return redirect()->route('login')
            ->withErrors([
                'username' => 'Username atau password salah, atau akun tidak aktif.',
            ])
            ->onlyInput('username');
    }

    /**
     * Logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
