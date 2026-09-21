@extends('layouts.app')
@section('title', 'Riwayat Absensi')
@section('page-title', 'Riwayat Absensi')
@section('page-subtitle', 'Lihat catatan kehadiran dan shift Anda')
@section('content')
<div class="card stat-card"><div class="card-body p-4">
    <form class="row g-3 mb-4" method="GET" action="{{ route('employee.attendances.index') }}">
        <div class="col-md-6 col-xl-3"><label class="form-label">Tanggal mulai</label><input class="form-control" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></div>
        <div class="col-md-6 col-xl-3"><label class="form-label">Tanggal akhir</label><input class="form-control" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></div>
        <div class="col-md-6 col-xl-2"><label class="form-label">Status masuk</label><select class="form-select" name="status"><option value="">Semua</option><option value="present" {{ ($filters['status'] ?? '') === 'present' ? 'selected' : '' }}>Tepat waktu</option><option value="late" {{ ($filters['status'] ?? '') === 'late' ? 'selected' : '' }}>Terlambat</option></select></div>
        <div class="col-md-6 col-xl-2"><label class="form-label">Kelengkapan</label><select class="form-select" name="completion"><option value="">Semua</option><option value="complete" {{ ($filters['completion'] ?? '') === 'complete' ? 'selected' : '' }}>Sudah pulang</option><option value="incomplete" {{ ($filters['completion'] ?? '') === 'incomplete' ? 'selected' : '' }}>Belum pulang</option></select></div>
        <div class="col-xl-2 d-flex align-items-end gap-2"><button class="btn btn-primary flex-grow-1" type="submit">Filter</button><a class="btn btn-light" href="{{ route('employee.attendances.index') }}">Reset</a></div>
    </form>
    <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Tanggal Shift</th><th>Jadwal</th><th>Lokasi</th><th>Masuk</th><th>Pulang</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
    @forelse ($attendances as $attendance)
        <tr><td>{{ $attendance->attendance_date->format('d/m/Y') }}</td><td>{{ optional($attendance->workSchedule)->name ?: '-' }}</td><td>{{ optional($attendance->location)->name ?: '-' }}</td><td>{{ $attendance->check_in ? $attendance->check_in->format('H:i:s') : '-' }}</td><td>{{ $attendance->check_out ? $attendance->check_out->format('H:i:s') : '-' }}</td><td><span class="badge {{ $attendance->check_in_status === 'late' ? 'bg-warning text-dark' : 'bg-success' }}">{{ $attendance->check_in_status === 'late' ? 'Terlambat' : 'Tepat waktu' }}</span></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('employee.attendances.show', $attendance) }}">Detail</a></td></tr>
    @empty<tr><td colspan="7" class="text-center text-muted py-5">Belum ada riwayat absensi yang sesuai.</td></tr>@endforelse
    </tbody></table></div><div class="mt-3">{{ $attendances->links() }}</div>
</div></div>
@endsection
