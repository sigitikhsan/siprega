<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeProfileTest extends TestCase
{
    use DatabaseTransactions;

    public function test_employee_can_update_account_and_phone_without_changing_employee_identity()
    {
        $schedule = WorkSchedule::create(['name' => 'Profile Test '.uniqid(), 'shift_type' => 'fixed', 'check_in_start' => '08:00', 'check_in_end' => '08:30', 'late_tolerance' => 0, 'check_out_start' => '17:00', 'status' => 'active']);
        $user = User::create(['name' => 'Old Name', 'username' => 'profile_'.uniqid(), 'password' => Hash::make('password123'), 'role' => 'employee', 'status' => 'active']);
        $employee = Employee::create(['user_id' => $user->id, 'work_schedule_id' => $schedule->id, 'employee_number' => 'PRO-'.uniqid(), 'position' => 'Security']);
        $originalNumber = $employee->employee_number;

        $this->actingAs($user)->put(route('employee.profile.update'), [
            'name' => 'New Name', 'username' => $user->username, 'email' => 'employee@example.test',
            'phone' => '081234567890', 'employee_number' => 'ILLEGAL-CHANGE', 'position' => 'Administrator',
        ])->assertRedirect(route('employee.profile.show'));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name', 'email' => 'employee@example.test']);
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'employee_number' => $originalNumber, 'position' => 'Security', 'phone' => '081234567890']);
    }
}
