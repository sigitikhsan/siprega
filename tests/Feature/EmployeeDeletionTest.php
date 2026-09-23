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

class EmployeeDeletionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_employee_with_attendance_is_deactivated_without_deleting_history()
    {
        $admin = $this->user('admin');
        $employeeUser = $this->user('employee');
        $employeeUser->forceFill(['remember_token' => 'remember-before-delete'])->save();
        $schedule = WorkSchedule::create(['name' => 'Delete Test '.uniqid(), 'shift_type' => 'fixed', 'check_in_start' => '08:00', 'check_in_end' => '08:30', 'late_tolerance' => 0, 'check_out_start' => '17:00', 'status' => 'active']);
        $employee = Employee::create(['user_id' => $employeeUser->id, 'work_schedule_id' => $schedule->id, 'employee_number' => 'DEL-'.uniqid()]);
        $location = Location::create(['name' => 'Test '.uniqid(), 'latitude' => -6.2, 'longitude' => 106.8, 'radius' => 100, 'accuracy_limit' => 50, 'status' => 'active']);
        $attendance = Attendance::create(['employee_id' => $employee->id, 'location_id' => $location->id, 'work_schedule_id' => $schedule->id, 'attendance_date' => today(), 'check_in' => now(), 'check_in_status' => 'present']);

        $this->actingAs($admin)->delete(route('admin.employees.destroy', $employee))->assertRedirect(route('admin.employees.index'));

        $this->assertDatabaseHas('users', ['id' => $employeeUser->id, 'status' => 'inactive']);
        $this->assertNotSame('remember-before-delete', $employeeUser->fresh()->remember_token);
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
        $this->assertDatabaseHas('attendances', ['id' => $attendance->id]);
    }

    private function user($role)
    {
        return User::create(['name' => ucfirst($role).' Test', 'username' => $role.'_'.uniqid(), 'password' => Hash::make('password123'), 'role' => $role, 'status' => 'active']);
    }
}
