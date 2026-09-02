@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Data belum dapat disimpan.</strong>
        <ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-md-6"><label class="form-label">Nama lengkap *</label><input class="form-control" name="name" value="{{ old('name', isset($employee) ? $employee->user->name : '') }}" required></div>
    <div class="col-md-6"><label class="form-label">Nomor pegawai *</label><input class="form-control" name="employee_number" value="{{ old('employee_number', isset($employee) ? $employee->employee_number : '') }}" required></div>
    <div class="col-md-6"><label class="form-label">Username *</label><input class="form-control" name="username" value="{{ old('username', isset($employee) ? $employee->user->username : '') }}" required></div>
    <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="{{ old('email', isset($employee) ? $employee->user->email : '') }}"></div>
    <div class="col-md-6">
        <label class="form-label" for="employeePassword">Password {{ isset($employee) ? '(kosongkan jika tidak diubah)' : '*' }}</label>
        <div class="input-group">
            <input class="form-control" id="employeePassword" type="password" name="password" autocomplete="new-password" {{ isset($employee) ? '' : 'required' }}>
            <button class="btn btn-outline-secondary password-toggle px-3" type="button" data-password-target="employeePassword" aria-label="Tampilkan password" aria-pressed="false">
                <svg class="eye-open" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-closed d-none" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 4.2A10.7 10.7 0 0 1 12 4c6.5 0 10 8 10 8a17.7 17.7 0 0 1-2 3.2"/><path d="M6.6 6.6C3.4 8.5 2 12 2 12s3.5 8 10 8a9.7 9.7 0 0 0 4.1-.9"/></svg>
            </button>
        </div>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="employeePasswordConfirmation">Konfirmasi password {{ isset($employee) ? '' : '*' }}</label>
        <div class="input-group">
            <input class="form-control" id="employeePasswordConfirmation" type="password" name="password_confirmation" autocomplete="new-password" {{ isset($employee) ? '' : 'required' }}>
            <button class="btn btn-outline-secondary password-toggle px-3" type="button" data-password-target="employeePasswordConfirmation" aria-label="Tampilkan konfirmasi password" aria-pressed="false">
                <svg class="eye-open" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-closed d-none" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 4.2A10.7 10.7 0 0 1 12 4c6.5 0 10 8 10 8a17.7 17.7 0 0 1-2 3.2"/><path d="M6.6 6.6C3.4 8.5 2 12 2 12s3.5 8 10 8a9.7 9.7 0 0 0 4.1-.9"/></svg>
            </button>
        </div>
    </div>
    @php($selectedSchedule = old('work_schedule_id', isset($employee) && $employee->uses_shift_schedule ? 'shift' : (isset($employee) ? $employee->work_schedule_id : '')))
    <div class="col-md-6"><label class="form-label">Jadwal kerja *</label><select class="form-select" name="work_schedule_id" required><option value="">Pilih jadwal</option><option value="shift" {{ $selectedSchedule === 'shift' ? 'selected' : '' }}>Jadwal mengikuti shift</option>@foreach ($schedules as $schedule)<option value="{{ $schedule->id }}" {{ (string) $selectedSchedule === (string) $schedule->id ? 'selected' : '' }}>{{ $schedule->name }} — {{ $schedule->shift_type === 'night' ? 'Shift Malam' : ($schedule->shift_type === 'day' ? 'Shift Siang' : 'Tetap') }}</option>@endforeach</select><div class="form-text">Untuk petugas keamanan, jadwal harian ditentukan melalui menu Atur Shift.</div></div>
    <div class="col-md-6"><label class="form-label">Status akun *</label><select class="form-select" name="status" required><option value="active" {{ old('status', isset($employee) ? $employee->user->status : 'active') === 'active' ? 'selected' : '' }}>Aktif</option><option value="inactive" {{ old('status', isset($employee) ? $employee->user->status : 'active') === 'inactive' ? 'selected' : '' }}>Nonaktif</option></select></div>
    <div class="col-md-6"><label class="form-label">Jabatan</label><input class="form-control" name="position" value="{{ old('position', isset($employee) ? $employee->position : '') }}"></div>
    <div class="col-md-6"><label class="form-label">Perusahaan</label><input class="form-control" name="company" value="{{ old('company', isset($employee) ? $employee->company : '') }}"></div>
    <div class="col-md-6"><label class="form-label">Nomor telepon</label><input class="form-control" name="phone" value="{{ old('phone', isset($employee) ? $employee->phone : '') }}"></div>
    <div class="col-md-6"><label class="form-label">Tanggal bergabung</label><input class="form-control" type="date" name="join_date" value="{{ old('join_date', isset($employee) && $employee->join_date ? $employee->join_date->format('Y-m-d') : '') }}"></div>
</div>

<script>
    document.querySelectorAll('.password-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.dataset.passwordTarget);
            var isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';
            button.querySelector('.eye-open').classList.toggle('d-none', !isVisible);
            button.querySelector('.eye-closed').classList.toggle('d-none', isVisible);
            button.setAttribute('aria-label', isVisible ? 'Tampilkan password' : 'Sembunyikan password');
            button.setAttribute('aria-pressed', isVisible ? 'false' : 'true');
        });
    });
</script>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-light" href="{{ route('admin.employees.index') }}">Batal</a>
    <button class="btn btn-primary" type="submit">Simpan</button>
</div>
