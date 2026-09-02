@extends('layouts.app')
@section('title', 'Ubah Password')
@section('page-title', 'Ubah Password')
@section('page-subtitle', 'Perbarui keamanan akun pegawai')
@section('content')
<div class="card rounded-4 mx-auto" style="max-width:760px"><div class="card-body p-4 p-md-5">
    <div class="alert alert-light border">Password baru minimal delapan karakter.</div>
    <form method="POST" action="{{ route('employee.profile.password.update') }}">@csrf @method('PUT')
        <div class="row g-3">
        @foreach ([['current_password','Password saat ini','current-password'],['password','Password baru','new-password'],['password_confirmation','Konfirmasi password baru','new-password']] as [$field,$label,$autocomplete])
            <div class="{{ $field === 'current_password' ? 'col-12' : 'col-md-6' }}"><label class="form-label" for="{{ $field }}">{{ $label }}</label><div class="input-group"><input class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" type="password" name="{{ $field }}" autocomplete="{{ $autocomplete }}" required><button class="btn btn-outline-secondary password-toggle px-3" type="button" data-password-target="{{ $field }}" aria-label="Tampilkan password"><svg class="eye-open" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg><svg class="eye-closed d-none" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M6.6 6.6C3.4 8.5 2 12 2 12s3.5 8 10 8a9.7 9.7 0 0 0 4.1-.9"/></svg></button></div>@error($field)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
        @endforeach
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4"><a class="btn btn-light" href="{{ route('employee.profile.show') }}">Batal</a><button class="btn btn-primary" type="submit">Perbarui Password</button></div>
    </form>
</div></div>
<script>document.querySelectorAll('.password-toggle').forEach(function(button){button.addEventListener('click',function(){var input=document.getElementById(button.dataset.passwordTarget),visible=input.type==='text';input.type=visible?'password':'text';button.querySelector('.eye-open').classList.toggle('d-none',!visible);button.querySelector('.eye-closed').classList.toggle('d-none',visible);button.setAttribute('aria-label',visible?'Tampilkan password':'Sembunyikan password');});});</script>
@endsection
