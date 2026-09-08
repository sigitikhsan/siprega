@extends('layouts.app')

@section('title', 'Dashboard Admin')
@section('page-title', 'Dashboard Admin')
@section('page-subtitle', 'Ringkasan pengelolaan sistem absensi')

@section('content')
    <style>
        .metric-card { position: relative; min-height: 112px; overflow: hidden; color: #fff; border: 0; border-radius: .45rem; box-shadow: 0 8px 22px rgba(20,39,76,.1); transition: transform .22s ease, box-shadow .22s ease, filter .22s ease; }
        .metric-card::after { position: absolute; inset: 0; background: linear-gradient(120deg, rgba(255,255,255,.12), transparent 48%); content: ''; pointer-events: none; }
        .metric-card:hover, .metric-card:focus-visible { color: #fff; transform: translateY(-4px); filter: saturate(1.06); box-shadow: 0 13px 28px rgba(20,39,76,.18); }
        .metric-card.theme-rose { background: linear-gradient(135deg, #ee4777, #f25f88); }
        .metric-card.theme-violet { background: linear-gradient(135deg, #6556b5, #8073c5); }
        .metric-card.theme-cyan { background: linear-gradient(135deg, #18a8d5, #35b8df); }
        .metric-card.theme-teal { background: linear-gradient(135deg, #21afb0, #42c1c1); }
        .metric-copy { position: relative; z-index: 1; min-width: 0; flex: 1; }
        .metric-label { color: rgba(255,255,255,.82); }
        .metric-icon { position: relative; z-index: 1; width: 72px; align-self: stretch; display: grid; flex: 0 0 72px; place-items: center; color: #fff; background: rgba(255,255,255,.16); border-left: 1px solid rgba(255,255,255,.15); }
        .metric-value { font-size: 1.8rem; line-height: 1; letter-spacing: -.04em; }
        .dashboard-panel { background: linear-gradient(145deg, #fffefa 0%, #f3f7fc 100%); border: 1px solid #e1e7ef; border-radius: 1rem; box-shadow: 0 8px 24px rgba(25,55,95,.06); }
        .panel-chart { background: linear-gradient(145deg, #fffefa 0%, #f2f7fd 100%); }
        .panel-attendance { background: linear-gradient(145deg, #fff 0%, #f3f8ff 100%); }
        .panel-leave { background: linear-gradient(145deg, #fffefa 0%, #fff7ed 100%); }
        .activity-row + .activity-row { border-top: 1px solid var(--warm-border); }
        .attendance-chart-wrap { position: relative; height: 230px; }
        .chart-mode-button { width: 30px; height: 30px; display: inline-grid; place-items: center; padding: 0; color: #6b6964; background: transparent; border: 1px solid transparent; border-radius: .5rem; }
        .chart-mode-button:hover, .chart-mode-button:focus-visible { color: #047857; background: #d1fae5; border-color: #a7f3d0; outline: none; }
        .chart-mode-button.active { color: #fff; background: #10b981; border-color: #10b981; }
        .chart-mode-button svg { width: 15px; height: 15px; }
        .chart-total-pill { background: #f6f4ef; border: 1px solid var(--warm-border); border-radius: .65rem; }
        .dashboard-action-link { color: #059669; font-weight: 600; text-underline-offset: 3px; transition: color .18s ease; }
        .dashboard-action-link:hover, .dashboard-action-link:focus-visible { color: #065f46; }
        .attendance-time { color: #6366f1; font-weight: 700; font-variant-numeric: tabular-nums; }
        @media (max-width: 575.98px) { .attendance-chart-wrap { height: 210px; } }
        @media (prefers-reduced-motion: reduce) { .metric-card { transition: none; } }
    </style>

    <div class="welcome-alert p-3 mb-4">
        <x-icon name="info" size="24" class="flex-shrink-0" />
        <span>Selamat datang, <strong>{{ auth()->user()->name }}</strong>. Berikut ringkasan aktivitas sistem hari ini.</span>
    </div>

    <a id="pendingLeaveAlert" class="alert alert-warning justify-content-between align-items-center text-decoration-none mb-4 {{ $statistics['pending_leave_requests'] > 0 ? 'd-flex' : 'd-none' }}" href="{{ route('admin.leave-requests.index', ['status' => 'pending']) }}">
            <span><strong data-live-stat="pending_leave_requests">{{ $statistics['pending_leave_requests'] }}</strong> pengajuan izin/sakit menunggu persetujuan.</span>
            <span class="fw-semibold">Tinjau →</span>
    </a>

    <div class="row g-3 mb-4">
        @foreach ([
            ['Pegawai Aktif', $statistics['active_employees'], 'users', route('admin.employees.index'), 'theme-rose'],
            ['Hadir Hari Ini', $statistics['attendance_today'], 'clipboard', route('admin.attendances.index', ['date_from' => now()->toDateString(), 'date_to' => now()->toDateString()]), 'theme-violet'],
            ['Terlambat', $statistics['late_today'], 'clock', route('admin.attendances.index', ['date_from' => now()->toDateString(), 'date_to' => now()->toDateString(), 'check_in_status' => 'late']), 'theme-cyan'],
            ['Menunggu Persetujuan', $statistics['pending_leave_requests'], 'file', route('admin.leave-requests.index', ['status' => 'pending']), 'theme-teal'],
            ] as [$label, $value, $icon, $url, $theme])
            <div class="col-sm-6 col-xl-3">
                <a class="metric-card {{ $theme }} d-flex align-items-stretch h-100 text-decoration-none" href="{{ $url }}">
                    <span class="metric-copy d-flex flex-column justify-content-center p-3"><span class="metric-label small text-uppercase fw-semibold mb-2">{{ $label }}</span><span class="metric-value d-block fw-semibold" data-live-stat="{{ ['Pegawai Aktif' => 'active_employees', 'Hadir Hari Ini' => 'attendance_today', 'Terlambat' => 'late_today', 'Menunggu Persetujuan' => 'pending_leave_requests'][$label] }}">{{ $value }}</span></span>
                    <span class="metric-icon"><x-icon :name="$icon" size="29" /></span>
                </a>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <section class="dashboard-panel panel-chart p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-3">
                    <div>
                        <h2 class="h5 fw-semibold mb-1">Tren Absensi 7 Hari</h2>
                        <p class="small text-muted mb-0">Kehadiran terbaru berdasarkan status masuk.</p>
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
                <div class="attendance-chart-wrap" aria-label="Grafik absensi tujuh hari terakhir">
                    <canvas id="attendanceChart"></canvas>
                </div>
                <div class="text-end mt-3"><a class="small dashboard-action-link" href="{{ route('admin.attendances.index', ['date_from' => $attendanceChart->first()['date'], 'date_to' => $attendanceChart->last()['date']]) }}">Lihat data periode ini →</a></div>
            </section>
            <section class="dashboard-panel panel-leave p-4 mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 fw-semibold mb-0">Pengajuan Menunggu</h2>
                    <a class="small dashboard-action-link" href="{{ route('admin.leave-requests.index') }}">Lihat semua</a>
                </div>
                <div id="recentLeaveRequests" aria-live="polite">
                @forelse ($recentLeaveRequests as $leaveRequest)
                    <a class="activity-row d-flex justify-content-between align-items-center gap-3 py-3 text-dark text-decoration-none" href="{{ route('admin.leave-requests.show', $leaveRequest) }}">
                        <span><span class="d-block fw-semibold">{{ $leaveRequest->employee->user->name }}</span><small class="text-muted">{{ $leaveRequest->type === 'sick' ? 'Sakit' : 'Izin' }} · {{ $leaveRequest->duration }} hari</small></span>
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
                <div id="recentAttendances" aria-live="polite">
                @forelse ($recentAttendances as $attendance)
                    <a class="activity-row d-flex justify-content-between align-items-center gap-3 py-3 text-dark text-decoration-none" href="{{ route('admin.attendances.show', $attendance) }}">
                        <span><span class="d-block fw-semibold">{{ $attendance->employee->user->name }}</span><small class="text-muted">{{ $attendance->location->name }}</small></span>
                        <span class="text-end"><span class="attendance-time d-block">{{ $attendance->check_in ? $attendance->check_in->format('H:i') : '-' }}</span><small class="text-muted">{{ $attendance->attendance_date->format('d/m/Y') }}</small></span>
                    </a>
                @empty
                    <p class="text-muted py-4 mb-0">Belum ada aktivitas absensi.</p>
                @endforelse
                </div>
            </section>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.Chart) return;

            const canvas = document.getElementById('attendanceChart');
            const buttons = Array.from(document.querySelectorAll('[data-chart-type]'));
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
                        animation: { duration: 350 },
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
                button.addEventListener('click', function () { renderChart(button.dataset.chartType); });
            });

            let initialType = 'bar';
            try { initialType = localStorage.getItem('adminAttendanceChartType') || 'bar'; } catch (error) {}
            renderChart(initialType);

            const liveUrl = @json(route('admin.dashboard.live'));
            const attendanceContainer = document.getElementById('recentAttendances');
            const leaveContainer = document.getElementById('recentLeaveRequests');
            const pendingAlert = document.getElementById('pendingLeaveAlert');
            let pollCount = 0;
            let polling = false;

            const textElement = function (tag, className, value) {
                const element = document.createElement(tag);
                element.className = className;
                element.textContent = value;
                return element;
            };

            const renderAttendances = function (items) {
                attendanceContainer.replaceChildren();
                if (!items.length) {
                    attendanceContainer.appendChild(textElement('p', 'text-muted py-4 mb-0', 'Belum ada aktivitas absensi.'));
                    return;
                }

                items.forEach(function (item) {
                    const link = document.createElement('a');
                    link.className = 'activity-row d-flex justify-content-between align-items-center gap-3 py-3 text-dark text-decoration-none';
                    link.href = item.url;
                    const identity = document.createElement('span');
                    identity.append(textElement('span', 'd-block fw-semibold', item.name), textElement('small', 'text-muted', item.location));
                    const moment = document.createElement('span');
                    moment.className = 'text-end';
                    moment.append(textElement('span', 'attendance-time d-block', item.time), textElement('small', 'text-muted', item.date));
                    link.append(identity, moment);
                    attendanceContainer.appendChild(link);
                });
            };

            const renderLeaveRequests = function (items) {
                leaveContainer.replaceChildren();
                if (!items.length) {
                    leaveContainer.appendChild(textElement('p', 'text-muted py-4 mb-0', 'Tidak ada pengajuan yang menunggu.'));
                    return;
                }

                items.forEach(function (item) {
                    const link = document.createElement('a');
                    link.className = 'activity-row d-flex justify-content-between align-items-center gap-3 py-3 text-dark text-decoration-none';
                    link.href = item.url;
                    const identity = document.createElement('span');
                    identity.append(textElement('span', 'd-block fw-semibold', item.name), textElement('small', 'text-muted', item.summary));
                    link.append(identity, textElement('small', 'text-muted', item.date));
                    leaveContainer.appendChild(link);
                });
            };

            const refreshDashboard = async function () {
                if (polling || document.hidden) return;
                polling = true;
                pollCount += 1;
                const includeChart = pollCount % 4 === 0;

                try {
                    const response = await fetch(liveUrl + (includeChart ? '?chart=1' : ''), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        cache: 'no-store'
                    });
                    if (!response.ok) return;
                    const data = await response.json();

                    Object.keys(data.statistics).forEach(function (key) {
                        document.querySelectorAll('[data-live-stat="' + key + '"]').forEach(function (element) {
                            element.textContent = data.statistics[key];
                        });
                    });
                    const hasPending = Number(data.statistics.pending_leave_requests) > 0;
                    pendingAlert.classList.toggle('d-none', !hasPending);
                    pendingAlert.classList.toggle('d-flex', hasPending);
                    renderAttendances(data.recent_attendances);
                    renderLeaveRequests(data.recent_leave_requests);

                    if (data.chart) {
                        labels = data.chart.labels;
                        present = data.chart.present;
                        late = data.chart.late;
                        totals = {
                            present: present.reduce(function (sum, value) { return sum + value; }, 0),
                            late: late.reduce(function (sum, value) { return sum + value; }, 0)
                        };
                        const activeButton = document.querySelector('[data-chart-type].active');
                        renderChart(activeButton ? activeButton.dataset.chartType : 'bar');
                    }
                } catch (error) {
                    // Pertahankan data terakhir jika jaringan sementara terputus.
                } finally {
                    polling = false;
                }
            };

            const pollingTimer = window.setInterval(refreshDashboard, 15000);
            document.addEventListener('visibilitychange', function () {
                if (!document.hidden) refreshDashboard();
            });
            window.addEventListener('pagehide', function () { window.clearInterval(pollingTimer); }, { once: true });
        });
    </script>
@endsection
