@extends('layouts.app')
@section('title', 'Edit Profil')
@section('page-title', 'Edit Informasi Akun')
@section('page-subtitle', 'Perbarui informasi yang dapat Anda kelola sendiri')
@section('content')
<div class="card rounded-4 mx-auto" style="max-width:760px"><div class="card-body p-4 p-md-5">
    <form method="POST" action="{{ route('employee.profile.update') }}">@csrf @method('PUT')
        <div class="row g-3">
            <div class="col-12"><label class="form-label" for="name">Nama lengkap</label><input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label" for="username">Username</label><input class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username', $user->username) }}" required>@error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label" for="email">Email</label><input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" value="{{ old('email', $user->email) }}">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label" for="phone">Nomor telepon</label><input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $user->employee->phone) }}">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">Nomor pegawai</label><input class="form-control" value="{{ $user->employee->employee_number }}" disabled><div class="form-text">Hubungi admin untuk mengubah data kepegawaian.</div></div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4"><a class="btn btn-light" href="{{ route('employee.profile.show') }}">Batal</a><button class="btn btn-primary" type="submit">Simpan Profil</button></div>
    </form>
</div></div>
@endsection
