@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Data belum dapat disimpan.</strong>
        <ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-12">
        <label class="form-label">Nama lokasi *</label>
        <input class="form-control" name="name" value="{{ old('name', isset($location) ? $location->name : '') }}" placeholder="Contoh: Kantor Balmon" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Latitude *</label>
        <input class="form-control" type="number" step="0.00000001" min="-90" max="90" name="latitude" value="{{ old('latitude', isset($location) ? $location->latitude : '') }}" placeholder="-6.20000000" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Longitude *</label>
        <input class="form-control" type="number" step="0.00000001" min="-180" max="180" name="longitude" value="{{ old('longitude', isset($location) ? $location->longitude : '') }}" placeholder="106.81666600" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Radius absensi (meter) *</label>
        <input class="form-control" type="number" step="0.01" min="1" max="10000" name="radius" value="{{ old('radius', isset($location) ? $location->radius : 100) }}" required>
        <div class="form-text">Jarak maksimum pegawai dari titik lokasi.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Batas akurasi GPS (meter) *</label>
        <input class="form-control" type="number" step="0.01" min="1" max="1000" name="accuracy_limit" value="{{ old('accuracy_limit', isset($location) ? $location->accuracy_limit : 50) }}" required>
        <div class="form-text">Nilai akurasi GPS di atas batas ini akan ditolak.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Status *</label>
        <select class="form-select" name="status" required>
            <option value="active" {{ old('status', isset($location) ? $location->status : 'active') === 'active' ? 'selected' : '' }}>Aktif</option>
            <option value="inactive" {{ old('status', isset($location) ? $location->status : 'active') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
        </select>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a class="btn btn-light" href="{{ route('admin.locations.index') }}">Batal</a>
    <button class="btn btn-primary" type="submit">Simpan</button>
</div>
