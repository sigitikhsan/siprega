<?php

/**
 * Menyiapkan dashboard pegawai: jadwal aktif, absensi terbuka, status izin/libur, ticker, dan riwayat terbaru.
 * Menggabungkan relasi Employee/Attendance dengan hasil EmployeeScheduleResolver untuk shift tetap maupun lintas hari.
 * Catatan: waktu awal/akhir dikirim dari server; React hanya menampilkan ticker dan bukan sumber keputusan absensi.
 */

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
        $serverNow = now();
        list($activeSchedule, $activeAssignment, $shiftDate) = $resolver->forCurrentMoment($employee, $serverNow);
        $openAttendance = $employee->attendances()->with(['location', 'workSchedule'])
            ->whereNotNull('check_in')->whereNull('check_out')->latest('check_in')->first();
        $todayAttendance = $openAttendance ?: $employee->attendances()->with(['location', 'workSchedule'])
            ->where('attendance_date', $serverNow->toDateString())->first();
        if ($openAttendance && $openAttendance->workSchedule) {
            $activeSchedule = $openAttendance->workSchedule;
        }
        $recentAttendances = $employee->attendances()->with(['location', 'workSchedule'])
            ->latest('attendance_date')->limit(5)->get();
        $todayLeave = $employee->leaveRequests()->whereIn('status', ['pending', 'approved'])
            ->overlappingDates($serverNow)->latest()->first();

        $isDayOff = $activeAssignment && $activeAssignment->is_day_off;

        // Match the checkout validation, including the next morning of a night shift.
        $checkoutAt = null;
        if ($openAttendance && $activeSchedule) {
            $checkoutDate = $openAttendance->attendance_date->copy();
            if ($activeSchedule->shift_type === 'night') {
                $checkoutDate->addDay();
            }
            $checkoutAt = $checkoutDate->setTimeFromTimeString($activeSchedule->check_out_start);
        }

        $tickerDate = $openAttendance ? $openAttendance->attendance_date->copy() : $shiftDate->copy();
        $tickerState = 'scheduled';
        if (!$openAttendance && $todayLeave && $todayLeave->status === 'approved') {
            $tickerState = $todayLeave->type === 'sick' ? 'sick' : 'leave';
        } elseif (!$openAttendance && $isDayOff) {
            $tickerState = 'day_off';
        } elseif (!$activeSchedule || (!$openAttendance && $activeSchedule->status !== 'active')) {
            $tickerState = 'unavailable';
        }
        $tickerStart = $tickerEnd = null;
        if ($tickerState === 'scheduled') {
            $tickerStart = $tickerDate->copy()->setTimeFromTimeString($activeSchedule->check_in_start);
            $tickerEnd = $tickerDate->copy();
            if ($activeSchedule->shift_type === 'night') {
                $tickerEnd->addDay();
            }
            $tickerEnd->setTimeFromTimeString($activeSchedule->check_out_start);
        }
        $scheduleTicker = [
            'serverNow' => $serverNow->toIso8601String(),
            'timezone' => config('app.timezone'),
            'scheduleName' => $activeSchedule ? $activeSchedule->name : null,
            'shiftType' => $activeSchedule ? $activeSchedule->shift_type : null,
            'shiftDate' => $tickerDate->startOfDay()->toIso8601String(),
            'startsAt' => $tickerStart ? $tickerStart->toIso8601String() : null,
            'endsAt' => $tickerEnd ? $tickerEnd->toIso8601String() : null,
            'state' => $tickerState,
            'attendanceStatus' => $todayAttendance
                ? ($todayAttendance->check_out ? 'Absensi selesai' : ($todayAttendance->check_in_status === 'late' ? 'Sudah masuk · Terlambat' : 'Sudah masuk'))
                : 'Belum absen masuk',
        ];

        return view('employee.dashboard', compact('employee', 'todayAttendance', 'openAttendance', 'recentAttendances', 'activeSchedule', 'activeAssignment', 'todayLeave', 'isDayOff', 'checkoutAt', 'scheduleTicker'));
    }
}
