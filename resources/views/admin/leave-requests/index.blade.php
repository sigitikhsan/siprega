@extends('layouts.app')

@section('title', 'Pengajuan Izin/Sakit')
@section('page-title', 'Pengajuan Izin/Sakit')
@section('page-subtitle', 'Tinjau pengajuan ketidakhadiran pegawai')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><span class="badge bg-warning text-dark fs-6">{{ $pendingCount }} menunggu</span></div>
    </div>

    <div class="card stat-card">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('admin.leave-requests.index') }}" class="row g-3 mb-4">
                <div class="col-md-6 col-xl-3"><label class="form-label">Cari pegawai</label><input class="form-control" name="search" value="{{ $search }}" placeholder="Nama atau nomor pegawai"></div>
                <div class="col-md-6 col-xl-3">
                    <label class="form-label">Pegawai</label>
                    <select class="form-select" name="employee_id"><option value="">Semua pegawai</option>@foreach ($employees as $employee)<option value="{{ $employee->id }}" {{ (string) ($filters['employee_id'] ?? '') === (string) $employee->id ? 'selected' : '' }}>{{ $employee->employee_number }} — {{ $employee->user->name }}</option>@endforeach</select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label">Jenis</label>
                    <select class="form-select" name="type"><option value="">Semua jenis</option><option value="permission" {{ ($filters['type'] ?? '') === 'permission' ? 'selected' : '' }}>Izin</option><option value="sick" {{ ($filters['type'] ?? '') === 'sick' ? 'selected' : '' }}>Sakit</option></select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status"><option value="">Semua status</option><option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Menunggu</option><option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Disetujui</option><option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Ditolak</option></select>
                </div>
                <div class="col-md-6 col-xl-2"><label class="form-label">Tanggal mulai</label><input class="form-control" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></div>
                <div class="col-md-6 col-xl-2"><label class="form-label">Tanggal akhir</label><input class="form-control" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></div>
                <div class="col-md-6 col-xl-3 d-flex align-items-end gap-2"><button class="btn btn-primary flex-grow-1" type="submit">Terapkan</button><a class="btn btn-light" href="{{ route('admin.leave-requests.index') }}">Reset</a></div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Diajukan</th><th>Pegawai</th><th>Jenis</th><th>Mulai</th><th>Durasi</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                    @forelse ($leaveRequests as $leaveRequest)
                        <tr>
                            <td>{{ $leaveRequest->created_at->format('d/m/Y H:i') }}</td>
                            <td><div class="fw-semibold">{{ $leaveRequest->employee->user->name }}</div><small class="text-muted">{{ $leaveRequest->employee->employee_number }}</small></td>
                            <td>{{ $leaveRequest->type === 'sick' ? 'Sakit' : 'Izin' }}</td>
                            <td>{{ $leaveRequest->start_date->format('d/m/Y') }}</td>
                            <td>{{ $leaveRequest->duration }} hari</td>
                            <td>
                                @if ($leaveRequest->status === 'approved')<span class="badge bg-success">Disetujui</span>
                                @elseif ($leaveRequest->status === 'rejected')<span class="badge bg-danger">Ditolak</span>
                                @else<span class="badge bg-warning text-dark">Menunggu</span>@endif
                            </td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.leave-requests.show', $leaveRequest) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">Belum ada pengajuan yang sesuai.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $leaveRequests->links() }}</div>
        </div>
    </div>
@endsection
