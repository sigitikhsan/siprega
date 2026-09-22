@extends('layouts.app')
@section('title', 'Profil Pegawai')
@section('page-title', 'Profil Pegawai')
@section('page-subtitle', 'Informasi kepegawaian dan pengaturan akun')
@section('content')
<style>
    .employee-profile { max-width:780px; margin:auto; overflow:hidden; border:1px solid var(--warm-border); border-radius:1.2rem; background:var(--warm-surface); }
    .employee-profile-head { padding:2rem 1.5rem 1.5rem; text-align:center; }
    .employee-profile-avatar { width:88px; height:88px; display:grid; place-items:center; margin:auto; overflow:hidden; color:#174f9f; background:#eef5ff; border:1px solid #d9e6f8; border-radius:50%; }
    .employee-profile-avatar img { width:100%; height:100%; display:block; object-fit:cover; border-radius:50%; }
    .employee-profile-row { display:flex; justify-content:space-between; gap:1rem; padding:1rem 0; border-bottom:1px solid var(--warm-border); }
    .employee-profile-row:last-child { border-bottom:0; }
    .employee-profile-link { display:flex; align-items:center; gap:1rem; padding:1rem 0; color:#242321; text-decoration:none; }
    .employee-profile-link + .employee-profile-link { border-top:1px solid var(--warm-border); }
    @media (max-width:575.98px) {
        .employee-profile-head { padding:1.5rem 1rem 1.25rem; }
        .employee-profile-row { align-items:flex-start; flex-direction:column; gap:.35rem; }
        .employee-profile-row .text-end { text-align:left !important; }
    }
</style>
<section class="employee-profile">
    <header class="employee-profile-head">
        <div class="employee-profile-avatar mb-3">
            @if ($user->employee->avatar_path)
                <img src="{{ route('employee.profile.avatar', ['v' => $user->employee->updated_at->timestamp]) }}" width="88" height="88" alt="Foto profil {{ $user->name }}" decoding="async">
            @else
                <x-icon name="user" size="42" />
            @endif
        </div>
        <h2 class="h4 fw-semibold mb-1">{{ $user->name }}</h2>
        <div class="text-muted">{{ $user->employee->employee_number }} · {{ $user->employee->position ?: 'Pegawai' }}</div>
    </header>
    <div class="px-4 pb-3">
        <div class="employee-profile-row"><span class="text-muted">Username</span><strong>{{ $user->username }}</strong></div>
        <div class="employee-profile-row"><span class="text-muted">Email</span><span class="text-end">{{ $user->email ?: 'Belum diisi' }}</span></div>
        <div class="employee-profile-row"><span class="text-muted">Telepon</span><span>{{ $user->employee->phone ?: 'Belum diisi' }}</span></div>
        <div class="employee-profile-row"><span class="text-muted">Perusahaan</span><span class="text-end">{{ $user->employee->company ?: '-' }}</span></div>
        <div class="employee-profile-row"><span class="text-muted">Status akun</span><span class="badge bg-success align-self-center">Aktif</span></div>
    </div>
    <div class="border-top px-4 py-3"><div class="small fw-bold text-muted text-uppercase mb-2">Pengaturan Akun</div>
        <a class="employee-profile-link" href="{{ route('employee.profile.edit') }}"><x-icon name="edit" /><span><strong class="d-block">Edit Informasi Akun</strong><small class="text-muted">Ubah nama, username, email, telepon, dan foto profil.</small></span><x-icon name="chevron" class="ms-auto" /></a>
        <a class="employee-profile-link" href="{{ route('employee.profile.password.edit') }}"><x-icon name="lock" /><span><strong class="d-block">Ubah Password</strong><small class="text-muted">Perbarui keamanan password akun.</small></span><x-icon name="chevron" class="ms-auto" /></a>
    </div>
</section>
@endsection
