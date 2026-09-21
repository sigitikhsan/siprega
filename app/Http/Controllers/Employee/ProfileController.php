<?php

/**
 * Menampilkan dan memperbarui profil akun pegawai yang sedang login.
 * Perubahan User dan Employee dilakukan bersama dalam transaksi tanpa mengubah identitas kepegawaian yang dikelola admin.
 * Catatan: perubahan password harus memverifikasi password sekarang dan merotasi token autentikasi terkait.
 */

namespace App\Http\Controllers\Employee;

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
    public function show(Request $request)
    {
        $user = $request->user()->load('employee.workSchedule');
        return view('employee.profile.profile-overview', compact('user'));
    }

    public function edit(Request $request)
    {
        $user = $request->user()->load('employee');
        return view('employee.profile.edit', compact('user'));
    }

    public function update(Request $request, ProfileAvatarService $avatarService)
    {
        $user = $request->user();
        $employee = $user->employee()->firstOrFail();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'not_regex:/[<>]/'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users')->ignore($user->id)],
            'email' => ['nullable', 'string', 'not_regex:/[\r\n]/', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+()\-\s]+$/'],
            'bio' => ['nullable', 'string', 'max:240', 'not_regex:/[<>]/'],
            'profile_accent' => ['nullable', Rule::in(['#2563eb', '#0891b2', '#059669', '#7c3aed', '#e11d48', '#d97706'])],
            'avatar' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072', 'dimensions:max_width=3000,max_height=3000'],
            'remove_avatar' => ['nullable', 'boolean'],
        ]);

        $oldAvatar = $employee->avatar_path;
        $newAvatar = $request->hasFile('avatar') ? $avatarService->store($request->file('avatar'), $employee) : null;
        $removeAvatar = $request->boolean('remove_avatar');

        try {
            DB::transaction(function () use ($user, $employee, $validated, $newAvatar, $removeAvatar) {
                $user->update([
                    'name' => trim($validated['name']),
                    'username' => $validated['username'],
                    'email' => $validated['email'] ?? null,
                ]);
                $employee->update([
                    'phone' => isset($validated['phone']) ? trim($validated['phone']) : null,
                    'bio' => array_key_exists('bio', $validated)
                        ? (!empty($validated['bio']) ? trim($validated['bio']) : null)
                        : $employee->bio,
                    'profile_accent' => $validated['profile_accent'] ?? ($employee->profile_accent ?: '#2563eb'),
                    'avatar_path' => $newAvatar ?: ($removeAvatar ? null : $employee->avatar_path),
                ]);
            });
        } catch (\Throwable $error) {
            if ($newAvatar) $avatarService->delete($newAvatar);
            throw $error;
        }

        if (($newAvatar || $removeAvatar) && $oldAvatar && $oldAvatar !== $newAvatar) {
            $avatarService->delete($oldAvatar);
        }

        return redirect()->route('employee.profile.show')->with('success', 'Profil berhasil diperbarui.');
    }

    public function avatar(Request $request)
    {
        $employee = $request->user()->employee()->firstOrFail();
        abort_unless($employee->avatar_path && Storage::disk('local')->exists($employee->avatar_path), 404);

        return response(Storage::disk('local')->get($employee->avatar_path), 200, [
            'Content-Type' => 'image/webp',
            'Content-Disposition' => 'inline; filename="foto-profil.webp"',
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function editPassword()
    {
        return view('employee.profile.password');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        if (!Hash::check($validated['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages(['current_password' => 'Password saat ini tidak sesuai.']);
        }
        if (Hash::check($validated['password'], $request->user()->password)) {
            throw ValidationException::withMessages(['password' => 'Password baru tidak boleh sama dengan password saat ini.']);
        }
        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();
        $request->session()->regenerate();
        $request->session()->regenerateToken();
        return redirect()->route('employee.profile.show')->with('success', 'Password berhasil diperbarui.');
    }
}
