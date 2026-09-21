@extends('layouts.app')
@section('title', 'Atur Shift Security')
@section('page-title', 'Atur Shift Security')
@section('page-subtitle', 'Tentukan Shift Siang, Shift Malam, atau Libur untuk petugas keamanan')
@section('content')
<div class="card stat-card"><div class="card-body p-4">
    <form class="row g-3 align-items-end mb-4" method="GET" action="{{ route('admin.shift-assignments.index') }}">
        <div class="col-md-4"><label class="form-label">Tanggal penugasan</label><input class="form-control" type="date" name="date" value="{{ $date }}" required></div>
        <div class="col-md-3"><button class="btn btn-outline-primary w-100" type="submit">Tampilkan</button></div>
    </form>
    @if ($shiftSchedules->isEmpty())<div class="alert alert-warning">Belum ada jadwal bertipe Shift Siang atau Shift Malam. Buat melalui menu Jadwal Kerja terlebih dahulu.</div>@endif
    <form method="POST" action="{{ route('admin.shift-assignments.store') }}">@csrf<input type="hidden" name="shift_date" value="{{ $date }}">
        <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>No.</th><th>Pegawai</th><th>Jabatan</th><th>Shift tanggal {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</th></tr></thead><tbody>
        @forelse ($employees as $employee)
            @php($assignment = $assignments->get($employee->id))
            <tr><td>{{ $loop->iteration }}</td><td><strong>{{ $employee->user->name }}</strong><small class="d-block text-muted">{{ $employee->employee_number }}</small></td><td>{{ $employee->position ?: '-' }}</td><td><select class="form-select" name="assignments[{{ $employee->id }}]"><option value="">Tidak dijadwalkan</option><option value="off" {{ $assignment && $assignment->is_day_off ? 'selected' : '' }}>Libur</option>@foreach ($shiftSchedules as $schedule)<option value="{{ $schedule->id }}" {{ $assignment && !$assignment->is_day_off && (string) $assignment->work_schedule_id === (string) $schedule->id ? 'selected' : '' }}>{{ $schedule->name }} — {{ $schedule->shift_type === 'night' ? 'Malam' : 'Siang' }}</option>@endforeach</select></td></tr>
        @empty<tr><td colspan="4" class="text-center text-muted py-5">Belum ada petugas keamanan aktif.</td></tr>@endforelse
        </tbody></table></div>
        <div class="d-flex justify-content-end mt-3"><button class="btn btn-primary" type="submit" {{ $shiftSchedules->isEmpty() ? 'disabled' : '' }}>Simpan Penugasan</button></div>
    </form>
</div></div>
@endsection
