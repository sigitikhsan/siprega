@extends('layouts.app')
@section('title', 'Tambah Jadwal Kerja')
@section('page-title', 'Tambah Jadwal Kerja')
@section('page-subtitle', 'Tentukan rentang waktu absensi pegawai')
@section('content')
    <div class="card stat-card"><div class="card-body p-4">
        <form method="POST" action="{{ route('admin.work-schedules.store') }}">@csrf @include('admin.work-schedules._form')</form>
    </div></div>
@endsection
