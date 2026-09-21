@extends('layouts.app')

@section('title', 'Jadwal Kerja')
@section('page-title', 'Jadwal Kerja')
@section('page-subtitle', 'Atur jam kerja dan toleransi keterlambatan pegawai')

@section('content')
    <div class="card stat-card">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                <form class="d-flex gap-2" method="GET" action="{{ route('admin.work-schedules.index') }}">
                    <input class="form-control" type="search" name="search" value="{{ $search }}" placeholder="Cari nama jadwal...">
                    <button class="btn btn-outline-primary" type="submit">Cari</button>
                </form>
                <a class="btn btn-primary" href="{{ route('admin.work-schedules.create') }}">+ Tambah Jadwal</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Jadwal</th>
                        <th>Jenis</th><th>Absen Masuk</th>
                        <th>Toleransi</th>
                        <th>Mulai Pulang</th>
                        
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($schedules as $schedule)
                        <tr>
                            <td>{{ $schedules->firstItem() + $loop->index }}</td>
                            <td class="fw-semibold">{{ $schedule->name }}</td>
                            <td>{{ $schedule->shift_type === 'day' ? 'Shift Siang' : ($schedule->shift_type === 'night' ? 'Shift Malam' : 'Tetap') }}</td><td>{{ substr($schedule->check_in_start, 0, 5) }}–{{ substr($schedule->check_in_end, 0, 5) }}</td>
                            <td>{{ $schedule->late_tolerance }} menit</td>
                            <td>{{ substr($schedule->check_out_start, 0, 5) }}</td>
                            
                            <td><span class="badge {{ $schedule->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ $schedule->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.work-schedules.edit', $schedule) }}">Edit</a>
                                @php($isInUse = $schedule->employees_count > 0 || $schedule->future_shift_assignments_count > 0)
                                @php($hasHistory = $schedule->attendances_count > 0 || $schedule->shift_assignments_count > 0)
                                <form class="d-inline" method="POST" action="{{ route('admin.work-schedules.destroy', $schedule) }}" onsubmit="return confirm('{{ $hasHistory ? 'Jadwal memiliki histori dan akan dinonaktifkan, bukan dihapus permanen. Lanjutkan?' : 'Hapus jadwal kerja ini?' }}')">
                                    @csrf
                                    @method('DELETE')
                                <button class="btn btn-sm {{ $hasHistory ? 'btn-outline-secondary' : 'btn-outline-danger' }}" type="submit" {{ $isInUse || ($hasHistory && $schedule->status === 'inactive') ? 'disabled' : '' }} title="{{ $isInUse ? 'Masih digunakan pegawai atau shift mendatang' : ($hasHistory ? 'Histori dipertahankan; jadwal akan dinonaktifkan' : 'Hapus permanen') }}">{{ $hasHistory ? ($schedule->status === 'inactive' ? 'Dipertahankan' : 'Nonaktifkan') : 'Hapus' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-5">Belum ada jadwal kerja.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $schedules->links() }}</div>
        </div>
    </div>
@endsection
