<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardData;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(AdminDashboardData $dashboardData)
    {
        $statistics = $dashboardData->statistics();
        $recentAttendances = $dashboardData->recentAttendances();
        $recentLeaveRequests = $dashboardData->recentLeaveRequests();
        $attendanceChart = $dashboardData->attendanceChart();
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

    public function live(Request $request, AdminDashboardData $dashboardData)
    {
        $payload = [
            'statistics' => $dashboardData->statistics(),
            'recent_attendances' => $dashboardData->recentAttendances()->map(function ($attendance) {
                return [
                    'name' => $attendance->employee->user->name,
                    'location' => optional($attendance->location)->name ?: '-',
                    'time' => optional($attendance->check_in)->format('H:i') ?: '-',
                    'date' => $attendance->attendance_date->format('d/m/Y'),
                    'url' => route('admin.attendances.show', $attendance),
                ];
            })->values(),
            'recent_leave_requests' => $dashboardData->recentLeaveRequests()->map(function ($leaveRequest) {
                return [
                    'name' => $leaveRequest->employee->user->name,
                    'summary' => ($leaveRequest->type === 'sick' ? 'Sakit' : 'Izin').' · '.$leaveRequest->duration.' hari',
                    'date' => $leaveRequest->start_date->format('d/m/Y'),
                    'url' => route('admin.leave-requests.show', $leaveRequest),
                ];
            })->values(),
            'updated_at' => now()->toIso8601String(),
        ];

        if ($request->boolean('chart')) {
            $attendanceChart = $dashboardData->attendanceChart();
            $payload['chart'] = [
                'labels' => $attendanceChart->pluck('label')->values(),
                'present' => $attendanceChart->pluck('present')->values(),
                'late' => $attendanceChart->pluck('late')->values(),
            ];
        }

        return response()->json($payload);
    }

}
