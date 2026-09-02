@extends('layouts.app')
@section('title', 'Tambah Lokasi')
@section('page-title', 'Tambah Lokasi')
@section('page-subtitle', 'Tentukan titik dan radius absensi baru')
@section('content')
    <div class="card stat-card"><div class="card-body p-4">
        <form method="POST" action="{{ route('admin.locations.store') }}">@csrf @include('admin.locations._form')</form>
    </div></div>
@endsection
