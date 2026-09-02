<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\EmployeeScheduleResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeScheduleResolverTest extends TestCase
{
    use DatabaseTransactions;

    public function test_previous_night_assignment_is_active_until_checkout_time()
    {
        $fixed = $this->schedule('Jadwal Tetap Test', 'fixed', '08:00', '08:30', '17:00');
        $night = $this->schedule('Shift Malam Test', 'night', '19:00', '19:30', '07:00');
        $day = $this->schedule('Shift Siang Test', 'day', '07:00', '07:30', '19:00');
        $employee = $this->employee($fixed);
        $assignment = EmployeeShiftAssignment::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $night->id,
            'shift_date' => '2026-09-01',
        ]);
        EmployeeShiftAssignment::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $day->id,
            'shift_date' => '2026-09-02',
        ]);

        list($schedule, $resolvedAssignment, $shiftDate) = app(EmployeeScheduleResolver::class)
            ->forCurrentMoment($employee, Carbon::parse('2026-09-02 06:00:00'));

        $this->assertSame($night->id, $schedule->id);
        $this->assertSame($assignment->id, $resolvedAssignment->id);
        $this->assertSame('2026-09-01', $shiftDate->toDateString());
    }

    public function test_day_off_assignment_has_no_active_schedule()
    {
        $day = $this->schedule('Shift Siang Libur', 'day', '07:00', '07:30', '19:00');
        $employee = $this->employee($day);
        $assignment = EmployeeShiftAssignment::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $day->id,
            'shift_date' => '2026-09-03',
            'is_day_off' => true,
        ]);

        [$schedule, $resolvedAssignment] = app(EmployeeScheduleResolver::class)
            ->forDate($employee, Carbon::parse('2026-09-03'));

        $this->assertNull($schedule);
        $this->assertSame($assignment->id, $resolvedAssignment->id);
        $this->assertTrue($resolvedAssignment->is_day_off);
    }

    private function schedule($name, $type, $start, $end, $checkout)
    {
        return WorkSchedule::create([
            'name' => $name.' '.uniqid(), 'shift_type' => $type,
            'check_in_start' => $start, 'check_in_end' => $end,
            'late_tolerance' => 0, 'check_out_start' => $checkout, 'status' => 'active',
        ]);
    }

    private function employee(WorkSchedule $schedule)
    {
        $user = User::create([
            'name' => 'Security Test', 'username' => 'security_'.uniqid(),
            'password' => Hash::make('password123'), 'role' => 'employee', 'status' => 'active',
        ]);
        return Employee::create([
            'user_id' => $user->id, 'work_schedule_id' => $schedule->id,
            'employee_number' => 'TEST-'.uniqid(), 'position' => 'Security',
        ]);
    }
}
