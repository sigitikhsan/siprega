@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Data belum dapat disimpan.</strong>
        <ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label">Nama jadwal *</label>
        <input class="form-control" name="name" value="{{ old('name', isset($workSchedule) ? $workSchedule->name : '') }}" placeholder="Contoh: Shift Reguler" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Jenis jadwal *</label>
        <select class="form-select" name="shift_type" required>
            <option value="fixed" {{ old('shift_type', isset($workSchedule) ? $workSchedule->shift_type : 'fixed') === 'fixed' ? 'selected' : '' }}>Jadwal Tetap</option>
            <option value="day" {{ old('shift_type', isset($workSchedule) ? $workSchedule->shift_type : '') === 'day' ? 'selected' : '' }}>Shift Siang</option>
            <option value="night" {{ old('shift_type', isset($workSchedule) ? $workSchedule->shift_type : '') === 'night' ? 'selected' : '' }}>Shift Malam</option>
        </select>
        <div class="form-text">Shift malam boleh memiliki jam pulang pada pagi berikutnya.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Waktu mulai absen masuk *</label>
        <input class="form-control" type="time" name="check_in_start" value="{{ old('check_in_start', isset($workSchedule) ? substr($workSchedule->check_in_start, 0, 5) : '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Batas akhir absen masuk *</label>
        <input class="form-control" type="time" name="check_in_end" value="{{ old('check_in_end', isset($workSchedule) ? substr($workSchedule->check_in_end, 0, 5) : '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Toleransi keterlambatan (menit) *</label>
        <input class="form-control" type="number" min="0" max="180" name="late_tolerance" value="{{ old('late_tolerance', isset($workSchedule) ? $workSchedule->late_tolerance : 0) }}" required>
        <div class="form-text">Isi 0 jika tidak ada toleransi.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Waktu mulai absen pulang *</label>
        <input class="form-control" type="time" name="check_out_start" value="{{ old('check_out_start', isset($workSchedule) ? substr($workSchedule->check_out_start, 0, 5) : '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Status *</label>
        <select class="form-select" name="status" required>
            <option value="active" {{ old('status', isset($workSchedule) ? $workSchedule->status : 'active') === 'active' ? 'selected' : '' }}>Aktif</option>
            <option value="inactive" {{ old('status', isset($workSchedule) ? $workSchedule->status : 'active') === 'inactive' ? 'selected' : '' }} {{ ($isScheduleInUse ?? false) ? 'disabled' : '' }}>Nonaktif</option>
        </select>
        @if ($isScheduleInUse ?? false)
            <div class="form-text text-warning">Jadwal masih digunakan. Pindahkan pegawai dan penugasan shift mendatang sebelum menonaktifkannya.</div>
        @endif
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-light" href="{{ route('admin.work-schedules.index') }}">Batal</a>
    <button class="btn btn-primary" type="submit">Simpan</button>
</div>
