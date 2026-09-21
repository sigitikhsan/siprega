<?php

/**
 * Mengoreksi waktu/status absensi secara administratif dan mencatat jejak perubahannya.
 * Attendance diperbarui bersama AttendanceCorrection dalam transaksi, lalu cache AdminDashboardData diinvalidasi.
 * Catatan: koreksi lintas tanggal hanya diizinkan sesuai aturan jenis shift dan wajib menyimpan alasan.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Services\AdminDashboardData;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceCorrectionController extends Controller
{
    public function edit(Attendance $attendance)
    {
        $attendance->load(['employee.user', 'employee.workSchedule', 'workSchedule', 'location']);

        return view('admin.attendances.correction', compact('attendance'));
    }

    public function update(Request $request, Attendance $attendance)
    {
        $validated = $request->validate([
            'check_in' => ['required', 'date_format:Y-m-d\TH:i'],
            'check_out' => ['nullable', 'date_format:Y-m-d\TH:i', 'after:check_in'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $newCheckIn = Carbon::createFromFormat('Y-m-d\TH:i', $validated['check_in']);
        $newCheckOut = !empty($validated['check_out'])
            ? Carbon::createFromFormat('Y-m-d\TH:i', $validated['check_out'])
            : null;

        $attendanceDate = $attendance->attendance_date->format('Y-m-d');
        $attendance->loadMissing(['workSchedule', 'employee.workSchedule']);
        $schedule = $attendance->workSchedule ?: $attendance->employee->workSchedule;
        if (!$schedule) {
            throw ValidationException::withMessages([
                'check_in' => 'Jadwal absensi tidak tersedia. Periksa data jadwal sebelum melakukan koreksi.',
            ]);
        }
        $usesShiftSchedule = $attendance->employee->uses_shift_schedule
            || in_array($schedule->shift_type, ['day', 'night'], true);
        $allowedCheckoutDates = [$attendanceDate];
        if ($usesShiftSchedule) {
            $allowedCheckoutDates[] = $attendance->attendance_date->copy()->addDay()->format('Y-m-d');
        }

        if ($newCheckIn->format('Y-m-d') !== $attendanceDate) {
            throw ValidationException::withMessages([
                'check_in' => 'Tanggal masuk harus sama dengan tanggal shift.',
            ]);
        }

        if ($newCheckOut && !in_array($newCheckOut->format('Y-m-d'), $allowedCheckoutDates, true)) {
            throw ValidationException::withMessages([
                'check_out' => $usesShiftSchedule
                    ? 'Tanggal pulang petugas shift harus pada tanggal shift atau hari berikutnya.'
                    : 'Tanggal pulang harus sama dengan tanggal absensi.',
            ]);
        }

        $oldCheckIn = $attendance->check_in;
        $oldCheckOut = $attendance->check_out;

        $checkInChanged = !$oldCheckIn || !$oldCheckIn->equalTo($newCheckIn);
        $checkOutChanged = ($oldCheckOut === null) !== ($newCheckOut === null)
            || ($oldCheckOut && $newCheckOut && !$oldCheckOut->equalTo($newCheckOut));

        if (!$checkInChanged && !$checkOutChanged) {
            throw ValidationException::withMessages([
                'check_in' => 'Tidak ada perubahan waktu yang perlu disimpan.',
            ]);
        }

        $correction = DB::transaction(function () use ($attendance, $request, $validated, $newCheckIn, $newCheckOut) {
            $lockedAttendance = Attendance::whereKey($attendance->id)->lockForUpdate()->firstOrFail();
            $lockedAttendance->load(['employee.workSchedule', 'workSchedule']);

            $schedule = $lockedAttendance->workSchedule ?: $lockedAttendance->employee->workSchedule;
            if (!$schedule) {
                throw ValidationException::withMessages([
                    'check_in' => 'Jadwal absensi tidak tersedia. Periksa data jadwal sebelum melakukan koreksi.',
                ]);
            }
            $lateThreshold = Carbon::parse($lockedAttendance->attendance_date->format('Y-m-d').' '.$schedule->check_in_end)
                ->addMinutes((int) $schedule->late_tolerance);
            $checkoutDate = $schedule->shift_type === 'night'
                ? $lockedAttendance->attendance_date->copy()->addDay()->format('Y-m-d')
                : $lockedAttendance->attendance_date->format('Y-m-d');
            $checkOutThreshold = Carbon::parse($checkoutDate.' '.$schedule->check_out_start);
            $isLate = $newCheckIn->greaterThan($lateThreshold);
            $isEarlyCheckout = $newCheckOut && $newCheckOut->lessThan($checkOutThreshold);

            $correction = AttendanceCorrection::create([
                'attendance_id' => $lockedAttendance->id,
                'corrected_by' => $request->user()->id,
                'old_check_in' => $lockedAttendance->check_in,
                'new_check_in' => $newCheckIn,
                'old_check_out' => $lockedAttendance->check_out,
                'new_check_out' => $newCheckOut,
                'reason' => $validated['reason'],
            ]);

            $lockedAttendance->update([
                'check_in' => $newCheckIn,
                'check_in_status' => $isLate ? 'late' : 'present',
                'late_reason' => $isLate ? $lockedAttendance->late_reason : null,
                'check_out' => $newCheckOut,
                'check_out_status' => $newCheckOut
                    ? ($isEarlyCheckout ? 'early_checkout' : 'normal')
                    : null,
                'early_checkout_reason' => $isEarlyCheckout
                    ? ($lockedAttendance->early_checkout_reason ?: 'Koreksi admin: '.$validated['reason'])
                    : null,
            ]);

            return $correction;
        });

        app(AdminDashboardData::class)->forgetAttendance();
        return redirect()->route('admin.attendances.show', $attendance)
            ->with('success', 'Data absensi berhasil dikoreksi.');
    }
}
