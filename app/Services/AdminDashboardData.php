<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Cache;

class AdminDashboardData
{
    private const STATISTICS_KEY = 'admin_dashboard.statistics';
    private const ATTENDANCES_KEY = 'admin_dashboard.recent_attendances';
    private const LEAVES_KEY = 'admin_dashboard.recent_leave_requests';
    private const CHART_KEY = 'admin_dashboard.attendance_chart';

    public function statistics(): array
    {
        return Cache::remember(self::STATISTICS_KEY, 15, function () {
            $today = now()->toDateString();

            return [
                'active_employees' => Employee::whereHas('user', function ($query) {
                    $query->where('status', 'active');
                })->count(),
                'attendance_today' => Attendance::where('attendance_date', $today)->whereNotNull('check_in')->count(),
                'late_today' => Attendance::where('attendance_date', $today)->where('check_in_status', 'late')->count(),
                'pending_leave_requests' => LeaveRequest::where('status', 'pending')->count(),
            ];
        });
    }

    public function recentAttendances()
    {
        return Cache::remember(self::ATTENDANCES_KEY, 15, function () {
            return Attendance::with(['employee.user', 'location'])->latest('check_in')->limit(4)->get();
        });
    }

    public function recentLeaveRequests()
    {
        return Cache::remember(self::LEAVES_KEY, 15, function () {
            return LeaveRequest::with('employee.user')->where('status', 'pending')->latest()->limit(5)->get();
        });
    }

    public function attendanceChart()
    {
        return Cache::remember(self::CHART_KEY, 60, function () {
            $chartStart = now()->startOfDay()->subDays(6);
            $rows = Attendance::selectRaw(
                "attendance_date as date_key,
                SUM(CASE WHEN check_in_status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN check_in_status = 'late' THEN 1 ELSE 0 END) as late_count,
                COUNT(*) as total_count"
            )->whereBetween('attendance_date', [$chartStart->toDateString(), now()->toDateString()])
                ->whereNotNull('check_in')
                ->groupBy('attendance_date')
                ->get()
                ->keyBy(function ($row) {
                    return (string) $row->date_key;
                });

            return collect(range(0, 6))->map(function ($offset) use ($chartStart, $rows) {
                $date = $chartStart->copy()->addDays($offset);
                $row = $rows->get($date->toDateString());

                return [
                    'date' => $date->toDateString(),
                    'day' => $date->locale('id')->translatedFormat('D'),
                    'label' => $date->translatedFormat('d M'),
                    'present' => (int) optional($row)->present_count,
                    'late' => (int) optional($row)->late_count,
                    'total' => (int) optional($row)->total_count,
                ];
            });
        });
    }

    public function forgetAttendance(): void
    {
        Cache::forget(self::STATISTICS_KEY);
        Cache::forget(self::ATTENDANCES_KEY);
        Cache::forget(self::CHART_KEY);
    }

    public function forgetLeaveRequests(): void
    {
        Cache::forget(self::STATISTICS_KEY);
        Cache::forget(self::LEAVES_KEY);
    }

    public function forgetAll(): void
    {
        $this->forgetAttendance();
        $this->forgetLeaveRequests();
    }
}
