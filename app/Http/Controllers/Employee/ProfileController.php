<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load('employee.workSchedule');
        return view('employee.profile.show', compact('user'));
    }

    public function edit(Request $request)
    {
        $user = $request->user()->load('employee');
        return view('employee.profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'email' => ['nullable', 'string', 'not_regex:/[\r\n]/', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);
        DB::transaction(function () use ($user, $validated) {
            $user->update(['name' => $validated['name'], 'username' => $validated['username'], 'email' => $validated['email'] ?? null]);
            $user->employee->update(['phone' => $validated['phone'] ?? null]);
        });
        return redirect()->route('employee.profile.show')->with('success', 'Profil berhasil diperbarui.');
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
