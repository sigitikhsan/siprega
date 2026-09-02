<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $statistics = [
            'active_employees' => Employee::whereHas('user', function ($query) {
                $query->where('status', 'active');
            })->count(),
            'attendance_today' => Attendance::whereDate('attendance_date', $today)
                ->whereNotNull('check_in')
                ->count(),
            'late_today' => Attendance::whereDate('attendance_date', $today)
                ->where('check_in_status', 'late')
                ->count(),
            'pending_leave_requests' => LeaveRequest::where('status', 'pending')->count(),
        ];

        $recentAttendances = Attendance::with(['employee.user', 'location'])
            ->latest('check_in')
            ->limit(6)
            ->get();

        $recentLeaveRequests = LeaveRequest::with('employee.user')
            ->where('status', 'pending')
            ->latest()
            ->limit(5)
            ->get();

        $chartStart = now()->startOfDay()->subDays(6);
        $attendanceByDate = Attendance::whereDate('attendance_date', '>=', $chartStart->toDateString())
            ->whereNotNull('check_in')
            ->get(['attendance_date', 'check_in_status'])
            ->groupBy(function ($attendance) {
                return $attendance->attendance_date->toDateString();
            });

        $attendanceChart = collect(range(0, 6))->map(function ($offset) use ($chartStart, $attendanceByDate) {
            $date = $chartStart->copy()->addDays($offset);
            $items = $attendanceByDate->get($date->toDateString(), collect());

            return [
                'date' => $date->toDateString(),
                'day' => $date->locale('id')->translatedFormat('D'),
                'label' => $date->translatedFormat('d M'),
                'present' => $items->where('check_in_status', 'present')->count(),
                'late' => $items->where('check_in_status', 'late')->count(),
                'total' => $items->count(),
            ];
        });
        $chartMaximum = max(1, (int) $attendanceChart->max('total'));
        $chartTotals = [
            'present' => $attendanceChart->sum('present'),
            'late' => $attendanceChart->sum('late'),
            'total' => $attendanceChart->sum('total'),
        ];

        return view('admin.dashboard', compact(
            'statistics',
            'recentAttendances',
            'recentLeaveRequests',
            'attendanceChart',
            'chartMaximum',
            'chartTotals'
        ));
    }
}
