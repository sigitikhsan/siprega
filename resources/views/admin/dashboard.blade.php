{{--
    Dashboard admin menampilkan ringkasan operasional dari Admin\DashboardController/AdminDashboardData.
    Grafik memakai bundle admin-chart.js; seluruh data diperbarui ketika halaman dimuat ulang.
--}}
@extends('layouts.app')

@section('title', 'Dashboard Admin')
@section('page-title', 'Dashboard Admin')
@section('page-subtitle', 'Ringkasan pengelolaan sistem absensi')
@section('body-class', 'admin-dashboard-page')

@section('content')
    <style>
        .metric-card { --metric-accent: #2563eb; --metric-border: #dbe3ee; position: relative; min-height: 156px; overflow: hidden; color: #0f172a; background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; box-shadow: 0 8px 24px rgba(15,23,42,.045); transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease; }
        .metric-card:hover, .metric-card:focus-visible { color: #0f172a; transform: translateY(-3px); border-color: var(--metric-border); box-shadow: 0 14px 30px rgba(15,23,42,.085); }
        .metric-card:focus-visible, .chart-mode-button:focus-visible { outline: 3px solid rgba(37,99,235,.45); outline-offset: 3px; }
        .metric-card.theme-rose { --metric-accent: #2563eb; --metric-border: #bfdbfe; }
        .metric-card.theme-violet { --metric-accent: #16a34a; --metric-border: #bbf7d0; }
        .metric-card.theme-cyan { --metric-accent: #d97706; --metric-border: #fde68a; }
        .metric-card.theme-teal { --metric-accent: #7c3aed; --metric-border: #ddd6fe; }
        .metric-copy { min-width: 0; }
        .metric-label { color: #64748b; font-size: .86rem; font-weight: 500; }
        .metric-number-line { display: flex; align-items: baseline; gap: .5rem; min-height: 42px; }
        .metric-value { color: #0f172a; font-size: 2.05rem; line-height: 1; letter-spacing: -.045em; }
        .metric-denominator { color: #94a3b8; font-size: 1rem; font-weight: 500; }
        .metric-context { margin-top: auto; color: #94a3b8; font-size: .78rem; }
        .metric-context--link { color: #2563eb; font-weight: 600; }
        .metric-progress { width: 100%; height: 6px; margin-top: auto; overflow: hidden; appearance: none; border: 0; border-radius: 999px; background: #e9edf3; }
        .metric-progress::-webkit-progress-bar { background: #e9edf3; border-radius: 999px; }
        .metric-progress::-webkit-progress-value { background: var(--metric-accent); border-radius: 999px; transition: width .3s ease; }
        .metric-progress::-moz-progress-bar { background: var(--metric-accent); border-radius: 999px; }
        .dashboard-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; box-shadow: 0 8px 24px rgba(15,23,42,.05); transition: box-shadow .22s ease, border-color .22s ease; }
        .dashboard-panel:hover { border-color: #d7e0eb; box-shadow: 0 12px 30px rgba(15,23,42,.075); }
        .activity-row + .activity-row { border-top: 1px solid #e2e8f0; }
        .attendance-chart-wrap { position: relative; height: 230px; }
        .chart-mode-button { width: 30px; height: 30px; display: inline-grid; place-items: center; padding: 0; color: #6b6964; background: transparent; border: 1px solid transparent; border-radius: .5rem; }
        .chart-mode-button:hover, .chart-mode-button:focus-visible { color: #047857; background: #d1fae5; border-color: #a7f3d0; outline: none; }
        .chart-mode-button.active { color: #fff; background: #10b981; border-color: #10b981; }
        .chart-mode-button svg { width: 15px; height: 15px; }
        .chart-total-pill { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: .65rem; }
        .dashboard-action-link { color: #059669; font-weight: 600; text-underline-offset: 3px; transition: color .18s ease; }
        .dashboard-action-link:hover, .dashboard-action-link:focus-visible { color: #065f46; }
        .attendance-time { color: #6366f1; font-weight: 700; font-variant-numeric: tabular-nums; }
        @media (max-width: 575.98px) {
            .metric-card { min-height: 142px; }
            .attendance-chart-wrap { height: 210px; }
            .dashboard-panel { padding: 1rem !important; }
        }
        @media (prefers-reduced-motion: reduce) { .metric-card, .metric-progress::-webkit-progress-value, .dashboard-panel { transition: none; } }
    </style>

    <div class="welcome-alert p-3 mb-4">
        <x-icon name="info" size="24" class="flex-shrink-0" />
        <span>Selamat datang, <strong>{{ auth()->user()->name }}</strong>. Berikut ringkasan aktivitas sistem hari ini.</span>
    </div>

    <a class="alert alert-warning justify-content-between align-items-center text-decoration-none mb-4 {{ $statistics['pending_leave_requests'] > 0 ? 'd-flex' : 'd-none' }}" href="{{ route('admin.leave-requests.index', ['status' => 'pending']) }}">
            <span><strong>{{ $statistics['pending_leave_requests'] }}</strong> pengajuan izin/sakit menunggu persetujuan.</span>
            <span class="fw-semibold">Tinjau →</span>
    </a>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <a class="metric-card theme-rose d-block h-100 text-decoration-none" href="{{ route('admin.employees.index') }}">
                <span class="metric-copy d-flex flex-column h-100 p-4">
                    <span class="metric-label mb-3">Pegawai aktif</span>
                    <span class="metric-number-line"><strong class="metric-value">{{ $statistics['active_employees'] }}</strong></span>
                    <span class="metric-context">Semua terdaftar</span>
                </span>
            </a>
        </div>
        <div class="col-sm-6 col-xl-3">
            <a class="metric-card theme-violet d-block h-100 text-decoration-none" href="{{ route('admin.attendances.index', ['date_from' => now()->toDateString(), 'date_to' => now()->toDateString()]) }}">
                <span class="metric-copy d-flex flex-column h-100 p-4">
                    <span class="metric-label mb-3">Hadir hari ini</span>
                    <span class="metric-number-line"><strong class="metric-value">{{ $statistics['attendance_today'] }}</strong><span class="metric-denominator">/ <span>{{ $statistics['active_employees'] }}</span></span></span>
                    <progress class="metric-progress" value="{{ min(max(0, $statistics['attendance_today']), max(1, $statistics['active_employees'])) }}" max="{{ max(1, $statistics['active_employees']) }}" aria-label="Proporsi kehadiran hari ini" aria-valuetext="{{ $statistics['active_employees'] > 0 ? $statistics['attendance_today'].' dari '.$statistics['active_employees'] : 'Belum ada data' }}"></progress>
                </span>
            </a>
        </div>
        <div class="col-sm-6 col-xl-3">
            <a class="metric-card theme-cyan d-block h-100 text-decoration-none" href="{{ route('admin.attendances.index', ['date_from' => now()->toDateString(), 'date_to' => now()->toDateString(), 'check_in_status' => 'late']) }}">
                <span class="metric-copy d-flex flex-column h-100 p-4">
                    <span class="metric-label mb-3">Terlambat</span>
                    <span class="metric-number-line"><strong class="metric-value">{{ $statistics['late_today'] }}</strong><span class="metric-denominator">/ <span>{{ $statistics['active_employees'] }}</span></span></span>
                    <progress class="metric-progress" value="{{ min(max(0, $statistics['late_today']), max(1, $statistics['active_employees'])) }}" max="{{ max(1, $statistics['active_employees']) }}" aria-label="Proporsi pegawai terlambat dari seluruh pegawai aktif" aria-valuetext="{{ $statistics['active_employees'] > 0 ? $statistics['late_today'].' dari '.$statistics['active_employees'] : 'Belum ada data' }}"></progress>
                </span>
            </a>
        </div>
        <div class="col-sm-6 col-xl-3">
            <a class="metric-card theme-teal d-block h-100 text-decoration-none" href="{{ route('admin.leave-requests.index', ['status' => 'pending']) }}">
                <span class="metric-copy d-flex flex-column h-100 p-4">
                    <span class="metric-label mb-3">Menunggu persetujuan</span>
                    <span class="metric-number-line"><strong class="metric-value">{{ $statistics['pending_leave_requests'] }}</strong></span>
                    <span class="metric-context metric-context--link">Tinjau pengajuan →</span>
                </span>
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <section class="dashboard-panel panel-chart p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-3">
                    <div>
                        <h2 class="h5 fw-semibold mb-1">Tren Absensi 7 Hari</h2>
                        <p class="small text-muted mb-1">Kehadiran terbaru berdasarkan status masuk.</p>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2 small">
                        <div class="d-flex" role="group" aria-label="Pilih bentuk grafik">
                            <button class="chart-mode-button active" type="button" data-chart-type="bar" title="Grafik batang" aria-label="Tampilkan grafik batang" aria-pressed="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 20V10h4v10M10 20V4h4v16M15 20v-7h4v7M3 20h18"/></svg>
                            </button>
                            <button class="chart-mode-button" type="button" data-chart-type="doughnut" title="Donut chart" aria-label="Tampilkan donut chart" aria-pressed="false">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3a9 9 0 1 0 9 9h-6a3 3 0 1 1-3-3V3Z"/><path d="M15 3.5A9 9 0 0 1 20.5 9H15V3.5Z"/></svg>
                            </button>
                            <button class="chart-mode-button" type="button" data-chart-type="line" title="Grafik garis" aria-label="Tampilkan grafik garis" aria-pressed="false">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 19 9 12l4 3 8-10"/><circle cx="3" cy="19" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="13" cy="15" r="1"/><circle cx="21" cy="5" r="1"/></svg>
                            </button>
                        </div>
                        
                    </div>
                </div>
                <div class="attendance-chart-wrap">
                    <canvas id="attendanceChart" role="img" aria-label="Grafik absensi tujuh hari terakhir, membandingkan jumlah tepat waktu dan terlambat"></canvas>
                </div>
                <div class="text-end mt-3"><a class="small dashboard-action-link" href="{{ route('admin.attendances.index', ['date_from' => $attendanceChart->first()['date'], 'date_to' => $attendanceChart->last()['date']]) }}">Lihat data periode ini →</a></div>
            </section>
            <section class="dashboard-panel panel-leave p-4 mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 fw-semibold mb-0">Pengajuan Menunggu</h2>
                    <a class="small dashboard-action-link" href="{{ route('admin.leave-requests.index') }}">Lihat semua</a>
                </div>
                <div>
                @forelse ($recentLeaveRequests as $leaveRequest)
                    <a class="activity-row d-flex justify-content-between align-items-center gap-3 py-3 text-dark text-decoration-none" href="{{ route('admin.leave-requests.show', $leaveRequest) }}">
                        <span><span class="d-block fw-semibold">{{ optional(optional($leaveRequest->employee)->user)->name ?: '-' }}</span><small class="text-muted">{{ $leaveRequest->type === 'sick' ? 'Sakit' : 'Izin' }} · {{ $leaveRequest->duration }} hari</small></span>
                        <small class="text-muted">{{ $leaveRequest->start_date->format('d/m/Y') }}</small>
                    </a>
                @empty
                    <p class="text-muted py-4 mb-0">Tidak ada pengajuan yang menunggu.</p>
                @endforelse
                </div>
            </section>
        </div>
        <div class="col-lg-4">
            <section class="dashboard-panel panel-attendance p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 fw-semibold mb-0">Absensi Terbaru</h2>
                    <a class="small dashboard-action-link" href="{{ route('admin.attendances.index') }}">Lihat semua</a>
                </div>
                <div>
                @forelse ($recentAttendances as $attendance)
                    <a class="activity-row d-flex justify-content-between align-items-center gap-3 py-3 text-dark text-decoration-none" href="{{ route('admin.attendances.show', $attendance) }}">
                        <span><span class="d-block fw-semibold">{{ optional(optional($attendance->employee)->user)->name ?: '-' }}</span><small class="text-muted">{{ optional($attendance->location)->name ?: '-' }}</small></span>
                        <span class="text-end"><span class="attendance-time d-block">{{ $attendance->check_in ? $attendance->check_in->format('H:i') : '-' }}</span><small class="text-muted">{{ $attendance->attendance_date->format('d/m/Y') }}</small></span>
                    </a>
                @empty
                    <p class="text-muted py-4 mb-0">Belum ada aktivitas absensi.</p>
                @endforelse
                </div>
            </section>
        </div>
    </div>

    <script src="{{ mix('js/admin-chart.js') }}" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const canvas = document.getElementById('attendanceChart');
            const buttons = Array.from(document.querySelectorAll('[data-chart-type]'));
            const chartAvailable = Boolean(window.Chart && canvas);
            let labels = @json($attendanceChart->pluck('label')->values());
            let present = @json($attendanceChart->pluck('present')->values());
            let late = @json($attendanceChart->pluck('late')->values());
            let totals = @json($chartTotals);
            const colors = { present: '#06b6d4', late: '#f59e0b' };
            let chart;

            const dailyDatasets = function (type) {
                const fill = type === 'line';
                return [
                    { label: 'Tepat waktu', data: present, backgroundColor: colors.present, borderColor: colors.present, borderWidth: 2, borderRadius: type === 'bar' ? 5 : 0, tension: .32, fill: fill ? 'origin' : false, pointRadius: type === 'line' ? 3 : 0 },
                    { label: 'Terlambat', data: late, backgroundColor: colors.late, borderColor: colors.late, borderWidth: 2, borderRadius: type === 'bar' ? 5 : 0, tension: .32, fill: false, pointRadius: type === 'line' ? 3 : 0 }
                ];
            };

            const renderChart = function (type) {
                if (!chartAvailable) return;
                if (!['bar', 'doughnut', 'line'].includes(type)) type = 'bar';
                if (chart) chart.destroy();

                const isDoughnut = type === 'doughnut';
                chart = new window.Chart(canvas, {
                    type: type,
                    data: isDoughnut ? {
                        labels: ['Tepat waktu', 'Terlambat'],
                        datasets: [{ data: [totals.present, totals.late], backgroundColor: [colors.present, colors.late], borderColor: '#fffefa', borderWidth: 3, hoverOffset: 5 }]
                    } : { labels: labels, datasets: dailyDatasets(type) },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: isDoughnut ? '68%' : undefined,
                        animation: { duration: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 350 },
                        plugins: {
                            legend: { display: true, position: isDoughnut ? 'right' : 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 14 } },
                            tooltip: { mode: isDoughnut ? 'nearest' : 'index', intersect: false }
                        },
                        scales: isDoughnut ? {} : {
                            x: { grid: { display: false }, stacked: type === 'bar' },
                            y: { beginAtZero: true, ticks: { precision: 0, stepSize: 1 }, grid: { color: '#eeeae3' }, stacked: type === 'bar' }
                        }
                    }
                });

                buttons.forEach(function (button) {
                    const active = button.dataset.chartType === type;
                    button.classList.toggle('active', active);
                    button.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
                try { localStorage.setItem('adminAttendanceChartType', type); } catch (error) {}
            };

            buttons.forEach(function (button) {
                button.disabled = !chartAvailable;
                button.addEventListener('click', function () { renderChart(button.dataset.chartType); });
            });

            let initialType = 'bar';
            try { initialType = localStorage.getItem('adminAttendanceChartType') || 'bar'; } catch (error) {}
            if (chartAvailable) renderChart(initialType);

        });
    </script>
@endsection
