@extends('layouts.app')
@section('title', 'Buat Pengajuan')
@section('page-title', 'Buat Pengajuan Izin / Sakit')
@section('page-subtitle', 'Lengkapi informasi ketidakhadiran Anda')
@section('content')
<div class="card stat-card"><div class="card-body p-4">
    @if($errors->any())<div class="alert alert-danger"><strong>Pengajuan belum dapat dikirim.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('employee.leave-requests.store') }}" enctype="multipart/form-data">@csrf
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Jenis pengajuan *</label><select class="form-select" name="type" required><option value="">Pilih jenis</option><option value="permission" {{ old('type') === 'permission' ? 'selected' : '' }}>Izin</option><option value="sick" {{ old('type') === 'sick' ? 'selected' : '' }}>Sakit</option></select></div>
            <div class="col-md-3"><label class="form-label">Tanggal mulai *</label><input class="form-control" type="date" name="start_date" min="{{ today()->toDateString() }}" value="{{ old('start_date') }}" required></div>
            <div class="col-md-3"><label class="form-label">Durasi (hari) *</label><input class="form-control" type="number" name="duration" min="1" max="30" value="{{ old('duration', 1) }}" required></div>
            <div class="col-12"><label class="form-label">Alasan *</label><textarea class="form-control" name="reason" rows="5" minlength="10" maxlength="2000" required>{{ old('reason') }}</textarea><div class="form-text">Jelaskan alasan minimal 10 karakter.</div></div>
            <div class="col-12"><label class="form-label">Lampiran</label><input class="form-control" type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf"><div class="form-text">JPG, PNG, atau PDF. Maksimal 5 MB. Lampiran disimpan privat.</div></div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4"><a class="btn btn-light" href="{{ route('employee.leave-requests.index') }}">Batal</a><button class="btn btn-primary" type="submit">Kirim Pengajuan</button></div>
    </form>
</div></div>
@endsection
