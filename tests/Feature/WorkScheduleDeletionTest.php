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

class WorkScheduleDeletionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_schedule_with_attendance_history_is_deactivated_instead_of_deleted()
    {
        $admin = $this->user('admin');
        $historicalSchedule = $this->schedule('Magang Lama');
        $currentSchedule = $this->schedule('Jadwal Baru');
        $employeeUser = $this->user('employee');
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'work_schedule_id' => $currentSchedule->id,
            'employee_number' => 'SCH-'.uniqid(),
        ]);
        $location = Location::create([
            'name' => 'Lokasi Jadwal '.uniqid(),
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius' => 100,
            'accuracy_limit' => 50,
            'status' => 'active',
        ]);
        Attendance::create([
            'employee_id' => $employee->id,
            'location_id' => $location->id,
            'work_schedule_id' => $historicalSchedule->id,
            'attendance_date' => today()->subDay(),
            'check_in' => now()->subDay(),
            'check_in_status' => 'present',
        ]);

        $this->actingAs($admin)->delete(route('admin.work-schedules.destroy', $historicalSchedule))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('work_schedules', ['id' => $historicalSchedule->id, 'status' => 'inactive']);
        $this->assertDatabaseHas('attendances', ['work_schedule_id' => $historicalSchedule->id]);
    }

    public function test_unused_schedule_is_deleted_permanently()
    {
        $admin = $this->user('admin');
        $schedule = $this->schedule('Tidak Terpakai');

        $this->actingAs($admin)->delete(route('admin.work-schedules.destroy', $schedule))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('work_schedules', ['id' => $schedule->id]);
    }

    public function test_schedule_used_by_employee_cannot_be_deactivated_from_edit_form()
    {
        $admin = $this->user('admin');
        $schedule = $this->schedule('Jadwal Aktif Pegawai');
        $employeeUser = $this->user('employee');
        Employee::create([
            'user_id' => $employeeUser->id,
            'work_schedule_id' => $schedule->id,
            'employee_number' => 'ACTIVE-'.uniqid(),
        ]);

        $this->actingAs($admin)->put(route('admin.work-schedules.update', $schedule), [
            'name' => $schedule->name,
            'shift_type' => $schedule->shift_type,
            'check_in_start' => substr($schedule->check_in_start, 0, 5),
            'check_in_end' => substr($schedule->check_in_end, 0, 5),
            'late_tolerance' => $schedule->late_tolerance,
            'check_out_start' => substr($schedule->check_out_start, 0, 5),
            'status' => 'inactive',
        ])->assertSessionHasErrors('status');

        $this->assertDatabaseHas('work_schedules', ['id' => $schedule->id, 'status' => 'active']);
    }

    private function user(string $role): User
    {
        return User::create([
            'name' => ucfirst($role).' Schedule Test',
            'username' => $role.'_schedule_'.uniqid(),
            'password' => Hash::make('password123'),
            'role' => $role,
            'status' => 'active',
        ]);
    }

    private function schedule(string $name): WorkSchedule
    {
        return WorkSchedule::create([
            'name' => $name.' '.uniqid(),
            'shift_type' => 'fixed',
            'check_in_start' => '07:00',
            'check_in_end' => '08:00',
            'late_tolerance' => 0,
            'check_out_start' => '16:00',
            'status' => 'active',
        ]);
    }
}
