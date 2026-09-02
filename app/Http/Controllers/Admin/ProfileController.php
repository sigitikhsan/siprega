<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->update($validated);

        return redirect()->route('admin.profile.edit')
            ->with('success', 'Profil admin berhasil diperbarui.');
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
