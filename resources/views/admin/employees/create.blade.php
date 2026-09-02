@extends('layouts.app')
@section('title', 'Tambah Pegawai')
@section('page-title', 'Tambah Pegawai')
@section('page-subtitle', 'Buat profil dan akun login pegawai baru')
@section('content')
    <div class="card stat-card"><div class="card-body p-4">
        @if ($schedules->isEmpty())
            <div class="alert alert-warning mb-0">
                Belum ada jadwal kerja aktif. Buat jadwal kerja terlebih dahulu sebelum menambahkan pegawai.
            </div>
        @else
            <form method="POST" action="{{ route('admin.employees.store') }}">@csrf @include('admin.employees._form')</form>
        @endif
    </div></div>
@endsection
