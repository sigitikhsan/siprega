<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Location;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityAttendanceLoadTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_twenty_security_employees_can_complete_day_and_night_shifts_without_collisions()
    {
        $day = $this->schedule('Security S1 Load', 'day', '06:00', '07:00', '19:00');
        $night = $this->schedule('Security S2 Load', 'night', '19:00', '20:00', '07:00');
        $this->location();

        $dayEmployees = $this->securityEmployees($day, '2026-09-22', 10, 'S1');
        $nightEmployees = $this->securityEmployees($night, '2026-09-22', 10, 'S2');

        Carbon::setTestNow('2026-09-22 06:30:00');
        foreach ($dayEmployees as [$user, $employee, $assignment]) {
            $this->actingAs($user)
                ->post(route('employee.attendance.check-in'), $this->gps('check_in'))
                ->assertSessionHas('success');
            $this->post(route('employee.attendance.check-in'), $this->gps('check_in'))
                ->assertSessionHasErrors('attendance');
            $this->assertAttendanceMatches($employee, $assignment, $day, '2026-09-22');
        }

        Carbon::setTestNow('2026-09-22 19:00:00');
        foreach ($dayEmployees as [$user, $employee]) {
            $this->actingAs($user)
                ->patch(route('employee.attendance.check-out'), $this->gps('check_out'))
                ->assertSessionHas('success');
            $this->patch(route('employee.attendance.check-out'), $this->gps('check_out'))
                ->assertSessionHas('error');

            $attendance = Attendance::where('employee_id', $employee->id)->sole();
            $this->assertSame('2026-09-22 19:00:00', $attendance->check_out->format('Y-m-d H:i:s'));
            $this->assertSame('normal', $attendance->check_out_status);
        }

        Carbon::setTestNow('2026-09-22 19:30:00');
        foreach ($nightEmployees as [$user, $employee, $assignment]) {
            $this->actingAs($user)
                ->post(route('employee.attendance.check-in'), $this->gps('check_in'))
                ->assertSessionHas('success');
            $this->post(route('employee.attendance.check-in'), $this->gps('check_in'))
                ->assertSessionHasErrors('attendance');
            $this->assertAttendanceMatches($employee, $assignment, $night, '2026-09-22');
        }

        Carbon::setTestNow('2026-09-23 07:00:00');
        foreach ($nightEmployees as [$user, $employee]) {
            $this->actingAs($user)
                ->patch(route('employee.attendance.check-out'), $this->gps('check_out'))
                ->assertSessionHas('success');
            $this->patch(route('employee.attendance.check-out'), $this->gps('check_out'))
                ->assertSessionHas('error');

            $attendance = Attendance::where('employee_id', $employee->id)->sole();
            $this->assertSame('2026-09-23 07:00:00', $attendance->check_out->format('Y-m-d H:i:s'));
            $this->assertSame('normal', $attendance->check_out_status);
        }

        $employeeIds = collect($dayEmployees)->merge($nightEmployees)
            ->map(function ($entry) { return $entry[1]->id; });

        $this->assertSame(20, Attendance::whereIn('employee_id', $employeeIds)->count());
        $this->assertSame(20, Attendance::whereIn('employee_id', $employeeIds)->whereNotNull('check_out')->count());
        $this->assertSame(20, Attendance::whereIn('employee_id', $employeeIds)->distinct('shift_assignment_id')->count('shift_assignment_id'));
    }

    private function assertAttendanceMatches(Employee $employee, EmployeeShiftAssignment $assignment, WorkSchedule $schedule, string $date): void
    {
        $attendance = Attendance::where('employee_id', $employee->id)->sole();
        $this->assertSame($assignment->id, $attendance->shift_assignment_id);
        $this->assertSame($schedule->id, $attendance->work_schedule_id);
        $this->assertSame($date, $attendance->attendance_date->toDateString());
    }

    private function securityEmployees(WorkSchedule $schedule, string $date, int $count, string $label): array
    {
        $employees = [];
        for ($index = 1; $index <= $count; $index++) {
            $suffix = strtolower($label).'_'.$index.'_'.uniqid();
            $user = User::create([
                'name' => 'Petugas Keamanan '.$label.' '.$index,
                'username' => 'security_'.$suffix,
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'status' => 'active',
            ]);
            $employee = Employee::create([
                'user_id' => $user->id,
                'work_schedule_id' => $schedule->id,
                'uses_shift_schedule' => true,
                'employee_number' => strtoupper($label).'-LOAD-'.$index.'-'.uniqid(),
                'position' => 'Petugas Keamanan',
            ]);
            $assignment = EmployeeShiftAssignment::create([
                'employee_id' => $employee->id,
                'work_schedule_id' => $schedule->id,
                'shift_date' => $date,
            ]);
            $employees[] = [$user, $employee, $assignment];
        }

        return $employees;
    }

    private function gps(string $action): array
    {
        $nonce = $this->getJson(route('employee.attendance.challenge', ['action' => $action]))
            ->assertOk()->json('token');

        return [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'accuracy' => 10,
            'captured_at' => now()->toIso8601String(),
            'attendance_nonce' => $nonce,
        ];
    }

    private function schedule(string $name, string $type, string $start, string $end, string $checkout): WorkSchedule
    {
        return WorkSchedule::create([
            'name' => $name.' '.uniqid(),
            'shift_type' => $type,
            'check_in_start' => $start,
            'check_in_end' => $end,
            'late_tolerance' => 0,
            'check_out_start' => $checkout,
            'status' => 'active',
        ]);
    }

    private function location(): Location
    {
        return Location::create([
            'name' => 'Security Load Location '.uniqid(),
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius' => 100,
            'accuracy_limit' => 50,
            'status' => 'active',
        ]);
    }
}
