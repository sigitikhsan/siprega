@extends('layouts.app')

@section('title', 'Data Lokasi')
@section('page-title', 'Data Lokasi')
@section('page-subtitle', 'Kelola titik koordinat dan radius absensi')

@section('content')
    <div class="card stat-card">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                <form class="d-flex gap-2" method="GET" action="{{ route('admin.locations.index') }}">
                    <input class="form-control" type="search" name="search" value="{{ $search }}" placeholder="Cari nama lokasi...">
                    <button class="btn btn-outline-primary" type="submit">Cari</button>
                </form>
                <a class="btn btn-primary" href="{{ route('admin.locations.create') }}">+ Tambah Lokasi</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Lokasi</th>
                        <th>Koordinat</th>
                        <th>Radius</th>
                        <th>Akurasi Maks.</th>
                        <th>Absensi</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($locations as $location)
                        <tr>
                            <td>{{ $locations->firstItem() + $loop->index }}</td>
                            <td class="fw-semibold">{{ $location->name }}</td>
                            <td>
                                <a href="https://www.google.com/maps?q={{ $location->latitude }},{{ $location->longitude }}" target="_blank" rel="noopener noreferrer">
                                    {{ $location->latitude }}, {{ $location->longitude }}
                                </a>
                            </td>
                            <td>{{ number_format((float) $location->radius, 0, ',', '.') }} m</td>
                            <td>{{ number_format((float) $location->accuracy_limit, 0, ',', '.') }} m</td>
                            <td>{{ $location->attendances_count }}</td>
                            <td><span class="badge {{ $location->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ $location->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.locations.edit', $location) }}">Edit</a>
                                <form class="d-inline" method="POST" action="{{ route('admin.locations.destroy', $location) }}" onsubmit="return confirm('Hapus lokasi absensi ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit" {{ $location->attendances_count > 0 ? 'disabled' : '' }}>Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5">Belum ada lokasi absensi.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $locations->links() }}</div>
        </div>
    </div>
@endsection
