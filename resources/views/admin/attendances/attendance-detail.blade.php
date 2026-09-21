@extends('layouts.app')

@section('title', 'Detail Absensi')
@section('page-title', 'Detail Absensi')
@section('page-subtitle', $attendance->employee->user->name.' · '.$attendance->attendance_date->format('d/m/Y'))

@section('content')
    <style>
        .detail-panel { background: var(--warm-surface); border: 1px solid var(--warm-border); border-radius: 1rem; }
        .detail-row { display: flex; justify-content: space-between; gap: 1rem; padding-block: .75rem; border-bottom: 1px solid var(--warm-border); }
        .detail-row:last-child { border-bottom: 0; }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <a class="btn btn-light" href="{{ route('admin.attendances.index') }}">← Kembali</a>
        <a class="btn btn-primary" href="{{ route('admin.attendances.correction.edit', $attendance) }}">Koreksi Absensi</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <section class="detail-panel p-4 h-100">
                <h2 class="h5 fw-semibold mb-3">Pegawai</h2>
                <div class="detail-row"><span class="text-muted">Nama</span><strong class="text-end">{{ $attendance->employee->user->name }}</strong></div>
                <div class="detail-row"><span class="text-muted">Nomor</span><span>{{ $attendance->employee->employee_number }}</span></div>
                <div class="detail-row"><span class="text-muted">Jabatan</span><span class="text-end">{{ $attendance->employee->position ?: '-' }}</span></div>
                <div class="detail-row"><span class="text-muted">Jadwal</span><span class="text-end">{{ optional($attendance->workSchedule)->name ?: (optional(optional($attendance->employee)->workSchedule)->name ?: '-') }}</span></div>
                <div class="detail-row"><span class="text-muted">Lokasi</span><span class="text-end">{{ optional($attendance->location)->name ?: '-' }}</span></div>
            </section>
        </div>

        <div class="col-lg-8">
            <div class="row g-4">
                @foreach ([
                    ['Absen Masuk', $attendance->check_in, $attendance->check_in_latitude, $attendance->check_in_longitude, $attendance->check_in_accuracy, $attendance->check_in_distance, $attendance->check_in_captured_at, $attendance->check_in_location_suspicious, $attendance->check_in_risk_note],
                    ['Absen Pulang', $attendance->check_out, $attendance->check_out_latitude, $attendance->check_out_longitude, $attendance->check_out_accuracy, $attendance->check_out_distance, $attendance->check_out_captured_at, $attendance->check_out_location_suspicious, $attendance->check_out_risk_note],
                ] as [$title, $time, $latitude, $longitude, $accuracy, $distance, $capturedAt, $isSuspicious, $riskNote])
                    <div class="col-md-6">
                        <section class="detail-panel p-4 h-100">
                            <h2 class="h5 fw-semibold mb-3">{{ $title }}</h2>
                            <div class="detail-row">
                                <span class="text-muted">Waktu</span>
                                <span class="text-end">
                                    <strong class="d-block">{{ $time ? $time->format('H:i:s') : '-' }}</strong>
                                    @if ($time)<small class="text-muted">{{ $time->format('d/m/Y') }}</small>@endif
                                </span>
                            </div>
                            <div class="detail-row"><span class="text-muted">Akurasi</span><span>{{ $accuracy !== null ? number_format((float) $accuracy, 2, ',', '.').' m' : '-' }}</span></div>
                            <div class="detail-row"><span class="text-muted">Jarak</span><span>{{ $distance !== null ? number_format((float) $distance, 2, ',', '.').' m' : '-' }}</span></div>
                            <div class="detail-row"><span class="text-muted">GPS diambil</span><span>{{ $capturedAt ? $capturedAt->format('H:i:s') : '-' }}</span></div>
                            <div class="detail-row"><span class="text-muted">Pemeriksaan</span>@if($time)<span class="badge {{ $isSuspicious ? 'bg-warning text-dark' : 'bg-success' }}">{{ $isSuspicious ? 'Perlu ditinjau' : 'Wajar' }}</span>@else<span class="text-muted">Belum ada data</span>@endif</div>
                            @if ($riskNote)<div class="alert alert-warning small mt-3 mb-0">{{ $riskNote }}</div>@endif
                            <div class="pt-3">
                                @if ($latitude !== null && $longitude !== null)
                                    <a href="https://www.google.com/maps?q={{ $latitude }},{{ $longitude }}" target="_blank" rel="noopener noreferrer">Lihat koordinat di Google Maps</a>
                                @else
                                    <span class="text-muted">Koordinat belum tersedia</span>
                                @endif
                            </div>
                        </section>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($attendance->late_reason || $attendance->early_checkout_reason)
            <div class="col-12">
                <section class="detail-panel p-4">
                    <h2 class="h5 fw-semibold mb-3">Keterangan</h2>
                    @if ($attendance->late_reason)<p><strong>Alasan terlambat:</strong> {{ $attendance->late_reason }}</p>@endif
                    @if ($attendance->early_checkout_reason)<p class="mb-0"><strong>Alasan pulang cepat:</strong> {{ $attendance->early_checkout_reason }}</p>@endif
                </section>
            </div>
        @endif

        @if ($attendance->corrections->isNotEmpty())
            <div class="col-12">
                <section class="detail-panel p-4">
                    <h2 class="h5 fw-semibold mb-3">Riwayat Koreksi</h2>
                    @foreach ($attendance->corrections as $correction)
                        <div class="border-bottom py-3">
                            <div class="fw-semibold">{{ $correction->correctedBy->name }}</div>
                            <div class="small text-muted mb-2">{{ $correction->created_at->format('d/m/Y H:i') }}</div>
                            <div class="small mb-1">
                                Masuk: {{ $correction->old_check_in ? $correction->old_check_in->format('d/m/Y H:i') : '-' }} → {{ $correction->new_check_in ? $correction->new_check_in->format('d/m/Y H:i') : '-' }}
                            </div>
                            <div class="small mb-2">
                                Pulang: {{ $correction->old_check_out ? $correction->old_check_out->format('d/m/Y H:i') : '-' }} → {{ $correction->new_check_out ? $correction->new_check_out->format('d/m/Y H:i') : '-' }}
                            </div>
                            <div>{{ $correction->reason }}</div>
                        </div>
                    @endforeach
                </section>
            </div>
        @endif
    </div>
@endsection
