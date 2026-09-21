@extends('layouts.app')

@section('title', 'Profil Admin')
@section('page-title', 'Profil Admin')
@section('page-subtitle', 'Kelola informasi akun dan keamanan password')

@section('content')
    <style>
        .profile-card { max-width: 760px; margin-inline: auto; overflow: hidden; background: var(--warm-surface); border: 1px solid var(--warm-border); border-radius: 1.1rem; }
        .profile-header { padding: 2rem 1.5rem 1.5rem; text-align: center; }
        .profile-card-avatar { width: 88px; height: 88px; display: grid; place-items: center; margin-inline: auto; overflow: hidden; color: #174f9f; background: #eef5ff; border: 1px solid #d9e6f8; border-radius: 50%; }
        .profile-card-avatar img { width: 100%; height: 100%; display: block; object-fit: cover; border-radius: 50%; }
        .profile-role { display: inline-block; padding: .3rem .75rem; color: #5b6777; background: #f1f3f5; border-radius: 999px; font-size: .78rem; }
        .profile-details { margin: 0 1.5rem; border-top: 1px solid var(--warm-border); }
        .profile-detail { display: flex; align-items: center; gap: 1rem; padding: 1rem 0; }
        .profile-detail + .profile-detail { border-top: 1px solid var(--warm-border); }
        .profile-detail-icon, .profile-setting-icon { width: 40px; height: 40px; display: grid; flex: 0 0 40px; place-items: center; color: #174f9f; }
        .profile-detail-label { color: #888680; font-size: .78rem; }
        .profile-settings-title { margin: 0 1.5rem; padding-top: 1.35rem; color: #888680; border-top: 1px solid var(--warm-border); font-size: .76rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; }
        .profile-settings { padding: .55rem 1.5rem 1.5rem; }
        .profile-setting { display: flex; align-items: center; gap: 1rem; padding: 1rem 0; color: #242321; text-decoration: none; }
        .profile-setting + .profile-setting { border-top: 1px solid var(--warm-border); }
        .profile-setting:hover { color: #174f9f; }
        .profile-setting-arrow { margin-left: auto; color: #8b8a86; }
    </style>

    <section class="profile-card">
        <header class="profile-header">
            <div class="profile-card-avatar mb-3">
                @if ($user->avatar_path)
                    <img src="{{ route('admin.profile.avatar', ['v' => $user->updated_at->timestamp]) }}" width="88" height="88" alt="Foto profil {{ $user->name }}" decoding="async">
                @else
                    <x-icon name="user" size="42" />
                @endif
            </div>
            <h2 class="h4 fw-semibold mb-2">{{ $user->name }}</h2>
            <span class="profile-role">Administrator</span>
        </header>

        <div class="profile-details">
            <div class="profile-detail">
                <span class="profile-detail-icon"><x-icon name="badge" /></span>
                <div><div class="profile-detail-label">Username</div><div class="fw-semibold">{{ $user->username }}</div></div>
            </div>
            <div class="profile-detail">
                <span class="profile-detail-icon"><x-icon name="mail" /></span>
                <div><div class="profile-detail-label">Email</div><div class="fw-semibold text-break">{{ $user->email ?: 'Belum diisi' }}</div></div>
            </div>
            <div class="profile-detail">
                <span class="profile-detail-icon"><x-icon name="info" /></span>
                <div><div class="profile-detail-label">Status akun</div><div class="fw-semibold">{{ $user->status === 'active' ? 'Aktif' : 'Nonaktif' }}</div></div>
            </div>
        </div>

        <h3 class="profile-settings-title">Pengaturan Akun</h3>
        <nav class="profile-settings" aria-label="Pengaturan akun">
            <a class="profile-setting" href="{{ route('admin.profile.account') }}">
                <span class="profile-setting-icon"><x-icon name="edit" /></span>
                <span><span class="d-block fw-semibold">Edit Informasi Akun</span><small class="text-muted">Ubah nama, username, alamat email, dan foto profil.</small></span>
                <x-icon name="chevron" class="profile-setting-arrow" />
            </a>
            <a class="profile-setting" href="{{ route('admin.profile.password.edit') }}">
                <span class="profile-setting-icon"><x-icon name="lock" /></span>
                <span><span class="d-block fw-semibold">Ubah Password</span><small class="text-muted">Perbarui keamanan password akun.</small></span>
                <x-icon name="chevron" class="profile-setting-arrow" />
            </a>
        </nav>
    </section>
@endsection
