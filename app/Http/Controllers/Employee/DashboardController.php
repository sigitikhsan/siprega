<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\EmployeeScheduleResolver;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, EmployeeScheduleResolver $resolver)
    {
        $employee = $request->user()->employee()->with('workSchedule')->firstOrFail();
        list($activeSchedule, $activeAssignment) = $resolver->forCurrentMoment($employee, now());
        $openAttendance = $employee->attendances()->with(['location', 'workSchedule'])
            ->whereNotNull('check_in')->whereNull('check_out')->where('check_in', '>=', now()->subDay())->latest('check_in')->first();
        $todayAttendance = $openAttendance ?: $employee->attendances()->with(['location', 'workSchedule'])
            ->whereDate('attendance_date', today())->first();
        if ($openAttendance && $openAttendance->workSchedule) {
            $activeSchedule = $openAttendance->workSchedule;
        }
        $recentAttendances = $employee->attendances()->with(['location', 'workSchedule'])
            ->latest('attendance_date')->limit(5)->get();
        $todayLeave = $employee->leaveRequests()->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', today())->latest()->get()->first(function ($leave) {
                return $leave->start_date->copy()->addDays($leave->duration - 1)->gte(today());
            });

        $isDayOff = $activeAssignment && $activeAssignment->is_day_off;

        return view('employee.dashboard', compact('employee', 'todayAttendance', 'recentAttendances', 'activeSchedule', 'activeAssignment', 'todayLeave', 'isDayOff'));
    }
}
