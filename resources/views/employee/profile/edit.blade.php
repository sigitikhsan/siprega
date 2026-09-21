@extends('layouts.app')
@section('title', 'Edit Profil')
@section('page-title', 'Edit Profil')
@section('page-subtitle', 'Perbarui informasi yang dapat Anda kelola sendiri')
@section('content')
<style>
    .profile-edit-card { max-width: 860px; margin: auto; overflow: hidden; }
    .profile-upload-preview { width: 88px; height: 88px; display: grid; flex: 0 0 88px; place-items: center; overflow: hidden; color: #2563eb; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 50%; }
    .profile-upload-preview img { width: 100%; height: 100%; display: block; object-fit: cover; }
    .profile-form-actions { display: flex; justify-content: flex-end; gap: .75rem; }
    @media (max-width: 575.98px) { .profile-edit-card .card-body { padding: 1.25rem !important; } .profile-upload { align-items: flex-start !important; flex-direction: column; } .profile-form-actions { flex-direction: column-reverse; } .profile-form-actions .btn { width: 100%; } }
</style>
<div class="card rounded-4 profile-edit-card"><div class="card-body p-4 p-md-5">
    <form method="POST" action="{{ route('employee.profile.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
        <div class="profile-upload d-flex align-items-center gap-3 mb-4">
            <div class="profile-upload-preview">
                @if ($user->employee->avatar_path)
                    <img src="{{ route('employee.profile.avatar', ['v' => $user->employee->updated_at->timestamp]) }}" width="88" height="88" alt="Foto profil saat ini" decoding="async">
                @else
                    <x-icon name="user" size="34" />
                @endif
            </div>
            <div class="flex-grow-1 min-w-0">
                <label class="form-label fw-semibold" for="avatar">Foto profil</label>
                <input class="form-control @error('avatar') is-invalid @enderror" id="avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/webp">
                @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">JPG, PNG, atau WebP maksimal 3 MB. Foto otomatis dipotong dan diperkecil menjadi 320×320.</div>
                @if ($user->employee->avatar_path)<div class="form-check mt-2"><input class="form-check-input" id="remove_avatar" type="checkbox" name="remove_avatar" value="1"><label class="form-check-label" for="remove_avatar">Hapus foto saat ini</label></div>@endif
            </div>
        </div>
        <div class="row g-3">
            <div class="col-12"><label class="form-label" for="name">Nama lengkap</label><input class="form-control @error('name') is-invalid @enderror" id="name" name="name" maxlength="100" value="{{ old('name', $user->name) }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label" for="username">Username</label><input class="form-control @error('username') is-invalid @enderror" id="username" name="username" maxlength="50" pattern="[A-Za-z0-9._-]+" value="{{ old('username', $user->username) }}" required>@error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label" for="email">Email</label><input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" maxlength="150" value="{{ old('email', $user->email) }}">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label" for="phone">Nomor telepon</label><input class="form-control @error('phone') is-invalid @enderror" id="phone" type="tel" name="phone" maxlength="20" value="{{ old('phone', $user->employee->phone) }}">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">Nomor pegawai</label><input class="form-control" value="{{ $user->employee->employee_number }}" disabled><div class="form-text">Hubungi admin untuk mengubah identitas kepegawaian.</div></div>
        </div>
        <div class="profile-form-actions mt-4"><a class="btn btn-light" href="{{ route('employee.profile.show') }}">Batal</a><button class="btn btn-primary" type="submit">Simpan Profil</button></div>
    </form>
</div></div>
@endsection
