<?php

/**
 * Menyusun halaman dashboard admin dari data terbaru saat halaman dimuat.
 * Seluruh agregasi Attendance, Employee, dan LeaveRequest didelegasikan ke AdminDashboardData.
 * Catatan: jangan menaruh query dashboard berulang di view; gunakan service agar cache dan invalidasi konsisten.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardData;

class DashboardController extends Controller
{
    public function index(AdminDashboardData $dashboardData)
    {
        $statistics = $dashboardData->statistics();
        $recentAttendances = $dashboardData->recentAttendances();
        $recentLeaveRequests = $dashboardData->recentLeaveRequests();
        $attendanceChart = $dashboardData->attendanceChart();
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
            'chartTotals'
        ));
    }

}
