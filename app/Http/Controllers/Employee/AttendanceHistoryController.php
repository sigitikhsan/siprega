<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
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
            ->when($filters['date_from'] ?? null, function ($query, $date) { $query->whereDate('attendance_date', '>=', $date); })
            ->when($filters['date_to'] ?? null, function ($query, $date) { $query->whereDate('attendance_date', '<=', $date); })
            ->when($filters['status'] ?? null, function ($query, $status) { $query->where('check_in_status', $status); })
            ->when(($filters['completion'] ?? null) === 'complete', function ($query) { $query->whereNotNull('check_out'); })
            ->when(($filters['completion'] ?? null) === 'incomplete', function ($query) { $query->whereNull('check_out'); })
            ->orderByDesc('attendance_date')->orderByDesc('check_in')->paginate(12)->appends($filters);

        return view('employee.attendances.index', compact('attendances', 'filters'));
    }

    public function show(Request $request, Attendance $attendance)
    {
        $employee = $request->user()->employee()->firstOrFail();
        abort_unless($attendance->employee_id === $employee->id, 403);
        $attendance->load(['location', 'workSchedule']);

        return view('employee.attendances.show', compact('attendance'));
    }
}
