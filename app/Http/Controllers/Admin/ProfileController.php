<?php

/**
 * Mengelola profil serta perubahan kredensial admin yang sedang login.
 * Beroperasi pada User dari sesi autentikasi dan merotasi token/sesi saat data sensitif berubah.
 * Catatan: verifikasi password lama dan aturan password baru merupakan kontrol keamanan wajib.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProfileAvatarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('admin.profile.edit', ['user' => $request->user()]);
    }

    public function editAccount(Request $request)
    {
        return view('admin.profile.account', ['user' => $request->user()]);
    }

    public function editPassword(Request $request)
    {
        return view('admin.profile.password', ['user' => $request->user()]);
    }

    public function update(Request $request, ProfileAvatarService $avatarService)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'not_regex:/[<>]/'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users')->ignore($user->id)],
            'email' => ['nullable', 'string', 'not_regex:/[\r\n]/', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'avatar' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072', 'dimensions:max_width=3000,max_height=3000'],
            'remove_avatar' => ['nullable', 'boolean'],
        ]);

        $oldAvatar = $user->avatar_path;
        $newAvatar = $request->hasFile('avatar')
            ? $avatarService->storeForAdmin($request->file('avatar'), $user)
            : null;
        $removeAvatar = $request->boolean('remove_avatar');

        try {
            DB::transaction(function () use ($user, $validated, $newAvatar, $removeAvatar) {
                $user->update([
                    'name' => trim($validated['name']),
                    'username' => $validated['username'],
                    'email' => $validated['email'] ?? null,
                    'avatar_path' => $newAvatar ?: ($removeAvatar ? null : $user->avatar_path),
                ]);
            });
        } catch (\Throwable $error) {
            if ($newAvatar) $avatarService->delete($newAvatar);
            throw $error;
        }

        if (($newAvatar || $removeAvatar) && $oldAvatar && $oldAvatar !== $newAvatar) {
            $avatarService->delete($oldAvatar);
        }

        return redirect()->route('admin.profile.edit')
            ->with('success', 'Profil admin berhasil diperbarui.');
    }

    public function avatar(Request $request)
    {
        $user = $request->user();
        abort_unless($user->avatar_path && Storage::disk('local')->exists($user->avatar_path), 404);

        return response(Storage::disk('local')->get($user->avatar_path), 200, [
            'Content-Type' => 'image/webp',
            'Content-Disposition' => 'inline; filename="foto-profil.webp"',
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($validated['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password saat ini tidak sesuai.',
            ]);
        }

        if (Hash::check($validated['password'], $request->user()->password)) {
            throw ValidationException::withMessages([
                'password' => 'Password baru tidak boleh sama dengan password saat ini.',
            ]);
        }

        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.profile.edit')
            ->with('success', 'Password berhasil diperbarui.');
    }
}
