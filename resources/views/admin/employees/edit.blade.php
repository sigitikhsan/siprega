@extends('layouts.app')
@section('title', 'Edit Pegawai')
@section('page-title', 'Edit Pegawai')
@section('page-subtitle', 'Perbarui profil dan akun login pegawai')
@section('content')
    <div class="card stat-card"><div class="card-body p-4">
        <form method="POST" action="{{ route('admin.employees.update', $employee) }}">@csrf @method('PUT') @include('admin.employees._form')</form>
    </div></div>
@endsection
