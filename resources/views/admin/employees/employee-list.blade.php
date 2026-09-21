@extends('layouts.app')

@section('title', 'Data Pegawai')
@section('page-title', 'Data Pegawai')
@section('page-subtitle', 'Kelola profil dan akun login pegawai')

@section('content')
    <div class="card stat-card">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                <form class="d-flex gap-2" method="GET" action="{{ route('admin.employees.index') }}">
                    <input class="form-control" type="search" name="search" value="{{ $search }}" placeholder="Cari nama, NIP, username...">
                    <button class="btn btn-outline-primary" type="submit">Cari</button>
                </form>
                <a class="btn btn-primary" href="{{ route('admin.employees.create') }}">+ Tambah Pegawai</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th>No.</th>
                        <th>Pegawai</th>
                        <th>Jabatan</th>
                        <th>Jadwal</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($employees as $employee)
                        <tr>
                            <td>{{ $employee->employee_number }}</td>
                            <td>
                                <div class="fw-semibold">{{ $employee->user->name }}</div>
                                <small class="text-muted">{{ '@'.$employee->user->username }}</small>
                            </td>
                            <td>{{ $employee->position ?: '-' }}</td>
                            <td>{{ $employee->uses_shift_schedule ? 'Mengikuti shift' : (optional($employee->workSchedule)->name ?: '-') }}</td>
                            <td>
                                <span class="badge {{ $employee->user->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $employee->user->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.employees.edit', $employee) }}">Edit</a>
                                @php($hasHistory = $employee->attendances_count > 0 || $employee->leave_requests_count > 0)
                                <form class="d-inline" method="POST" action="{{ route('admin.employees.destroy', $employee) }}" onsubmit="return confirm('{{ $hasHistory ? 'Pegawai memiliki riwayat sehingga akunnya akan dinonaktifkan, bukan dihapus. Lanjutkan?' : 'Hapus pegawai ini beserta akun loginnya?' }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit" {{ $hasHistory && $employee->user->status === 'inactive' ? 'disabled' : '' }}>{{ $hasHistory ? ($employee->user->status === 'inactive' ? 'Diarsipkan' : 'Nonaktifkan') : 'Hapus' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">Belum ada data pegawai.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $employees->links() }}</div>
        </div>
    </div>
@endsection
