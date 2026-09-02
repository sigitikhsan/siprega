@extends('layouts.app')
@section('title', 'Edit Jadwal Kerja')
@section('page-title', 'Edit Jadwal Kerja')
@section('page-subtitle', 'Perbarui waktu dan status jadwal')
@section('content')
    <div class="card stat-card"><div class="card-body p-4">
        <form method="POST" action="{{ route('admin.work-schedules.update', $workSchedule) }}">@csrf @method('PUT') @include('admin.work-schedules._form')</form>
    </div></div>
@endsection
