<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceHistoryAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_employee_cannot_view_another_employees_attendance()
    {
        $schedule = WorkSchedule::create(['name' => 'History Test '.uniqid(), 'shift_type' => 'fixed', 'check_in_start' => '08:00', 'check_in_end' => '08:30', 'late_tolerance' => 0, 'check_out_start' => '17:00', 'status' => 'active']);
        $location = Location::create(['name' => 'History Location '.uniqid(), 'latitude' => -6.2, 'longitude' => 106.8, 'radius' => 100, 'accuracy_limit' => 50, 'status' => 'active']);
        list($userOne) = $this->employee($schedule);
        list(, $employeeTwo) = $this->employee($schedule);
        $attendance = Attendance::create(['employee_id' => $employeeTwo->id, 'location_id' => $location->id, 'work_schedule_id' => $schedule->id, 'attendance_date' => today(), 'check_in' => now(), 'check_in_status' => 'present']);

        $this->actingAs($userOne)->get(route('employee.attendances.show', $attendance))->assertForbidden();
    }

    private function employee(WorkSchedule $schedule)
    {
        $user = User::create(['name' => 'Employee Test', 'username' => 'history_'.uniqid(), 'password' => Hash::make('password123'), 'role' => 'employee', 'status' => 'active']);
        $employee = Employee::create(['user_id' => $user->id, 'work_schedule_id' => $schedule->id, 'employee_number' => 'HIS-'.uniqid()]);
        return [$user, $employee];
    }
}
