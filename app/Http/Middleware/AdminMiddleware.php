<?php

/**
 * Membatasi route admin hanya untuk User aktif dengan role admin.
 * Dipasang melalui Kernel dan kelompok route web; autentikasi dasar tetap ditangani middleware auth.
 * Catatan: jangan mengandalkan penyembunyian menu sebagai otorisasi—pemeriksaan ini harus tetap di server.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        if (Auth::user()->role !== 'admin') {
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
