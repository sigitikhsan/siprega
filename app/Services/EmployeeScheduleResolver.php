<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use Carbon\Carbon;

class EmployeeScheduleResolver
{
    public function forCurrentMoment(Employee $employee, Carbon $now): array
    {
        $previousDate = $now->copy()->subDay();
        $previousAssignment = $this->assignmentForDate($employee, $previousDate);
        if ($previousAssignment && !$previousAssignment->is_day_off && $previousAssignment->workSchedule->shift_type === 'night') {
            $checkoutAt = Carbon::parse($now->toDateString().' '.$previousAssignment->workSchedule->check_out_start);
            if ($now->lte($checkoutAt)) {
                return [$previousAssignment->workSchedule, $previousAssignment, $previousDate->startOfDay()];
            }
        }

        $todayAssignment = $this->assignmentForDate($employee, $now);
        if ($todayAssignment) {
            if ($todayAssignment->is_day_off) {
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
            if ($assignment->is_day_off) {
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
            ->whereDate('shift_date', $date->toDateString())
            ->first();
    }
}
