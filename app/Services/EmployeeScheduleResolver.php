<?php

/**
 * Sumber tunggal untuk menentukan jadwal efektif pegawai pada waktu atau tanggal tertentu.
 * Membaca Employee.workSchedule dan EmployeeShiftAssignment, termasuk kelanjutan shift malam dari hari sebelumnya.
 * Catatan: service hanya memilih jadwal; otorisasi, transaksi, dan pencatatan attendance tetap dilakukan controller.
 */

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use Carbon\Carbon;

class EmployeeScheduleResolver
{
    public function forCurrentMoment(Employee $employee, Carbon $now): array
    {
        $previousDate = $now->copy()->subDay();
        $assignments = EmployeeShiftAssignment::with('workSchedule')
            ->where('employee_id', $employee->id)
            ->whereIn('shift_date', [$previousDate->toDateString(), $now->toDateString()])
            ->get()->keyBy(function ($assignment) { return $assignment->shift_date->toDateString(); });
        $previousAssignment = $assignments->get($previousDate->toDateString());
        $previousSchedule = $previousAssignment ? $previousAssignment->workSchedule : null;
        if ($previousAssignment && !$previousAssignment->is_day_off && $previousSchedule && $previousSchedule->shift_type === 'night') {
            $checkoutAt = Carbon::parse($now->toDateString().' '.$previousSchedule->check_out_start);
            if ($now->lte($checkoutAt)) {
                return [$previousSchedule, $previousAssignment, $previousDate->startOfDay()];
            }
        }

        $todayAssignment = $assignments->get($now->toDateString());
        if ($todayAssignment) {
            if ($todayAssignment->is_day_off || !$todayAssignment->workSchedule) {
                return [null, $todayAssignment, $now->copy()->startOfDay()];
            }
            return [$todayAssignment->workSchedule, $todayAssignment, $now->copy()->startOfDay()];
        }

        $employee->loadMissing('workSchedule');
        if ($employee->uses_shift_schedule) {
            return [null, null, $now->copy()->startOfDay()];
        }
        if ($employee->workSchedule && $employee->workSchedule->shift_type === 'fixed') {
            return [$employee->workSchedule, null, $now->copy()->startOfDay()];
        }

        return [null, null, $now->copy()->startOfDay()];
    }

    public function forDate(Employee $employee, Carbon $date): array
    {
        $assignment = $this->assignmentForDate($employee, $date);

        if ($assignment) {
            if ($assignment->is_day_off || !$assignment->workSchedule) {
                return [null, $assignment];
            }
            return [$assignment->workSchedule, $assignment];
        }

        $employee->loadMissing('workSchedule');
        if ($employee->uses_shift_schedule) {
            return [null, null];
        }
        if ($employee->workSchedule && $employee->workSchedule->shift_type === 'fixed') {
            return [$employee->workSchedule, null];
        }

        return [null, null];
    }

    private function assignmentForDate(Employee $employee, Carbon $date)
    {
        return EmployeeShiftAssignment::with('workSchedule')
            ->where('employee_id', $employee->id)
            ->where('shift_date', $date->toDateString())
            ->first();
    }
}
