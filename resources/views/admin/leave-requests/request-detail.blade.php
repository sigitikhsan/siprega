@extends('layouts.app')

@section('title', 'Detail Pengajuan')
@section('page-title', 'Detail Pengajuan Izin/Sakit')
@section('page-subtitle', $leaveRequest->employee->user->name)

@section('content')
    <style>
        .leave-panel { background: var(--warm-surface); border: 1px solid var(--warm-border); border-radius: 1rem; }
        .leave-row { display: flex; justify-content: space-between; gap: 1rem; padding-block: .75rem; border-bottom: 1px solid var(--warm-border); }
        .leave-row:last-child { border-bottom: 0; }
    </style>

    <div class="mb-4"><a class="btn btn-light" href="{{ route('admin.leave-requests.index') }}">← Kembali</a></div>

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-5">
            <section class="leave-panel p-4 h-100">
                <h2 class="h5 fw-semibold mb-3">Informasi Pengajuan</h2>
                <div class="leave-row"><span class="text-muted">Pegawai</span><strong class="text-end">{{ $leaveRequest->employee->user->name }}</strong></div>
                <div class="leave-row"><span class="text-muted">Nomor</span><span>{{ $leaveRequest->employee->employee_number }}</span></div>
                <div class="leave-row"><span class="text-muted">Jenis</span><span>{{ $leaveRequest->type === 'sick' ? 'Sakit' : 'Izin' }}</span></div>
                <div class="leave-row"><span class="text-muted">Mulai</span><span>{{ $leaveRequest->start_date->format('d/m/Y') }}</span></div>
                <div class="leave-row"><span class="text-muted">Selesai</span><span>{{ $leaveRequest->start_date->copy()->addDays($leaveRequest->duration - 1)->format('d/m/Y') }}</span></div>
                <div class="leave-row"><span class="text-muted">Durasi</span><span>{{ $leaveRequest->duration }} hari</span></div>
                <div class="leave-row"><span class="text-muted">Jadwal</span><span class="text-end">{{ optional(optional($leaveRequest->employee)->workSchedule)->name ?: '-' }}</span></div>
            </section>
        </div>

        <div class="col-lg-7">
            <section class="leave-panel p-4 mb-4">
                <h2 class="h5 fw-semibold mb-3">Alasan</h2>
                <p class="mb-0" style="white-space: pre-line">{{ $leaveRequest->reason }}</p>
                @if ($leaveRequest->attachment)
                    <div class="mt-3"><a class="btn btn-sm btn-outline-primary" href="{{ route('leave-requests.attachment', $leaveRequest) }}">Unduh Lampiran</a></div>
                @endif
            </section>

            @if ($leaveRequest->status === 'pending')
                <section class="leave-panel p-4">
                    <h2 class="h5 fw-semibold mb-3">Keputusan Admin</h2>
                    <form method="POST" action="{{ route('admin.leave-requests.review', $leaveRequest) }}">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <label class="form-label" for="review_note">Catatan</label>
                            <textarea class="form-control" id="review_note" name="review_note" rows="4" maxlength="1000" placeholder="Wajib diisi jika pengajuan ditolak">{{ old('review_note') }}</textarea>
                        </div>
                        <div class="d-flex flex-wrap justify-content-end gap-2">
                            <button class="btn btn-outline-danger" type="submit" name="decision" value="rejected" onclick="return confirm('Tolak pengajuan ini?')">Tolak</button>
                            <button class="btn btn-success" type="submit" name="decision" value="approved" onclick="return confirm('Setujui pengajuan ini?')">Setujui</button>
                        </div>
                    </form>
                </section>
            @else
                <section class="leave-panel p-4">
                    <h2 class="h5 fw-semibold mb-3">Hasil Peninjauan</h2>
                    <div class="leave-row"><span class="text-muted">Status</span><strong>{{ $leaveRequest->status === 'approved' ? 'Disetujui' : 'Ditolak' }}</strong></div>
                    <div class="leave-row"><span class="text-muted">Diproses oleh</span><span>{{ $leaveRequest->reviewer ? $leaveRequest->reviewer->name : '-' }}</span></div>
                    <div class="leave-row"><span class="text-muted">Waktu</span><span>{{ $leaveRequest->reviewed_at ? $leaveRequest->reviewed_at->format('d/m/Y H:i') : '-' }}</span></div>
                    <div class="pt-3"><div class="small text-muted mb-1">Catatan</div><div>{{ $leaveRequest->review_note ?: '-' }}</div></div>
                </section>
            @endif
        </div>
    </div>
@endsection
