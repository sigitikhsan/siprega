@extends('layouts.app')

@section('title', 'Dashboard Pegawai')
@section('page-title', 'Dashboard Pegawai')
@section('page-subtitle', 'Aktivitas absensi Anda hari ini')

@section('content')
    @if ($isDayOff)
        <div class="alert alert-info mb-4">Hari ini Anda dijadwalkan <strong>libur</strong>. Tombol absensi tidak dapat digunakan.</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="welcome-alert p-3 mb-4">
        <x-icon name="info" size="24" />
        <span>Selamat datang, <strong>{{ auth()->user()->name }}</strong>. Waktu saat ini <strong id="liveClock">--:--:--</strong>.</span>
    </div>

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

    <div class="card border-0 mb-4" style="background:#f4f8ff;">
        <div class="card-body py-3 px-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <x-icon name="pin" size="21" />
                <div><strong>Status GPS</strong><div class="small text-muted" id="gpsStatus">Belum diperiksa. GPS akan diambil saat tombol absensi ditekan.</div></div>
            </div>
            <span class="badge bg-secondary align-self-start align-self-md-center" id="gpsBadge">Belum aktif</span>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100"><div class="card-body p-4">
                <div class="stat-icon mb-4"><x-icon name="clock" size="27" /></div>
                <h2 class="h6 fw-bold">Jadwal Hari Ini</h2>
                <p class="text-muted small mb-1">{{ $todayLeave && $todayLeave->status === 'approved' ? ($todayLeave->type === 'sick' ? 'Sakit' : 'Izin') : ($activeSchedule ? $activeSchedule->name : 'Tidak ada jadwal') }}</p>
                @if ($activeSchedule)
                    <p class="small mb-0">
                        {{ substr($activeSchedule->check_in_start, 0, 5) }}–{{ substr($activeSchedule->check_out_start, 0, 5) }} ·
                        {{ $activeSchedule->shift_type === 'night' ? 'Shift Malam' : ($activeSchedule->shift_type === 'day' ? 'Shift Siang' : 'Tetap') }}
                    </p>
                @endif
                @if ($todayAttendance)
                    <span class="badge availability-badge mt-3">
                        {{ $todayAttendance->check_in_status === 'late' ? 'Terlambat' : ($todayAttendance->check_out ? 'Selesai' : 'Sudah masuk') }}
                    </span>
                @endif
            </div></div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100"><div class="card-body p-4 d-flex flex-column">
                <div class="stat-icon mb-4"><x-icon name="login" size="27" /></div>
                <h2 class="h6 fw-bold">Absen Masuk</h2>
                <p class="text-muted small">{{ optional($todayAttendance)->check_in ? $todayAttendance->check_in->format('H:i:s') : 'Catat waktu dan lokasi kedatangan' }}</p>
                <form method="POST" action="{{ route('employee.attendance.check-in') }}" class="attendance-form mt-auto">
                    @csrf
                    <input type="hidden" name="latitude"><input type="hidden" name="longitude"><input type="hidden" name="accuracy"><input type="hidden" name="captured_at"><input type="hidden" name="attendance_nonce">
                    <button class="btn btn-primary w-100 locate-button" type="button" data-action-label="Absen Masuk" {{ !$activeSchedule || optional($todayAttendance)->check_in || ($todayLeave && $todayLeave->status === 'approved') ? 'disabled' : '' }}>Absen Masuk</button>
                </form>
            </div></div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100"><div class="card-body p-4 d-flex flex-column">
                <div class="stat-icon mb-4"><x-icon name="logout" size="27" /></div>
                <h2 class="h6 fw-bold">Absen Pulang</h2>
                <p class="text-muted small">{{ optional($todayAttendance)->check_out ? $todayAttendance->check_out->format('H:i:s') : 'Catat waktu dan lokasi kepulangan' }}</p>
                <form method="POST" action="{{ route('employee.attendance.check-out') }}" class="attendance-form mt-auto">
                    @csrf @method('PATCH')
                    <input type="hidden" name="latitude"><input type="hidden" name="longitude"><input type="hidden" name="accuracy"><input type="hidden" name="captured_at"><input type="hidden" name="attendance_nonce">
                    <input class="form-control form-control-sm mb-2" name="early_checkout_reason" placeholder="Alasan jika pulang lebih awal">
                    <button class="btn btn-primary w-100 locate-button" type="button" data-action-label="Absen Pulang" {{ !optional($todayAttendance)->check_in || optional($todayAttendance)->check_out ? 'disabled' : '' }}>Absen Pulang</button>
                </form>
            </div></div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100"><div class="card-body p-4">
                <div class="stat-icon mb-4"><x-icon name="file" size="27" /></div>
                <h2 class="h6 fw-bold">Izin / Sakit</h2>
                <p class="text-muted small">Ajukan dan pantau status ketidakhadiran.</p>
                <a class="btn btn-outline-primary w-100 mt-3" href="{{ route('employee.leave-requests.index') }}">Buka Pengajuan</a>
            </div></div>
        </div>
    </div>

    <div class="card stat-card mt-4"><div class="card-body p-4">
        <h2 class="h6 fw-bold mb-3">Riwayat Absensi Terbaru</h2>
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Tanggal Shift</th><th>Jadwal</th><th>Lokasi</th><th>Masuk</th><th>Pulang</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($recentAttendances as $attendance)
                <tr>
                    <td>{{ $attendance->attendance_date->format('d/m/Y') }}</td>
                    <td>{{ optional($attendance->workSchedule)->name ?: optional($employee->workSchedule)->name }}</td>
                    <td>{{ $attendance->location->name }}</td>
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

    <script>
        (() => {
            const clock = document.getElementById('liveClock');
            const updateClock = () => clock.textContent = new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).format(new Date());
            updateClock(); setInterval(updateClock, 1000);
            const gpsStatus = document.getElementById('gpsStatus');
            const gpsBadge = document.getElementById('gpsBadge');
            const challengeUrl = @json(route('employee.attendance.challenge'));
            document.querySelectorAll('.locate-button').forEach(button => button.addEventListener('click', async () => {
                if (!navigator.geolocation) {
                    gpsStatus.textContent = 'Perangkat atau browser ini tidak mendukung GPS.';
                    gpsBadge.className = 'badge bg-danger align-self-start align-self-md-center';
                    gpsBadge.textContent = 'Tidak tersedia';
                    return;
                }
                const form = button.form, label = button.dataset.actionLabel;
                button.disabled = true; button.textContent = 'Mengambil lokasi...';
                gpsStatus.textContent = 'Sedang meminta koordinat dengan akurasi tinggi...';
                gpsBadge.className = 'badge bg-warning text-dark align-self-start align-self-md-center';
                gpsBadge.textContent = 'Memproses';
                try {
                    const action = form.action.includes('check-out') ? 'check_out' : 'check_in';
                    const response = await fetch(challengeUrl + '?action=' + encodeURIComponent(action), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin', cache: 'no-store'
                    });
                    if (!response.ok) throw new Error('challenge_failed');
                    form.attendance_nonce.value = (await response.json()).token;
                } catch (error) {
                    button.disabled = false; button.textContent = label;
                    gpsStatus.textContent = 'Verifikasi keamanan gagal. Periksa koneksi lalu coba kembali.';
                    gpsBadge.className = 'badge bg-danger align-self-start align-self-md-center';
                    gpsBadge.textContent = 'Gagal';
                    return;
                }
                navigator.geolocation.getCurrentPosition(position => {
                    form.latitude.value = position.coords.latitude;
                    form.longitude.value = position.coords.longitude;
                    form.accuracy.value = position.coords.accuracy;
                    form.captured_at.value = new Date(position.timestamp).toISOString();
                    gpsStatus.textContent = 'Lokasi baru ditemukan dengan akurasi ±' + Math.round(position.coords.accuracy) + ' meter.';
                    gpsBadge.className = 'badge bg-success align-self-start align-self-md-center';
                    gpsBadge.textContent = 'GPS siap';
                    if (window.confirm('Konfirmasi ' + label + ' sekarang? Waktu dan lokasi Anda akan dicatat.')) {
                        button.textContent = 'Menyimpan...';
                        form.submit();
                    } else {
                        button.disabled = false;
                        button.textContent = label;
                    }
                }, error => {
                    button.disabled = false; button.textContent = label;
                    gpsStatus.textContent = error.code === 1 ? 'Izin lokasi ditolak. Aktifkan akses lokasi browser.' : 'Lokasi gagal diperoleh. Aktifkan GPS dan coba kembali.';
                    gpsBadge.className = 'badge bg-danger align-self-start align-self-md-center';
                    gpsBadge.textContent = 'GPS gagal';
                }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
            }));
        })();
    </script>
@endsection
