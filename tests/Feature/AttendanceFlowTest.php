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

class AttendanceFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_position_outside_location_radius_is_rejected()
    {
        Carbon::setTestNow('2026-09-03 09:00:00');
        [$user, $employee] = $this->fixedEmployee();
        $this->location();

        $this->actingAs($user)->post(route('employee.attendance.check-in'), $this->gps(-6.25, 106.85))
            ->assertSessionHasErrors('location');

        $this->assertDatabaseMissing('attendances', ['employee_id' => $employee->id]);
    }

    public function test_position_with_poor_accuracy_is_rejected()
    {
        Carbon::setTestNow('2026-09-03 09:00:00');
        [$user, $employee] = $this->fixedEmployee();
        $this->location();

        $this->actingAs($user)->post(route('employee.attendance.check-in'), $this->gps(-6.2, 106.8, 75))
            ->assertSessionHasErrors('accuracy');

        $this->assertDatabaseMissing('attendances', ['employee_id' => $employee->id]);
    }

    public function test_employee_cannot_check_in_twice_for_same_shift_date()
    {
        Carbon::setTestNow('2026-09-03 09:00:00');
        [$user, $employee] = $this->fixedEmployee();
        $this->location();

        $this->actingAs($user)->post(route('employee.attendance.check-in'), $this->gps())->assertSessionHas('success');
        $this->actingAs($user)->post(route('employee.attendance.check-in'), $this->gps())->assertSessionHasErrors('attendance');

        $this->assertSame(1, Attendance::where('employee_id', $employee->id)->count());
    }

    public function test_employee_cannot_check_out_without_check_in()
    {
        Carbon::setTestNow('2026-09-03 17:00:00');
        [$user] = $this->fixedEmployee();
        $this->location();

        $this->actingAs($user)->patch(route('employee.attendance.check-out'), $this->gps())
            ->assertSessionHas('error');
    }

    public function test_night_shift_can_check_in_and_check_out_after_date_changes()
    {
        Carbon::setTestNow('2026-09-03 19:31:00');
        $night = $this->schedule('Shift Malam', 'night', '19:00', '19:30', '07:00');
        [$user, $employee] = $this->employee($night);
        $location = $this->location();
        $assignment = EmployeeShiftAssignment::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $night->id,
            'shift_date' => '2026-09-03',
        ]);

        $this->actingAs($user)->post(route('employee.attendance.check-in'), $this->gps())->assertSessionHas('success');

        $attendance = Attendance::where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame('2026-09-03', $attendance->attendance_date->toDateString());
        $this->assertSame('late', $attendance->check_in_status);
        $this->assertSame($assignment->id, $attendance->shift_assignment_id);
        $this->assertSame($location->id, $attendance->location_id);

        Carbon::setTestNow('2026-09-04 07:05:00');
        $this->actingAs($user)->patch(route('employee.attendance.check-out'), $this->gps())->assertSessionHas('success');

        $attendance->refresh();
        $this->assertSame('2026-09-04 07:05:00', $attendance->check_out->format('Y-m-d H:i:s'));
        $this->assertSame('normal', $attendance->check_out_status);
    }

    private function gps(float $latitude = -6.2, float $longitude = 106.8, float $accuracy = 10): array
    {
        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'captured_at' => now()->toIso8601String(),
        ];
    }

    private function fixedEmployee(): array
    {
        return $this->employee($this->schedule('Jadwal Tetap', 'fixed', '05:00', '08:00', '16:00'));
    }

    private function employee(WorkSchedule $schedule): array
    {
        $user = User::create([
            'name' => 'Attendance Flow Employee',
            'username' => 'flow_'.uniqid(),
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'status' => 'active',
        ]);
        $employee = Employee::create([
            'user_id' => $user->id,
            'work_schedule_id' => $schedule->id,
            'employee_number' => 'FLOW-'.uniqid(),
            'position' => 'Pegawai',
        ]);

        return [$user, $employee];
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
            'name' => 'Attendance Flow Location '.uniqid(),
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius' => 100,
            'accuracy_limit' => 50,
            'status' => 'active',
        ]);
    }
}
