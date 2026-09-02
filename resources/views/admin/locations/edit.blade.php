@extends('layouts.app')
@section('title', 'Edit Lokasi')
@section('page-title', 'Edit Lokasi')
@section('page-subtitle', 'Perbarui koordinat, radius, dan status lokasi')
@section('content')
    <div class="card stat-card"><div class="card-body p-4">
        <form method="POST" action="{{ route('admin.locations.update', $location) }}">@csrf @method('PUT') @include('admin.locations._form')</form>
    </div></div>
@endsection
