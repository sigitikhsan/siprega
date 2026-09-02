<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LeaveRequestAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_employee_cannot_view_another_employees_leave_request()
    {
        $schedule = WorkSchedule::create(['name' => 'Leave Test '.uniqid(), 'shift_type' => 'fixed', 'check_in_start' => '08:00', 'check_in_end' => '08:30', 'late_tolerance' => 0, 'check_out_start' => '17:00', 'status' => 'active']);
        list($userOne) = $this->employee($schedule);
        list(, $employeeTwo) = $this->employee($schedule);
        $leave = LeaveRequest::create(['employee_id' => $employeeTwo->id, 'type' => 'permission', 'start_date' => today()->addDay(), 'duration' => 1, 'reason' => 'Keperluan keluarga penting', 'status' => 'pending']);

        $this->actingAs($userOne)->get(route('employee.leave-requests.show', $leave))->assertForbidden();
    }

    private function employee(WorkSchedule $schedule)
    {
        $user = User::create(['name' => 'Leave Employee', 'username' => 'leave_'.uniqid(), 'password' => Hash::make('password123'), 'role' => 'employee', 'status' => 'active']);
        $employee = Employee::create(['user_id' => $user->id, 'work_schedule_id' => $schedule->id, 'employee_number' => 'LEV-'.uniqid()]);
        return [$user, $employee];
    }
}
