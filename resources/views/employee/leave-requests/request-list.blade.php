@extends('layouts.app')
@section('title', 'Izin / Sakit')
@section('page-title', 'Pengajuan Izin / Sakit')
@section('page-subtitle', 'Pantau status pengajuan ketidakhadiran Anda')
@section('content')
<div class="card stat-card"><div class="card-body p-4">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
        <form class="d-flex flex-wrap gap-2" method="GET" action="{{ route('employee.leave-requests.index') }}">
            <select class="form-select" name="type" style="width:auto"><option value="">Semua jenis</option><option value="permission" {{ ($filters['type'] ?? '') === 'permission' ? 'selected' : '' }}>Izin</option><option value="sick" {{ ($filters['type'] ?? '') === 'sick' ? 'selected' : '' }}>Sakit</option></select>
            <select class="form-select" name="status" style="width:auto"><option value="">Semua status</option><option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Menunggu</option><option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Disetujui</option><option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Ditolak</option></select>
            <button class="btn btn-outline-primary" type="submit">Filter</button><a class="btn btn-light" href="{{ route('employee.leave-requests.index') }}">Reset</a>
        </form>
        <a class="btn btn-primary" href="{{ route('employee.leave-requests.create') }}">+ Buat Pengajuan</a>
    </div>
    <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Jenis</th><th>Mulai</th><th>Durasi</th><th>Status</th><th>Diajukan</th><th class="text-end">Aksi</th></tr></thead><tbody>
    @forelse($leaveRequests as $leave)
        <tr><td>{{ $leave->type === 'sick' ? 'Sakit' : 'Izin' }}</td><td>{{ $leave->start_date->format('d/m/Y') }}</td><td>{{ $leave->duration }} hari</td><td><span class="badge {{ $leave->status === 'approved' ? 'bg-success' : ($leave->status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark') }}">{{ $leave->status === 'approved' ? 'Disetujui' : ($leave->status === 'rejected' ? 'Ditolak' : 'Menunggu') }}</span></td><td>{{ $leave->created_at->format('d/m/Y H:i') }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('employee.leave-requests.show', $leave) }}">Detail</a></td></tr>
    @empty<tr><td colspan="6" class="text-center text-muted py-5">Belum ada pengajuan izin atau sakit.</td></tr>@endforelse
    </tbody></table></div><div class="mt-3">{{ $leaveRequests->links() }}</div>
</div></div>
@endsection
