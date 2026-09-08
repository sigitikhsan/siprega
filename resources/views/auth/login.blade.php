<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - SiHadir</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --navy: #08275a; --navy-dark: #061d46; --blue: #1764dc; --warm-bg: #fcfbfa; --warm-surface: #fffefa; --warm-border: #eae8e2; --muted: #716f69; }
        body { min-height: 100vh; margin: 0; color: #242321; background: radial-gradient(circle at 15% 15%, #e8f1ff 0, transparent 34%), var(--warm-bg); line-height: 1.55; }
        .login-page { min-height: 100vh; display: grid; place-items: center; }
        .login-shell { width: min(100%, 1040px); min-height: 620px; margin: auto; overflow: hidden; background: var(--warm-surface); border: 1px solid var(--warm-border); border-radius: 1.25rem; box-shadow: 0 24px 65px rgba(8,39,90,.12); }
        .brand-panel { position: relative; display: flex; min-height: 620px; flex-direction: column; justify-content: space-between; padding: 3rem; overflow: hidden; color: #fff; background: linear-gradient(145deg, #0b377b 0%, var(--navy-dark) 72%); }
        .brand-panel::after { position: absolute; right: -140px; bottom: -180px; width: 420px; height: 420px; border: 1px solid rgba(255,255,255,.1); border-radius: 50%; content: ""; }
        .brand-mark { width: 62px; height: 54px; display: grid; flex: 0 0 62px; place-items: center; overflow: hidden; background: #fff; border: 1px solid rgba(255,255,255,.8); border-radius: .7rem; }
        .brand-mark img { width: 100%; height: 100%; object-fit: contain; }
        .brand-copy { max-width: 420px; }
        .brand-copy h1 { max-width: 360px; font-size: clamp(2rem, 4vw, 3.25rem); letter-spacing: -.045em; }
        .brand-copy p { max-width: 360px; color: rgba(255,255,255,.68); }
        .brand-footer { position: relative; z-index: 1; color: rgba(255,255,255,.5); font-size: .82rem; }
        .form-panel { position: relative; display: grid; min-height: 620px; place-items: center; padding: 5.5rem 3rem 3rem; background: var(--warm-surface); }
        .balmon-logo { position: absolute; top: 1.4rem; right: 1.7rem; width: 180px; height: 62px; object-fit: contain; object-position: right center; }
        .login-form { width: min(100%, 420px); }
        .login-form h2 { letter-spacing: -.035em; }
        .login-form .intro { color: var(--muted); }
        .form-label { margin-bottom: .45rem; font-size: .88rem; font-weight: 600; }
        .form-control { min-height: 48px; color: #242321; background: var(--warm-surface); border-color: var(--warm-border); border-radius: .6rem; }
        .form-control:focus { background: #fff; border-color: #8db2e8; box-shadow: 0 0 0 .2rem rgba(23,100,220,.1); }
        .password-field { position: relative; }
        .password-field .form-control { padding-right: 4.5rem; }
        .password-toggle { position: absolute; top: 50%; right: .65rem; width: 34px; height: 34px; display:grid; place-items:center; padding:0; color: #5f6470; background: transparent; border: 0; border-radius:.45rem; transform: translateY(-50%); }
        .password-toggle:hover { color: var(--blue); }
        .login-button { min-height: 48px; font-weight: 600; background: var(--navy); border-color: var(--navy); border-radius: .6rem; }
        .login-button:hover, .login-button:focus { background: #0b3476; border-color: #0b3476; }
        .login-alert { color: #8a2d2d; background: #fff6f5; border: 1px solid #efd6d2; border-radius: .6rem; font-size: .9rem; }
        .admin-contact { color: var(--blue); font-size: .9rem; font-weight: 600; text-underline-offset: .2rem; }
        .admin-contact:hover { color: var(--navy); }
        .admin-contact[aria-disabled="true"] { color: #92908b; pointer-events: none; }
        .mobile-brand { display: none; }
        @media (max-width: 767.98px) {
            .brand-panel { display: none; }
            .login-page { padding: 1rem !important; }
            .login-shell { min-height: auto; border-radius: 1rem; }
            .form-panel { min-height: auto; padding: 5.6rem 1.35rem 2rem; }
            .balmon-logo { top: 1.1rem; right: 1.35rem; width: 145px; height: 52px; }
            .mobile-brand { display: flex; }
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; transition: none !important; } }
    </style>
</head>
<body>
<main class="login-page p-3 p-lg-5">
    <div class="row g-0 login-shell">
        <section class="col-md-5 col-xl-6 brand-panel" aria-label="Tentang aplikasi">
            <div class="d-flex align-items-center gap-3">
                <div><div class="fw-bold fs-5">SiPrega</div><div class="small text-white-50">Sistem Presensi Pegawai</div></div>
            </div>
            <div class="brand-copy position-relative z-1">
                <div class="text-uppercase small fw-semibold text-white-50 mb-3">Portal internal</div>
                <h1 class="fw-semibold mb-3">Kelola kehadiran dalam satu tempat.</h1>
                <p class="mb-0">Akses data absensi, jadwal kerja, dan aktivitas harian sesuai peran akun Anda.</p>
            </div>
            <div class="brand-footer">SiPrega · Kantor Balmon SFRID Kelas I Jakarta</div>
        </section>

        <section class="col-md-7 col-xl-6 form-panel">
            <img class="balmon-logo" src="{{ asset('img/balmon.png') }}" alt="Logo Balai Monitor Jakarta">
            <div class="login-form">
                <div class="mobile-brand align-items-center gap-3 mb-5">
                    <span class="brand-mark"><img src="{{ asset('img/komdigi.png') }}" alt="Logo Kementerian Komunikasi dan Digital"></span>
                    <div><div class="fw-bold">SiHadir</div><div class="small text-muted">Sistem Absensi</div></div>
                </div>

                <header class="mb-4">
                    <h2 class="h2 fw-semibold mb-2">Masuk ke akun</h2>
                    <p class="intro mb-0">Gunakan username dan password yang telah terdaftar.</p>
                </header>

                @if ($errors->any() && !$errors->has('throttle'))
                    <div class="login-alert p-3 mb-4" role="alert">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ url('/login') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="username">Username</label>
                        <input class="form-control @error('username') is-invalid @enderror" id="username" type="text" name="username" value="{{ old('username') }}" autocomplete="username" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="password">Password</label>
                        <div class="password-field">
                            <input class="form-control" id="password" type="password" name="password" autocomplete="current-password" required>
                            <button class="password-toggle" id="passwordToggle" type="button" aria-label="Tampilkan password" aria-pressed="false">
                                <svg class="eye-open" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-closed d-none" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M6.6 6.6C3.4 8.5 2 12 2 12s3.5 8 10 8a9.7 9.7 0 0 0 4.1-.9"/></svg>
                            </button>
                        </div>
                        @error('throttle')
                            <div class="text-danger small mt-2" id="loginThrottleWarning" data-retry-seconds="{{ (int) session('retry_after', 300) }}" role="alert">
                                Terlalu banyak aksi, silakan coba lagi dalam <strong id="loginRetryCountdown">--:--</strong>.
                            </div>
                        @enderror
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" id="remember" type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label small" for="remember">Ingat saya</label>
                    </div>
                    <button class="btn btn-primary login-button w-100" id="loginButton" type="submit" {{ $errors->has('throttle') ? 'disabled' : '' }}>Masuk</button>
                </form>
                @php
                    $adminWhatsApp = preg_replace('/\D+/', '', config('services.whatsapp.admin_number', ''));
                    $whatsAppMessage = "Halo Admin, saya ingin mengajukan bantuan akses akun SiHadir.\n\nNama: \nPosisi: \nKendala: ";
                    $whatsAppUrl = $adminWhatsApp
                        ? 'https://wa.me/'.$adminWhatsApp.'?text='.rawurlencode($whatsAppMessage)
                        : '#';
                @endphp

                <div class="text-center mt-4">
                    <p class="small text-muted mb-2">Hubungi administrator apabila Anda tidak dapat mengakses akun.</p>
                    <a
                        class="admin-contact"
                        href="{{ $whatsAppUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        @if (!$adminWhatsApp) aria-disabled="true" title="Nomor WhatsApp admin belum dikonfigurasi" @endif>
                        Hubungi Admin
                    </a>
                </div>
            </div>
        </section>
    </div>
</main>
<script>
    (() => {
        const input = document.getElementById('password');
        const toggle = document.getElementById('passwordToggle');
        toggle.addEventListener('click', () => {
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            toggle.querySelector('.eye-open').classList.toggle('d-none', !visible);
            toggle.querySelector('.eye-closed').classList.toggle('d-none', visible);
            toggle.setAttribute('aria-pressed', visible ? 'false' : 'true');
            toggle.setAttribute('aria-label', visible ? 'Tampilkan password' : 'Sembunyikan password');
        });

        const warning = document.getElementById('loginThrottleWarning');
        const countdown = document.getElementById('loginRetryCountdown');
        const loginButton = document.getElementById('loginButton');
        if (warning && countdown && loginButton) {
            let remaining = Number(warning.dataset.retrySeconds) || 0;
            const renderCountdown = () => {
                const minutes = Math.floor(remaining / 60);
                const seconds = remaining % 60;
                countdown.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');

                if (remaining <= 0) {
                    loginButton.disabled = false;
                    warning.textContent = 'Waktu tunggu selesai. Anda dapat mencoba login kembali.';
                    clearInterval(timer);
                    return;
                }
                remaining--;
            };
            const timer = setInterval(renderCountdown, 1000);
            renderCountdown();
        }
    })();
</script>
</body>
</html>
