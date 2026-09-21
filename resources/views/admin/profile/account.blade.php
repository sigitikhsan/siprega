@extends('layouts.app')

@section('title', 'Edit Informasi Akun')
@section('page-title', 'Edit Informasi Akun')
@section('page-subtitle', 'Perbarui identitas administrator')

@section('content')
    <div class="card rounded-4 mx-auto" style="max-width: 760px">
        <div class="card-body p-4 p-md-5">
            <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="avatar">Foto profil</label>
                        <input class="form-control @error('avatar') is-invalid @enderror" id="avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/webp">
                        @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">JPG, PNG, atau WebP maksimal 3 MB.</div>
                        @if ($user->avatar_path)
                            <div class="form-check mt-2">
                                <input class="form-check-input" id="remove_avatar" type="checkbox" name="remove_avatar" value="1">
                                <label class="form-check-label" for="remove_avatar">Hapus foto saat ini</label>
                            </div>
                        @endif
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="name">Nama lengkap</label>
                        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required autofocus>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="username">Username</label>
                        <input class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username', $user->username) }}" required>
                        @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" value="{{ old('email', $user->email) }}">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a class="btn btn-light" href="{{ route('admin.profile.edit') }}">Batal</a>
                    <button class="btn btn-primary" type="submit">Simpan Profil</button>
                </div>
            </form>
        </div>
    </div>
@endsection
