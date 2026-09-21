{{--
    Layout autentikasi utama untuk sidebar, header, aset bersama, pesan flash, dan slot konten halaman.
    Dipakai halaman admin serta pegawai; script berat tetap dimuat oleh halaman yang membutuhkannya saja.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistem Absensi') - Kantor Balmon</title>
    <link href="{{ mix('css/bootstrap-app.min.css') }}" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 270px;
            --navy: #08275a;
            --blue: #1764dc;
            --warm-bg: #f8fafc;
            --warm-surface: #ffffff;
            --warm-border: #e2e8f0;
            --surface-muted: #f8fafc;
            --ink: #0f172a;
            --muted: #64748b;
            --primary: #1764dc;
        }
        body {
            background: var(--warm-bg);
            color: var(--ink);
            line-height: 1.55;
            letter-spacing: -.005em;
        }
        .sidebar {
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            min-height: 100vh;
            background: linear-gradient(160deg, #0b326f 0%, #061d46 100%);
            transition: margin-left .25s ease, transform .25s ease;
            z-index: 1040;
        }
        .sidebar .brand { min-width: 0; color: #fff; letter-spacing: .1px; border-bottom: 1px solid rgba(255,255,255,.14); }
        .brand-copy { min-width: 0; line-height: 1.2; }
        .brand-name { display: block; font-size: 1.2rem; line-height: 1.2; }
        .brand-subtitle { display: block; margin-top: .35rem; color: rgba(255,255,255,.58); font-size: .75rem; font-weight: 400; line-height: 1.35; white-space: nowrap; }
        .brand-icon { width: 44px; height: 44px; display: grid; place-items: center; color: #dbeafe; border: 2px solid rgba(219,234,254,.85); border-radius: .55rem; }
        .sidebar .nav-link { position: relative; display: flex; align-items: center; gap: .85rem; color: #d6e2f5; border-radius: .7rem; padding: .85rem 1rem; font-size: .925rem; transition: color .18s ease, background-color .18s ease; }
        .sidebar .nav-link::before { content: ''; position: absolute; top: .55rem; bottom: .55rem; left: -1rem; width: 4px; background: #4f7cff; border-radius: 0 .3rem .3rem 0; transform: scaleY(0); transform-origin: center; transition: transform .2s ease; }
        .sidebar .nav-link:hover { color: #fff; background: transparent; }
        .sidebar .nav-link:hover::before { transform: scaleY(1); }
        .sidebar .nav-link.active { color: #fff; background: transparent; }
        .sidebar .nav-link.active::before { transform: scaleY(1); background: #8babff; }
        .sidebar .nav-link.disabled { opacity: .65; background: transparent; }
        .sidebar-nav { flex: 1 1 auto; }
        .sidebar-footer { border-top: 1px solid rgba(255,255,255,.14); }
        .sidebar-profile { min-width: 0; color: #fff; }
        a.sidebar-profile:hover { color: #fff; }
        .sidebar-profile > div { min-width: 0; }
        .sidebar-profile-avatar {
            width: 40px;
            height: 40px;
            display: grid;
            flex: 0 0 40px;
            place-items: center;
            color: #e6efff;
            background: rgba(255,255,255,.09);
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 50%;
            overflow: hidden;
        }
        .sidebar-profile-avatar img, .profile-avatar img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            border-radius: 50% !important;
            clip-path: circle(50% at 50% 50%);
        }
        .sidebar-profile-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .925rem; }
        .sidebar-profile-role { color: rgba(255,255,255,.62); font-size: .78rem; }
        .app-shell.sidebar-collapsed .sidebar { margin-left: calc(var(--sidebar-width) * -1); }
        .main-content { min-width: 0; background: var(--warm-bg); }
        .topbar { min-height: 92px; background: rgba(255,255,255,.96); border-bottom: 1px solid var(--warm-border); }
        .sidebar-toggle {
            width: 42px;
            height: 42px;
            display: inline-grid;
            place-items: center;
            border: 0;
            border-radius: .65rem;
            background: var(--warm-surface);
            color: #172554;
        }
        .sidebar-toggle:hover { background: #f1f5f9; }
        .sidebar-overlay { display: none; }
        .profile-avatar { width: 44px; height: 44px; flex: 0 0 44px; display: grid; place-items: center; overflow: hidden; color: #6a6965; background: var(--warm-surface); border: 1px solid var(--warm-border); border-radius: 50% !important; clip-path: circle(50% at 50% 50%); }
        .card, .stat-card {
            background: var(--warm-surface);
            border: 1px solid var(--warm-border);
            border-radius: 1rem;
            box-shadow: 0 8px 24px rgba(15,23,42,.05);
        }
        .stat-card { overflow: hidden; }
        .stat-card .card-body { min-height: 0; }
        a.stat-card { transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease; }
        a.stat-card:hover { transform: translateY(-2px); color: var(--ink); background: #fff; border-color: #bfdbfe; box-shadow: 0 12px 28px rgba(15,23,42,.09); }
        .stat-icon { width: 56px; height: 56px; display: grid; place-items: center; border: 1px solid #d6e6ff; border-radius: .85rem; color: #0d5cda; background: #eff6ff; }
        .quick-arrow { color: #0a2a5f; }
        .welcome-alert { display: flex; align-items: center; gap: 1rem; color: #16468c; background: #f7faff; border: 1px solid #d8e5f6; border-radius: .7rem; }
        .availability-badge { color: #23653b; background: #e8f6ec; }
        .logout-button { display: flex; justify-content: center; align-items: center; gap: .65rem; font-size: .925rem; }
        .content-wrapper { max-width: 1680px; width: 100%; margin-inline: auto; }
        .content-wrapper > .card .card-body { letter-spacing: 0; }
        h1, h2, h3, h4, h5, h6 { letter-spacing: -.02em; }
        .text-muted { color: var(--muted) !important; }
        .form-label { margin-bottom: .45rem; color: #334155; font-size: .875rem; font-weight: 600; }
        .form-control, .form-select {
            min-height: 46px;
            color: var(--ink);
            background-color: #fff;
            border-color: #cbd5e1;
            border-radius: .7rem;
            transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
        }
        textarea.form-control { min-height: auto; }
        .form-control:hover, .form-select:hover { border-color: #94a3b8; }
        .form-control:focus, .form-select:focus {
            color: var(--ink);
            background-color: #fff;
            border-color: #60a5fa;
            box-shadow: 0 0 0 .2rem rgba(37,99,235,.12);
        }
        .input-group > .form-control, .input-group > .form-select { min-width: 0; }
        .input-group .btn { border-color: #cbd5e1; }
        .form-text { color: var(--muted); font-size: .8rem; }
        .btn {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            border-radius: .7rem;
            font-weight: 600;
            transition: transform .18s ease, color .18s ease, background-color .18s ease, border-color .18s ease, box-shadow .18s ease;
        }
        .btn:hover:not(:disabled) { transform: translateY(-1px); }
        .btn.btn-sm { min-height: 34px; border-radius: .55rem; }
        .btn-primary { background-color: var(--primary); border-color: var(--primary); }
        .btn-primary:hover, .btn-primary:focus-visible { background-color: #1259c3; border-color: #1259c3; box-shadow: 0 7px 18px rgba(23,100,220,.18); }
        .btn-light { color: #334155; background: #f1f5f9; border-color: #e2e8f0; }
        .btn-light:hover, .btn-light:focus-visible { color: #0f172a; background: #e2e8f0; border-color: #cbd5e1; }
        .btn-outline-primary { color: var(--primary); border-color: #93c5fd; }
        .btn-outline-primary:hover { background: var(--primary); border-color: var(--primary); }
        .table-responsive { border-radius: .8rem; }
        .table { --bs-table-bg: transparent; --bs-table-hover-bg: #f8fafc; margin-bottom: 0; color: #334155; }
        .table > :not(caption) > * > * { padding: .9rem .75rem; border-bottom-color: #e2e8f0; vertical-align: middle; }
        .table thead th {
            color: #64748b;
            background: #f8fafc;
            border-bottom-width: 1px;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .045em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .table tbody tr { transition: background-color .16s ease; }
        .table tbody tr:last-child > * { border-bottom: 0; }
        .pagination { gap: .3rem; }
        .page-link { min-width: 38px; min-height: 38px; display: grid; place-items: center; color: #475569; border-color: #e2e8f0; border-radius: .55rem !important; }
        .active > .page-link, .page-link.active { color: #fff; background: var(--primary); border-color: var(--primary); }
        .alert { border-radius: .85rem; border-width: 1px; }
        .alert-success { color: #166534; background: #f0fdf4; border-color: #bbf7d0; }
        .alert-danger { color: #991b1b; background: #fef2f2; border-color: #fecaca; }
        .alert-warning { color: #92400e; background: #fffbeb; border-color: #fde68a; }
        .modal-backdrop.show { opacity: .46; }
        .modal-dialog { padding-inline: .5rem; }
        .modal-content { overflow: hidden; background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; box-shadow: 0 24px 70px rgba(15,23,42,.18); }
        .modal-header { padding: 1.2rem 1.35rem; background: #f8fafc; border-color: #e2e8f0; }
        .modal-title { color: #0f172a; letter-spacing: -.02em; }
        .modal-body { padding: 1.35rem; color: #334155; }
        .modal-footer { gap: .5rem; padding: 1rem 1.35rem; background: #f8fafc; border-color: #e2e8f0; }
        .badge { padding: .4em .65em; border-radius: 999px; font-weight: 600; }
        .detail-panel, .leave-panel, .profile-card, .employee-profile {
            background: #fff;
            border-color: #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 8px 24px rgba(15,23,42,.05);
        }
        .detail-row, .leave-row, .profile-detail + .profile-detail,
        .profile-settings-title, .profile-setting + .profile-setting,
        .employee-profile-row, .employee-profile-link + .employee-profile-link { border-color: #e2e8f0; }
        .profile-setting, .employee-profile-link { transition: color .18s ease, transform .18s ease; }
        .profile-setting:hover, .employee-profile-link:hover { color: var(--primary); transform: translateX(3px); }
        /* Both workspaces use the same calm, legible navigation language. */
        body.role-employee .sidebar,
        body.role-admin .sidebar {
            color: #172033;
            background: #fff;
            border-right: 1px solid #e2e8f0;
            box-shadow: 8px 0 28px rgba(15, 23, 42, .025);
        }
        body.role-employee .sidebar .brand,
        body.role-admin .sidebar .brand { color: #172033; border-bottom-color: #e2e8f0; }
        body.role-employee .brand-subtitle,
        body.role-admin .brand-subtitle { color: #64748b; }
        body.role-employee .brand-icon,
        body.role-admin .brand-icon { color: #1764dc; background: #eff6ff; border-color: #dbeafe; }
        body.role-employee .sidebar .nav-link,
        body.role-admin .sidebar .nav-link { color: #475569; }
        body.role-employee .sidebar .nav-link::before,
        body.role-admin .sidebar .nav-link::before { left: -1rem; background: #1764dc; }
        body.role-employee .sidebar .nav-link:hover,
        body.role-admin .sidebar .nav-link:hover { color: #0f172a; background: #f8fafc; }
        body.role-employee .sidebar .nav-link.active,
        body.role-admin .sidebar .nav-link.active { color: #1259c3; background: #eff6ff; font-weight: 600; }
        body.role-employee .sidebar .nav-link.active::before,
        body.role-admin .sidebar .nav-link.active::before { background: #1764dc; }
        body.role-admin .sidebar small.text-white-50 { color: #94a3b8 !important; }
        body.role-employee .sidebar-footer,
        body.role-admin .sidebar-footer { border-top-color: #e2e8f0; }
        body.role-employee .sidebar-profile,
        body.role-admin .sidebar-profile { color: #172033; }
        body.role-employee a.sidebar-profile:hover,
        body.role-admin a.sidebar-profile:hover { color: #1259c3; }
        body.role-employee .sidebar-profile-avatar,
        body.role-admin .sidebar-profile-avatar { color: #1764dc; background: #eff6ff; border-color: #dbeafe; }
        body.role-employee .sidebar-profile-role,
        body.role-admin .sidebar-profile-role { color: #64748b; }
        body.role-employee .logout-button,
        body.role-admin .logout-button {
            color: #dc2626;
            background: rgba(239, 68, 68, .08);
            border-color: rgba(239, 68, 68, .32);
        }
        body.role-employee .logout-button:hover,
        body.role-employee .logout-button:focus-visible,
        body.role-admin .logout-button:hover,
        body.role-admin .logout-button:focus-visible {
            color: #b91c1c;
            background: rgba(239, 68, 68, .16);
            border-color: rgba(220, 38, 38, .5);
        }
        body.employee-dashboard-page,
        body.employee-dashboard-page .main-content,
        body.admin-dashboard-page,
        body.admin-dashboard-page .main-content { background: #f8fafc; }
        body.employee-dashboard-page .topbar { background: rgba(255, 255, 255, .96); border-bottom-color: #e2e8f0; }
        body.admin-dashboard-page .topbar { background: rgba(255, 255, 255, .96); border-bottom-color: #e2e8f0; }
        @media (max-width: 767.98px) {
            .sidebar {
                position: fixed;
                inset: 0 auto 0 0;
                min-height: 100vh;
                margin-left: 0 !important;
                transform: translateX(-100%);
            }
            .app-shell.sidebar-open .sidebar { transform: translateX(0); }
            .sidebar-overlay {
                position: fixed;
                inset: 0;
                z-index: 1030;
                background: rgba(15, 23, 42, .5);
            }
            .app-shell.sidebar-open .sidebar-overlay { display: block; }
            .topbar { padding-left: 1rem !important; padding-right: 1rem !important; }
            .content-wrapper { padding: 1rem !important; }
            .topbar-user-role { display: none; }
            .card-body { padding: 1.1rem !important; }
            .table > :not(caption) > * > * { padding: .78rem .65rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition: none !important; animation: none !important; }
        }
    </style>
</head>
<body class="role-{{ auth()->user()->role }} @yield('body-class')">
@php
    $layoutUser = auth()->user();
    $layoutEmployee = $layoutUser->role === 'employee' ? $layoutUser->employee : null;
    $layoutAvatarPath = $layoutEmployee ? $layoutEmployee->avatar_path : $layoutUser->avatar_path;
    $layoutAvatarRoute = $layoutEmployee ? 'employee.profile.avatar' : 'admin.profile.avatar';
    $layoutAvatarVersion = $layoutEmployee ? optional($layoutEmployee->updated_at)->timestamp : optional($layoutUser->updated_at)->timestamp;
@endphp
<div class="d-flex app-shell min-vh-100">
    <aside class="sidebar p-3 d-flex flex-column" id="sidebar">
        <a class="brand text-decoration-none d-flex align-items-center gap-3 fw-bold px-2 py-3" href="{{ auth()->user()->role === 'admin' ? route('admin.dashboard') : route('employee.dashboard') }}">
            <span class="brand-icon"><x-icon name="calendar" size="28" /></span>
            <span class="brand-copy"><span class="brand-name">SiPrega</span><small class="brand-subtitle">Sistem Presensi Pegawai</small></span>
        </a>

        <nav class="sidebar-nav nav nav-pills flex-column gap-1 mt-3">
            @if (auth()->user()->role === 'admin')
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><x-icon name="grid" />Dashboard</a>
                <small class="d-block fw-normal text-white-50 fs-8 mt-1">Kelola Data Master</small>
                <a class="nav-link {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}" href="{{ route('admin.employees.index') }}"><x-icon name="users" />Data Pegawai</a>
                <a class="nav-link {{ request()->routeIs('admin.locations.*') ? 'active' : '' }}" href="{{ route('admin.locations.index') }}"><x-icon name="pin" />Data Lokasi</a>
                <a class="nav-link {{ request()->routeIs('admin.attendances.*') ? 'active' : '' }}" href="{{ route('admin.attendances.index') }}"><x-icon name="clipboard" />Data Absensi</a>
                <small class="d-block fw-normal text-white-50 fs-8 mt-1">Kelola Jadwal & Shift</small>
                <a class="nav-link {{ request()->routeIs('admin.work-schedules.*') ? 'active' : '' }}" href="{{ route('admin.work-schedules.index') }}"><x-icon name="calendar" />Jadwal Kerja</a>
                <a class="nav-link {{ request()->routeIs('admin.shift-assignments.*') ? 'active' : '' }}" href="{{ route('admin.shift-assignments.index') }}"><x-icon name="clock" />Atur Shift</a>
                <small class="d-block fw-normal text-white-50 fs-8 mt-1">Aktivitas & Laporan</small>
                <a class="nav-link {{ request()->routeIs('admin.leave-requests.*') ? 'active' : '' }}" href="{{ route('admin.leave-requests.index') }}"><x-icon name="file" />Pengajuan Izin/Sakit</a>
                <a class="nav-link {{ request()->routeIs('admin.attendance-recap.*') ? 'active' : '' }}" href="{{ route('admin.attendance-recap.index') }}"><x-icon name="history" />Rekap Absensi</a>
            @else
                <a class="nav-link {{ request()->routeIs('employee.dashboard') ? 'active' : '' }}" href="{{ route('employee.dashboard') }}"><x-icon name="grid" />Dashboard</a>
                <a class="nav-link {{ request()->routeIs('employee.attendances.*') ? 'active' : '' }}" href="{{ route('employee.attendances.index') }}"><x-icon name="clipboard" />Riwayat Absensi</a>
                <a class="nav-link {{ request()->routeIs('employee.leave-requests.*') ? 'active' : '' }}" href="{{ route('employee.leave-requests.index') }}"><x-icon name="file" />Izin / Sakit</a>
                <a class="nav-link {{ request()->routeIs('employee.profile.*') ? 'active' : '' }}" href="{{ route('employee.profile.show') }}"><x-icon name="user" />Profil</a>
            @endif
        </nav>

        <div class="sidebar-footer mt-auto pt-3">
            @if (auth()->user()->role === 'admin')
                <a class="sidebar-profile d-flex align-items-center gap-3 px-2 pb-3 text-decoration-none" href="{{ route('admin.profile.edit') }}" aria-label="Buka profil admin">
            @else
                <a class="sidebar-profile d-flex align-items-center gap-3 px-2 pb-3 text-decoration-none" href="{{ route('employee.profile.show') }}" aria-label="Buka profil pegawai">
            @endif
                <span class="sidebar-profile-avatar">
                    @if ($layoutAvatarPath)
                        <img src="{{ route($layoutAvatarRoute, ['v' => $layoutAvatarVersion]) }}" width="40" height="40" alt="" decoding="async">
                    @else
                        <x-icon name="user" size="21" />
                    @endif
                </span>
                <div class="min-w-0">
                    <div class="sidebar-profile-name fw-semibold">{{ auth()->user()->name }}</div>
                    <div class="sidebar-profile-role text-capitalize">{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Employee' }}</div>
                </div>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-outline-light logout-button w-100" type="submit"><x-icon name="logout" />Keluar</button>
            </form>
        </div>
    </aside>
    <button class="sidebar-overlay border-0" type="button" aria-label="Tutup menu"></button>

    <main class="main-content flex-grow-1">
        <header class="topbar px-4 py-3 d-flex justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <button class="sidebar-toggle flex-shrink-0" id="sidebarToggle" type="button" aria-label="Buka atau tutup menu" aria-controls="sidebar" aria-expanded="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div>
                    <h1 class="h5 mb-0">@yield('page-title', 'Dashboard')</h1>
                    <small class="text-muted">@yield('page-subtitle')</small>
                </div>
            </div>
            @if (auth()->user()->role === 'admin')
                <a class="d-flex align-items-center gap-3 text-dark text-decoration-none" href="{{ route('admin.profile.edit') }}" aria-label="Buka profil admin">
            @else
                <a class="d-flex align-items-center gap-3 text-dark text-decoration-none" href="{{ route('employee.profile.show') }}" aria-label="Buka profil pegawai">
            @endif
                <div class="text-end d-none d-sm-block">
                    <div class="fw-semibold">{{ auth()->user()->name }}</div>
                    <small class="text-muted text-capitalize topbar-user-role">{{ auth()->user()->role }}</small>
                </div>
                <span class="profile-avatar">
                    @if ($layoutAvatarPath)
                        <img src="{{ route($layoutAvatarRoute, ['v' => $layoutAvatarVersion]) }}" width="44" height="44" alt="" decoding="async">
                    @else
                        <x-icon name="user" size="24" />
                    @endif
                </span>
            </a>
        </header>

        <div class="content-wrapper p-4">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            @endif
            @yield('content')
        </div>
    </main>
</div>
<script src="{{ asset('vendor/bootstrap/5.3.3/bootstrap.bundle.min.js') }}" defer></script>
<script>
    (() => {
        const shell = document.querySelector('.app-shell');
        const toggle = document.getElementById('sidebarToggle');
        const overlay = document.querySelector('.sidebar-overlay');
        const mobile = () => window.matchMedia('(max-width: 767.98px)').matches;

        const updateState = () => {
            const isOpen = mobile()
                ? shell.classList.contains('sidebar-open')
                : !shell.classList.contains('sidebar-collapsed');

            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        };

        toggle.addEventListener('click', () => {
            if (mobile()) {
                shell.classList.toggle('sidebar-open');
            } else {
                shell.classList.toggle('sidebar-collapsed');
            }
            updateState();
        });

        overlay.addEventListener('click', () => {
            shell.classList.remove('sidebar-open');
            updateState();
        });

        window.addEventListener('resize', () => {
            if (!mobile()) shell.classList.remove('sidebar-open');
            updateState();
        });

        updateState();

    })();
</script>
</body>
</html>
