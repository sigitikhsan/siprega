{{--
    Dashboard pegawai menampilkan jadwal efektif, card aksi, modal pulang awal, dan riwayat absensi terbaru.
    Data keputusan berasal dari Employee\DashboardController; React ticker dan JavaScript absensi hanya menangani tampilan/interaksi.
--}}
@extends('layouts.app')

@section('title', 'Dashboard Pegawai')
@section('page-title', 'Dashboard Pegawai')
@section('page-subtitle', 'Aktivitas absensi Anda hari ini')
@section('body-class', 'employee-dashboard-page')

@section('content')
<div class="employee-dashboard" id="employeeDashboard"
     data-server-now="{{ $scheduleTicker['serverNow'] }}"
     data-timezone="{{ config('app.timezone') }}"
     data-checkout-at="{{ $checkoutAt ? $checkoutAt->toIso8601String() : '' }}"
     data-challenge-url="{{ route('employee.attendance.challenge') }}">
    <style>
        .employee-dashboard {
            --employee-clock-font: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            --employee-ink: #172033;
            --employee-muted: #64748b;
            --employee-line: #e2e8f0;
            color: var(--employee-ink);
        }
        .employee-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
            min-height: 148px;
            padding: 1.75rem 2rem;
            color: var(--employee-ink);
            background: #eff6ff;
            border: 1px solid #dbeafe;
            border-radius: 1rem;
            box-shadow: 0 10px 28px rgba(30, 64, 175, .055);
        }
        .employee-hero__eyebrow,
        .employee-mode-badge {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            width: fit-content;
            padding: .42rem .72rem;
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            color: #1259c3;
            background: rgba(255, 255, 255, .72);
            font-size: .78rem;
            font-weight: 600;
            letter-spacing: .02em;
        }
        .employee-hero h2 { font-size: clamp(1.35rem, 3vw, 1.9rem); letter-spacing: -.025em; }
        .employee-hero p { color: #526079; max-width: 700px; }
        .employee-timezone {
            flex: 0 0 auto;
            min-width: 132px;
            padding: 1rem 1.15rem;
            text-align: center;
            background: #fff;
            border: 1px solid #bfdbfe;
            border-radius: .85rem;
        }
        .employee-timezone small { display: block; color: #64748b; font-size: .67rem; letter-spacing: .14em; }
        .employee-timezone strong { display: block; margin-top: .2rem; color: #1259c3; font-size: .9rem; }
        .employee-card {
            position: relative;
            overflow: hidden;
            min-height: 188px;
            color: var(--employee-ink);
            background: #fff !important;
            border: 1px solid var(--employee-line) !important;
            border-radius: .9rem !important;
            box-shadow: 0 5px 16px rgba(15, 23, 42, .035) !important;
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
        }
        .employee-card--action.is-active {
            border-color: #cbd5e1 !important;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .075) !important;
        }
        .employee-card--action.is-complete { border-color: #bbf7d0 !important; }
        .employee-card--action:hover:not(:disabled) {
            transform: translateY(-3px);
            color: var(--employee-ink);
            background: #fff !important;
            border-color: var(--employee-line) !important;
            box-shadow: 0 14px 28px rgba(15, 23, 42, .14) !important;
        }
        .employee-card .card-body { min-height: 188px !important; padding: 1.35rem !important; }
        .employee-card--action { width: 100%; text-align: left; text-decoration: none; font: inherit; cursor: pointer; }
        .employee-card--action:focus-visible { outline: 3px solid #2563eb; outline-offset: 4px; }
        .employee-card--action:disabled { cursor: default; opacity: 1; }
        .employee-card--action.is-locked { color: #64748b; background: #f8fafc !important; border-style: dashed !important; }
        .employee-card--action[aria-busy="true"] { cursor: wait; }
        .employee-card__header { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin-bottom: .8rem; }
        .employee-card__title { display: block; color: inherit; font-size: .92rem; font-weight: 500; }
        .employee-card__status { display: inline-flex; align-items: center; gap: .35rem; padding: .26rem .55rem; color: #64748b; background: #fff; border: 1px solid #e2e8f0; border-radius: 999px; font-size: .69rem; white-space: nowrap; }
        .employee-card__status::before { width: 7px; height: 7px; flex: 0 0 7px; background: #94a3b8; border-radius: 50%; content: ''; }
        .employee-card__status--late::before { background: #f59e0b; }
        .employee-card__status--ready::before { background: #10b981; box-shadow: 0 0 0 3px #d1fae5; }
        .employee-card__time { display: block; margin: .15rem 0 1rem; color: #344054; font-family: var(--employee-clock-font); font-size: clamp(1.8rem, 3vw, 2.35rem); font-weight: 500; font-variant-numeric: tabular-nums; letter-spacing: .015em; line-height: 1.2; }
        .employee-card.is-locked .employee-card__time { color: #cbd5e1; }
        .employee-card__footer { display: flex; align-items: flex-end; justify-content: space-between; gap: .75rem; margin-top: auto; }
        .employee-card__hint { display: block; color: var(--employee-muted); font-size: .78rem; line-height: 1.45; }
        .employee-card__arrow { width: 38px; height: 38px; display: grid; flex: 0 0 38px; place-items: center; color: #172033; background: #fff; border: 1px solid #d9e0e8; border-radius: 50%; font-size: 1.05rem; transition: color .18s ease, background-color .18s ease, transform .18s ease; }
        .employee-card--action:hover:not(:disabled) .employee-card__arrow { transform: translateX(2px); }
        .employee-card__recorded { display: inline-flex; align-items: center; gap: .45rem; color: #94a3b8; }
        .employee-card__recorded::before { width: 17px; height: 17px; display: grid; place-items: center; color: #fff; background: #16a34a; border-radius: 50%; content: '✓'; font-size: .65rem; font-weight: 800; }
        .employee-card__leave-copy { max-width: 230px; margin: .15rem 0 1rem; color: #172033; font-size: 1.15rem; font-weight: 600; line-height: 1.35; }
        #checkoutModal .modal-dialog { max-width: 520px; }
        #checkoutModal .modal-content { border: 1px solid #d1fae5; border-top: 3px solid #10b981; border-radius: 1rem; }
        #checkoutModal .modal-title { font-size: 1.1rem; font-weight: 700; }
        #checkoutModal .modal-footer { gap: .5rem; }
        .checkout-modal__icon { width: 44px; height: 44px; display: grid; flex: 0 0 44px; place-items: center; color: #059669; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: .8rem; }
        .checkout-modal__eyebrow { margin-bottom: .15rem; color: #059669; font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .checkout-modal__description { margin: 0; padding: .9rem 1rem; color: #475569; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: .75rem; }
        #checkoutModal #earlyReasonGroup { margin-top: 1.1rem; }
        #checkoutModal #confirmCheckout { background: #059669; border-color: #059669; }
        #checkoutModal #confirmCheckout:hover, #checkoutModal #confirmCheckout:focus-visible { background: #047857; border-color: #047857; box-shadow: 0 7px 18px rgba(5,150,105,.2); }
        .employee-card--leave { color: var(--employee-ink); }
        a.employee-card--leave:hover { color: var(--employee-ink); }
        .employee-card--leave .text-muted { color: var(--employee-muted) !important; }
        .employee-history {
            overflow: hidden;
            background: #fff;
            border-color: var(--employee-line) !important;
            border-radius: 1rem !important;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .04) !important;
        }
        .employee-history .card-body { min-height: 0 !important; }
        .employee-history .table { --bs-table-bg: transparent; }
        .employee-history thead th { color: #64748b; background: #f8fafc; border-bottom-color: #e2e8f0; font-size: .72rem; letter-spacing: .08em; text-transform: uppercase; white-space: nowrap; }
        .employee-history tbody tr { transition: background-color .18s ease; }
        .employee-history tbody tr:hover { background: #f7faff; }
        @media (max-width: 767.98px) {
            .employee-hero { align-items: flex-start; min-height: auto; padding: 1.4rem; }
            .employee-timezone { display: none; }
            .employee-card, .employee-card .card-body { min-height: 176px !important; }
            .employee-card--action:hover:not(:disabled),
            .employee-card--action:active:not(:disabled),
            .employee-card--action:focus-visible {
                transform: none;
                box-shadow: 0 10px 22px rgba(15, 23, 42, .16) !important;
            }
            .employee-card--action:hover:not(:disabled) .employee-card__arrow,
            .employee-card--action:active:not(:disabled) .employee-card__arrow { transform: none; }
            .employee-history .card-body { padding: 1.1rem !important; }
        }
        @media (prefers-reduced-motion: reduce) {
            .employee-card, .employee-history tbody tr { transition: none !important; }
            .employee-card--action:hover:not(:disabled) { transform: none; }
        }
    </style>
    @if ($isDayOff)
        <div class="alert alert-info mb-4">Hari ini Anda dijadwalkan <strong>libur</strong>. Absen masuk tidak tersedia. @if ($openAttendance) Anda tetap dapat menyelesaikan absen pulang untuk jadwal sebelumnya. @endif</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    @if ($openAttendance && !$openAttendance->attendance_date->isToday())
        <div class="alert alert-warning mb-4">
            Anda masih memiliki absensi tanggal <strong>{{ $openAttendance->attendance_date->format('d/m/Y') }}</strong> yang belum diselesaikan.
            Lakukan absen pulang atau hubungi administrator untuk melakukan koreksi.
        </div>
    @endif

    <section class="employee-hero mb-4">
        <div>
            <div class="employee-hero__eyebrow mb-3"><x-icon name="clock" size="15" /> Presensi kerja real-time</div>
            <h2 class="fw-bold mb-2">Selamat Datang, {{ auth()->user()->name }}</h2>
            <p class="mb-0">Lakukan absensi masuk dan kepulangan langsung melalui kartu aksi di bawah ini.</p>
        </div>
        <div class="employee-timezone"><small>ZONA WAKTU</small><strong>WIB (UTC+7)</strong></div>
    </section>

    @if ($todayLeave)
        <div class="alert {{ $todayLeave->status === 'approved' ? 'alert-success' : 'alert-warning' }} mb-4">
            @if ($todayLeave->status === 'approved')
                Hari ini Anda tercatat <strong>{{ $todayLeave->type === 'sick' ? 'Sakit' : 'Izin' }}</strong>. Anda tidak perlu melakukan absen masuk.
            @else
                Pengajuan <strong>{{ $todayLeave->type === 'sick' ? 'Sakit' : 'Izin' }}</strong> untuk hari ini masih menunggu keputusan administrator. Absensi tetap tersedia sampai pengajuan disetujui.
            @endif
            <a class="alert-link ms-1" href="{{ route('employee.leave-requests.show', $todayLeave) }}">Lihat pengajuan</a>
        </div>
    @endif

    <div class="alert alert-danger mb-3" id="attendanceError" role="alert" tabindex="-1" hidden></div>
    <div class="visually-hidden" id="attendanceProgress" role="status" aria-live="polite"></div>

    <section class="card employee-schedule-card" id="employeeScheduleTicker" aria-label="Informasi jadwal kerja"
        data-schedule='@json($scheduleTicker)'>
        <div class="p-4">
            <h2 class="h6 fw-bold">{{ $scheduleTicker['scheduleName'] ?: 'Informasi Kerja' }}</h2>
            <p class="small text-muted mb-0">{{ $scheduleTicker['state'] === 'scheduled' ? 'Memuat jam dan rentang jadwal...' : 'Informasi jadwal akan ditampilkan di sini.' }}</p>
            <noscript>Aktifkan JavaScript untuk menampilkan jam real-time.</noscript>
        </div>
    </section>

    <noscript><div class="alert alert-warning mb-3">JavaScript diperlukan untuk mengambil GPS dan memproses absensi. Aktifkan JavaScript, lalu muat ulang halaman.</div></noscript>
    <div class="row g-3 employee-action-cards">
        <div class="col-md-6 col-xl-4">
            <button type="button" id="checkInCard" aria-label="Absen Masuk" aria-describedby="checkInHint" class="card stat-card employee-card employee-card--action employee-card--checkin h-100 {{ optional($todayAttendance)->check_in ? 'is-complete' : ((!$activeSchedule || $activeSchedule->status !== 'active' || $isDayOff || ($todayLeave && $todayLeave->status === 'approved')) ? 'is-locked' : 'is-active') }}"
                {{ !$activeSchedule || $activeSchedule->status !== 'active' || $isDayOff || optional($todayAttendance)->check_in || ($todayLeave && $todayLeave->status === 'approved') ? 'disabled' : '' }}>
                <span class="card-body d-flex flex-column w-100">
                    <span class="employee-card__header">
                        <span class="employee-card__title">Absen masuk</span>
                        @if (optional($todayAttendance)->check_in)
                            <span class="employee-card__status {{ $todayAttendance->check_in_status === 'late' ? 'employee-card__status--late' : 'employee-card__status--ready' }}">{{ $todayAttendance->check_in_status === 'late' ? 'Terlambat' : 'Tepat waktu' }}</span>
                        @elseif (!$activeSchedule || $activeSchedule->status !== 'active' || $isDayOff || ($todayLeave && $todayLeave->status === 'approved'))
                            <span class="employee-card__status">Terkunci</span>
                        @else
                            <span class="employee-card__status employee-card__status--ready">Siap dicatat</span>
                        @endif
                    </span>
                    <span class="employee-card__time">{{ optional($todayAttendance)->check_in ? $todayAttendance->check_in->format('H:i:s') : '--:--:--' }}</span>
                    <span class="employee-card__footer">
                        <span class="employee-card__hint {{ optional($todayAttendance)->check_in ? 'employee-card__recorded' : '' }}" id="checkInHint" data-card-hint>{{ optional($todayAttendance)->check_in ? 'Tercatat' : (($todayLeave && $todayLeave->status === 'approved') ? 'Izin / sakit disetujui' : ($isDayOff ? 'Hari ini libur' : (!$activeSchedule || $activeSchedule->status !== 'active' ? 'Belum ada jadwal aktif' : 'Klik kartu untuk masuk'))) }}</span>
                        @if (!optional($todayAttendance)->check_in && $activeSchedule && $activeSchedule->status === 'active' && !$isDayOff && !($todayLeave && $todayLeave->status === 'approved'))<span class="employee-card__arrow" aria-hidden="true">→</span>@endif
                    </span>
                </span>
            </button>
        </div>

        <div class="col-md-6 col-xl-4">
            <button type="button" id="checkOutCard" aria-label="Absen Pulang" aria-describedby="checkOutHint" aria-haspopup="dialog" aria-controls="checkoutModal" class="card stat-card employee-card employee-card--action employee-card--checkout h-100 {{ optional($todayAttendance)->check_out ? 'is-complete' : (($openAttendance && $checkoutAt) ? 'is-active' : 'is-locked') }}"
                {{ !$openAttendance || !$checkoutAt ? 'disabled' : '' }}>
                <span class="card-body d-flex flex-column w-100">
                    <span class="employee-card__header">
                        <span class="employee-card__title">Absen pulang</span>
                        @if (optional($todayAttendance)->check_out)
                            <span class="employee-card__status employee-card__status--ready">Tercatat</span>
                        @elseif ($openAttendance && $checkoutAt)
                            <span class="employee-card__status employee-card__status--ready">Siap dicatat</span>
                        @else
                            <span class="employee-card__status">Terkunci</span>
                        @endif
                    </span>
                    <span class="employee-card__time">{{ optional($todayAttendance)->check_out ? $todayAttendance->check_out->format('H:i:s') : '--:--:--' }}</span>
                    <span class="employee-card__footer">
                        <span class="employee-card__hint {{ optional($todayAttendance)->check_out ? 'employee-card__recorded' : '' }}" id="checkOutHint" data-card-hint>{{ optional($todayAttendance)->check_out ? 'Tercatat' : (!$openAttendance ? 'Absen masuk terlebih dahulu' : (!$checkoutAt ? 'Hubungi admin untuk memeriksa jadwal' : 'Klik kartu untuk pulang')) }}</span>
                        @if (!optional($todayAttendance)->check_out && $openAttendance && $checkoutAt)<span class="employee-card__arrow" aria-hidden="true">→</span>@endif
                    </span>
                </span>
            </button>
        </div>

        <div class="col-md-6 col-xl-4">
            <a href="{{ route('employee.leave-requests.index') }}" class="card stat-card employee-card employee-card--action employee-card--leave h-100"><div class="card-body d-flex flex-column">
                <div class="employee-card__header"><h2 class="employee-card__title mb-0">Izin / sakit</h2></div>
                <p class="employee-card__leave-copy">Berhalangan hadir?<br>Ajukan di sini.</p>
                <span class="employee-card__footer">
                    <span class="employee-card__hint">Ajukan dan pantau status</span>
                    <span class="employee-card__arrow" aria-hidden="true">→</span>
                </span>
            </div></a>
        </div>
    </div>

    <form method="POST" action="{{ route('employee.attendance.check-in') }}" id="checkInForm" hidden>
        @csrf
        <input type="hidden" name="latitude"><input type="hidden" name="longitude"><input type="hidden" name="accuracy"><input type="hidden" name="captured_at"><input type="hidden" name="attendance_nonce">
    </form>

    <div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalTitle" aria-describedby="checkoutModalDescription" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('employee.attendance.check-out') }}" id="checkOutForm" class="modal-content">
                @csrf @method('PATCH')
                <input type="hidden" name="latitude"><input type="hidden" name="longitude"><input type="hidden" name="accuracy"><input type="hidden" name="captured_at"><input type="hidden" name="attendance_nonce">
                <div class="modal-header align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <span class="checkout-modal__icon"><x-icon name="logout" size="22" /></span>
                        <div>
                            <div class="checkout-modal__eyebrow">Presensi pulang</div>
                            <h2 class="modal-title mb-0" id="checkoutModalTitle">Konfirmasi Absen Pulang</h2>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="checkout-modal__description" id="checkoutModalDescription">Catat waktu kepulangan Anda sekarang?</p>
                    <div id="earlyReasonGroup" hidden>
                        <label for="earlyCheckoutReason" class="form-label">Alasan pulang lebih awal <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="earlyCheckoutReason" name="early_checkout_reason" rows="3" maxlength="1000" placeholder="Tuliskan alasan Anda..." disabled>{{ old('early_checkout_reason') }}</textarea>
                    </div>
                    <div id="checkoutError" class="alert alert-danger mt-3 mb-0" role="alert" hidden></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="confirmCheckout">Ya, Pulang</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card stat-card employee-history mt-4"><div class="card-body p-4">
        <h2 class="h6 fw-bold mb-3">Riwayat Absensi Terbaru</h2>
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Tanggal Shift</th><th>Jadwal</th><th>Lokasi</th><th>Masuk</th><th>Pulang</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($recentAttendances as $attendance)
                <tr>
                    <td>{{ $attendance->attendance_date->format('d/m/Y') }}</td>
                    <td>{{ optional($attendance->workSchedule)->name ?: optional($employee->workSchedule)->name }}</td>
                    <td>{{ optional($attendance->location)->name ?: '-' }}</td>
                    <td>{{ optional($attendance->check_in)->format('H:i:s') ?: '-' }}</td>
                    <td>{{ optional($attendance->check_out)->format('H:i:s') ?: '-' }}</td>
                    <td>{{ $attendance->check_in_status === 'late' ? 'Terlambat' : 'Tepat waktu' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada riwayat absensi.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div></div>
</div>

    <script src="{{ mix('js/employee-attendance.js') }}" defer></script>
    <script src="{{ mix('js/employee-schedule.js') }}" defer></script>
@endsection
