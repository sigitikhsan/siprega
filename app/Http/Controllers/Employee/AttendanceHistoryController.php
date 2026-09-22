<?php

/**
 * Menampilkan histori absensi milik pegawai yang sedang login dengan filter dan pagination.
 * Query dibatasi melalui relasi User -> Employee -> Attendance agar pegawai tidak dapat membaca data milik orang lain.
 * Catatan: setiap detail attendance wajib tetap diperiksa kepemilikannya di sisi server.
 */

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceHistoryController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', 'in:present,late'],
            'completion' => ['nullable', 'in:complete,incomplete'],
        ]);
        $employee = $request->user()->employee()->firstOrFail();
        $attendances = $employee->attendances()->with(['location', 'workSchedule'])
            ->when($filters['date_from'] ?? null, function ($query, $date) { $query->where('attendance_date', '>=', Carbon::parse($date)->toDateString()); })
            ->when($filters['date_to'] ?? null, function ($query, $date) { $query->where('attendance_date', '<=', Carbon::parse($date)->toDateString()); })
            ->when($filters['status'] ?? null, function ($query, $status) { $query->where('check_in_status', $status); })
            ->when(($filters['completion'] ?? null) === 'complete', function ($query) { $query->whereNotNull('check_out'); })
            ->when(($filters['completion'] ?? null) === 'incomplete', function ($query) { $query->whereNull('check_out'); })
            ->orderByDesc('attendance_date')->orderByDesc('check_in')->orderByDesc('id')->paginate(12)->appends($filters);

        return view('employee.attendances.attendance-history', compact('attendances', 'filters'));
    }

    public function show(Request $request, Attendance $attendance)
    {
        $employee = $request->user()->employee()->firstOrFail();
        abort_unless($attendance->employee_id === $employee->id, 403);
        $attendance->load(['location', 'workSchedule']);

        return view('employee.attendances.attendance-detail', compact('attendance'));
    }
}
