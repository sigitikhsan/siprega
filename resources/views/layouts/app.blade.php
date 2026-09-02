<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistem Absensi') - Kantor Balmon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 270px;
            --navy: #08275a;
            --blue: #1764dc;
            --warm-bg: #fcfbfa;
            --warm-surface: #fffefa;
            --warm-border: #eae8e2;
        }
        body {
            background: var(--warm-bg);
            color: #242321;
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
        .sidebar .brand { min-width: 0; color: #fff; letter-spacing: .1px; }
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
        }
        .sidebar-profile-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .925rem; }
        .sidebar-profile-role { color: rgba(255,255,255,.62); font-size: .78rem; }
        .app-shell.sidebar-collapsed .sidebar { margin-left: calc(var(--sidebar-width) * -1); }
        .main-content { min-width: 0; background: var(--warm-bg); }
        .topbar { min-height: 92px; background: rgba(255,254,250,.96); border-bottom: 1px solid var(--warm-border); }
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
        .profile-avatar { width: 44px; height: 44px; display: grid; place-items: center; color: #6a6965; background: var(--warm-surface); border: 1px solid var(--warm-border); border-radius: 50%; }
        .card, .stat-card { background: var(--warm-surface); border-color: var(--warm-border); }
        .stat-card { border: 1px solid var(--warm-border); border-radius: 1.35rem; box-shadow: none; }
        a.stat-card { transition: transform .18s ease, border-color .18s ease, background-color .18s ease; }
        a.stat-card:hover { transform: translateY(-2px); background: #fff; border-color: #d5d1c8; box-shadow: none; }
        .stat-card .card-body { min-height: 230px; }
        .stat-icon { width: 56px; height: 56px; display: grid; place-items: center; border: 1px solid #d6e6ff; border-radius: .85rem; color: #0d5cda; background: #eff6ff; }
        .quick-arrow { color: #0a2a5f; }
        .welcome-alert { display: flex; align-items: center; gap: 1rem; color: #16468c; background: #f7faff; border: 1px solid #d8e5f6; border-radius: .7rem; }
        .availability-badge { color: #23653b; background: #e8f6ec; }
        .logout-button { display: flex; justify-content: center; align-items: center; gap: .65rem; font-size: .925rem; }
        .logout-button:hover {color: #08275a; background-color: #cbd5e1; border-color: #ffffff;}
        .content-wrapper { max-width: 1680px; width: 100%; margin-inline: auto; }
        .content-wrapper > .card .card-body { letter-spacing: 0; }
        h1, h2, h3, h4, h5, h6 { letter-spacing: -.02em; }
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
            .stat-card .card-body { min-height: 190px; }
        }
    </style>
</head>
<body>
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
                <span class="sidebar-profile-avatar"><x-icon name="user" size="21" /></span>
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
                <span class="profile-avatar"><x-icon name="user" size="24" /></span>
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
<script src="{{ mix('js/app.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

