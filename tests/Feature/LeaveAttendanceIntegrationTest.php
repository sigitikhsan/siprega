<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LeaveAttendanceIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_approved_leave_prevents_check_in()
    {
        $schedule = WorkSchedule::create(['name' => 'Leave Block '.uniqid(), 'shift_type' => 'fixed', 'check_in_start' => '00:00', 'check_in_end' => '00:01', 'late_tolerance' => 0, 'check_out_start' => '23:59', 'status' => 'active']);
        $user = User::create(['name' => 'Leave Block Employee', 'username' => 'block_'.uniqid(), 'password' => Hash::make('password123'), 'role' => 'employee', 'status' => 'active']);
        $employee = Employee::create(['user_id' => $user->id, 'work_schedule_id' => $schedule->id, 'employee_number' => 'BLK-'.uniqid()]);
        LeaveRequest::create(['employee_id' => $employee->id, 'type' => 'sick', 'start_date' => today(), 'duration' => 1, 'reason' => 'Sedang sakit dan memerlukan istirahat', 'status' => 'approved']);

        $this->actingAs($user);
        $nonce = $this->getJson(route('employee.attendance.challenge', ['action' => 'check_in']))->assertOk()->json('token');
        $this->post(route('employee.attendance.check-in'), ['latitude' => -6.2, 'longitude' => 106.8, 'accuracy' => 10, 'captured_at' => now()->toIso8601String(), 'attendance_nonce' => $nonce])
            ->assertSessionHas('error');
        $this->assertDatabaseMissing('attendances', ['employee_id' => $employee->id, 'attendance_date' => today()->toDateString()]);
    }
}
