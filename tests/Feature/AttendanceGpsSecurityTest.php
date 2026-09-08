<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceGpsSecurityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_stale_gps_position_is_rejected()
    {
        [$user, $employee] = $this->attendanceEmployee();

        $this->actingAs($user)->post(route('employee.attendance.check-in'), [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'accuracy' => 10,
            'captured_at' => now()->subMinutes(3)->toIso8601String(),
        ])->assertSessionHasErrors('location');

        $this->assertDatabaseMissing('attendances', ['employee_id' => $employee->id]);
    }

    public function test_fresh_valid_gps_position_is_stored_with_evidence()
    {
        [$user, $employee] = $this->attendanceEmployee();

        $this->actingAs($user);
        $nonce = $this->challenge('check_in');
        $this->post(route('employee.attendance.check-in'), [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'accuracy' => 10,
            'captured_at' => now()->toIso8601String(),
            'attendance_nonce' => $nonce,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'check_in_accuracy' => 10,
            'check_in_location_suspicious' => 0,
        ]);
    }

    public function test_gps_challenge_cannot_be_replayed()
    {
        [$user] = $this->attendanceEmployee();
        $this->actingAs($user);
        $nonce = $this->challenge('check_in');
        $payload = [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'accuracy' => 10,
            'captured_at' => now()->toIso8601String(),
            'attendance_nonce' => $nonce,
        ];

        $this->post(route('employee.attendance.check-in'), $payload)->assertSessionHas('success');
        $this->post(route('employee.attendance.check-in'), $payload)->assertSessionHasErrors('attendance_nonce');
    }

    private function challenge(string $action): string
    {
        return $this->getJson(route('employee.attendance.challenge', ['action' => $action]))
            ->assertOk()->json('token');
    }

    private function attendanceEmployee(): array
    {
        $schedule = WorkSchedule::create([
            'name' => 'GPS Test '.uniqid(),
            'shift_type' => 'fixed',
            'check_in_start' => '00:00',
            'check_in_end' => '23:58',
            'late_tolerance' => 0,
            'check_out_start' => '23:59',
            'status' => 'active',
        ]);
        $user = User::create([
            'name' => 'GPS Test Employee',
            'username' => 'gps_'.uniqid(),
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'status' => 'active',
        ]);
        $employee = Employee::create([
            'user_id' => $user->id,
            'work_schedule_id' => $schedule->id,
            'employee_number' => 'GPS-'.uniqid(),
        ]);
        Location::create([
            'name' => 'GPS Location '.uniqid(),
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius' => 100,
            'accuracy_limit' => 50,
            'status' => 'active',
        ]);

        return [$user, $employee];
    }
}
