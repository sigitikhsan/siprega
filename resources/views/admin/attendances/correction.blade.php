@extends('layouts.app')

@section('title', 'Koreksi Absensi')
@section('page-title', 'Koreksi Data Absensi')
@section('page-subtitle', $attendance->employee->user->name.' · '.$attendance->attendance_date->format('d/m/Y'))

@section('content')
    <div class="card rounded-4 mx-auto" style="max-width: 820px">
        <div class="card-body p-4 p-md-5">
            <div class="alert alert-warning border mb-4">
                Setiap koreksi akan dicatat permanen beserta nilai sebelumnya, alasan, waktu, dan admin yang melakukan perubahan.
            </div>

            @if ($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <div class="row g-3 mb-4">
                <div class="col-md-6"><div class="small text-muted">Pegawai</div><div class="fw-semibold">{{ $attendance->employee->user->name }}</div></div>
                <div class="col-md-6"><div class="small text-muted">Nomor pegawai</div><div class="fw-semibold">{{ $attendance->employee->employee_number }}</div></div>
                <div class="col-md-6"><div class="small text-muted">Jadwal</div><div class="fw-semibold">{{ optional($attendance->workSchedule)->name ?: (optional(optional($attendance->employee)->workSchedule)->name ?: '-') }}</div></div>
                <div class="col-md-6"><div class="small text-muted">Lokasi</div><div class="fw-semibold">{{ optional($attendance->location)->name ?: '-' }}</div></div>
            </div>

            <form method="POST" action="{{ route('admin.attendances.correction.update', $attendance) }}">
                @csrf
                @method('PATCH')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="check_in">Waktu masuk</label>
                        <input class="form-control" id="check_in" type="datetime-local" name="check_in" value="{{ old('check_in', $attendance->check_in ? $attendance->check_in->format('Y-m-d\TH:i') : $attendance->attendance_date->format('Y-m-d').'T00:00') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="check_out">Waktu pulang</label>
                        <input class="form-control" id="check_out" type="datetime-local" name="check_out" value="{{ old('check_out', $attendance->check_out ? $attendance->check_out->format('Y-m-d\TH:i') : '') }}">
                        <div class="form-text">Kosongkan jika pegawai belum melakukan absen pulang.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="reason">Alasan koreksi</label>
                        <textarea class="form-control" id="reason" name="reason" rows="4" minlength="5" maxlength="1000" required placeholder="Jelaskan alasan perubahan data absensi">{{ old('reason') }}</textarea>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a class="btn btn-light" href="{{ route('admin.attendances.show', $attendance) }}">Batal</a>
                    <button class="btn btn-primary" type="submit" onclick="return confirm('Simpan koreksi data absensi ini?')">Simpan Koreksi</button>
                </div>
            </form>
        </div>
    </div>
@endsection
