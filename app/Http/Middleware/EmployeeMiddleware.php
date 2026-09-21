<?php

/**
 * Membatasi route pegawai hanya untuk User aktif dengan role employee.
 * Menjadi lapisan akses sebelum controller dashboard, absensi, profil, riwayat, dan izin/sakit.
 * Catatan: controller tetap harus membatasi query pada Employee milik user yang sedang login.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        if (Auth::user()->role !== 'employee') {
            abort(403, 'Akses ditolak.');
        }

        if (Auth::user()->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'username' => 'Akun Anda sudah dinonaktifkan.',
            ]);
        }

        return $next($request);
    }
}
