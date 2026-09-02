@extends('layouts.app')
@php($isRecap = request()->routeIs('admin.attendance-recap.*'))

@section('title', $isRecap ? 'Rekap Absensi' : 'Data Absensi')
@section('page-title', $isRecap ? 'Rekap Absensi' : 'Data Absensi')
@section('page-subtitle', $isRecap ? 'Filter dan ekspor laporan kehadiran pegawai' : 'Pantau catatan kehadiran seluruh pegawai')

@section('content')
    <div class="card stat-card">
        <div class="card-body p-4">
            <form method="GET" action="{{ $isRecap ? route('admin.attendance-recap.index') : route('admin.attendances.index') }}" class="row g-3 mb-4">
                @unless ($isRecap)
                <div class="col-md-6 col-xl-3">
                    <label class="form-label">Cari pegawai</label>
                    <input class="form-control" name="search" value="{{ $search }}" placeholder="Nama atau nomor pegawai">
                </div>
                
                <div class="col-md-6 col-xl-3">
                    <label class="form-label">Status masuk</label>
                    <select class="form-select" name="check_in_status">
                        <option value="">Semua status</option>
                        <option value="present" {{ ($filters['check_in_status'] ?? '') === 'present' ? 'selected' : '' }}>Tepat waktu</option>
                        <option value="late" {{ ($filters['check_in_status'] ?? '') === 'late' ? 'selected' : '' }}>Terlambat</option>
                    </select>
                </div>
                @endunless
                <div class="col-md-6 col-xl-3">
                    <label class="form-label">Tanggal mulai</label>
                    <input class="form-control" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-md-6 col-xl-3">
                    <label class="form-label">Tanggal akhir</label>
                    <input class="form-control" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                @unless ($isRecap)
                <div class="col-md-6 col-xl-3">
                    <label class="form-label">Kelengkapan</label>
                    <select class="form-select" name="completion">
                        <option value="">Semua catatan</option>
                        <option value="complete" {{ ($filters['completion'] ?? '') === 'complete' ? 'selected' : '' }}>Sudah pulang</option>
                        <option value="incomplete" {{ ($filters['completion'] ?? '') === 'incomplete' ? 'selected' : '' }}>Belum pulang</option>
                    </select>
                </div>
                <div class="col-md-6 col-xl-3">
                    <label class="form-label">Pegawai</label>
                    <select class="form-select" name="employee_id">
                        <option value="">Semua pegawai</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" {{ (string) ($filters['employee_id'] ?? '') === (string) $employee->id ? 'selected' : '' }}>{{ $employee->employee_number }} · {{ $employee->user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-3">
                    <label class="form-label">Jadwal / shift</label>
                    <select class="form-select" name="work_schedule_id">
                        <option value="">Semua jadwal</option>
                        @foreach ($schedules as $schedule)
                            <option value="{{ $schedule->id }}" {{ (string) ($filters['work_schedule_id'] ?? '') === (string) $schedule->id ? 'selected' : '' }}>{{ $schedule->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endunless
                <div class="col-md-6 col-xl-3 d-flex align-items-end gap-2">
                    <button class="btn btn-primary flex-grow-1" type="submit">Terapkan</button>
                    <a class="btn btn-light" href="{{ $isRecap ? route('admin.attendance-recap.index') : route('admin.attendances.index') }}">Reset</a>
                </div>
                <div class="col-md-6 col-xl-3 d-flex align-items-end">
                    <a class="btn btn-success w-100" href="{{ $isRecap ? route('admin.attendance-recap.export', $filters) : route('admin.attendances.export', $filters) }}">Ekspor Excel</a>
                </div>
            </form>

            @if ($isRecap)
                <div class="alert alert-light border mb-4">File Excel berisi worksheet terpisah untuk semua data, status kehadiran, sistem shift, dan kelengkapan absensi.</div>
            @endif

            @if ($isRecap)
                <div class="row g-2 mb-4">
                    @foreach ([
                        ['Total data', $summary['total']],
                        ['Tepat waktu', $summary['present']],
                        ['Terlambat', $summary['late']],
                        ['Sudah pulang', $summary['complete']],
                        ['Belum pulang', $summary['incomplete']],
                    ] as [$label, $value])
                        <div class="col-6 col-md"><div class="border rounded-3 px-3 py-2 h-100"><span class="small text-muted d-block">{{ $label }}</span><strong class="fs-5">{{ $value }}</strong></div></div>
                    @endforeach
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Tanggal</th><th>Pegawai</th><th>Jadwal/Shift</th><th>Lokasi</th><th>Masuk</th><th>Pulang</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                    @forelse ($attendances as $attendance)
                        <tr>
                            <td>{{ $attendance->attendance_date->format('d/m/Y') }}</td>
                            <td><div class="fw-semibold">{{ $attendance->employee->user->name }}</div><small class="text-muted">{{ $attendance->employee->employee_number }}</small></td>
                            <td>{{ optional($attendance->workSchedule)->name ?: $attendance->employee->workSchedule->name }}</td>
                            <td>{{ $attendance->location->name }}</td>
                            <td>{{ $attendance->check_in ? $attendance->check_in->format('H:i:s') : '-' }}</td>
                            <td>{{ $attendance->check_out ? $attendance->check_out->format('H:i:s') : '-' }}</td>
                            <td>
                                @if ($attendance->check_in_status === 'late')
                                    <span class="badge bg-warning text-dark">Terlambat</span>
                                @elseif ($attendance->check_in_status === 'present')
                                    <span class="badge bg-success">Tepat waktu</span>
                                @else
                                    <span class="badge bg-secondary">Belum lengkap</span>
                                @endif
                            </td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.attendances.show', $attendance) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5">Belum ada data absensi yang sesuai.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $attendances->links() }}</div>
        </div>
    </div>
@endsection
