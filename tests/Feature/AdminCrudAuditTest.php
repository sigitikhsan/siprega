<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Location;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\EmployeeScheduleResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCrudAuditTest extends TestCase
{
    use DatabaseTransactions;

    public function test_inactive_admin_session_is_terminated()
    {
        $admin = $this->user('admin', 'inactive');

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_employee_cannot_be_created_with_inactive_schedule()
    {
        $admin = $this->user('admin');
        $inactiveSchedule = $this->schedule('Jadwal Nonaktif', 'fixed', 'inactive');

        $this->actingAs($admin)->post(route('admin.employees.store'), [
            'name' => 'Pegawai Invalid',
            'employee_number' => 'INVALID-'.uniqid(),
            'username' => 'invalid_'.uniqid(),
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
            'work_schedule_id' => $inactiveSchedule->id,
        ])->assertSessionHasErrors('work_schedule_id');
    }

    public function test_security_employee_can_follow_daily_shift_assignments()
    {
        $admin = $this->user('admin');
        $day = $this->schedule('Shift Siang Fleksibel', 'day');

        $this->actingAs($admin)->post(route('admin.employees.store'), [
            'name' => 'Security Fleksibel',
            'employee_number' => 'SHIFT-'.uniqid(),
            'username' => 'shift_'.uniqid(),
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
            'work_schedule_id' => 'shift',
            'position' => 'Petugas Keamanan',
        ])->assertSessionHas('success');

        $employee = Employee::whereHas('user', function ($query) {
            $query->where('name', 'Security Fleksibel');
        })->firstOrFail();

        $this->assertTrue($employee->uses_shift_schedule);
        $this->assertContains($employee->workSchedule->shift_type, ['day', 'night']);
        $this->assertSame('active', $employee->workSchedule->status);
        [$schedule] = app(EmployeeScheduleResolver::class)->forDate($employee, today());
        $this->assertNull($schedule);
    }

    public function test_deactivating_employee_cancels_future_shift_assignments()
    {
        $admin = $this->user('admin');
        $fixed = $this->schedule('Jadwal Pegawai');
        $night = $this->schedule('Shift Malam', 'night');
        [$employeeUser, $employee] = $this->employee($fixed);
        $employeeUser->forceFill(['remember_token' => 'remember-before-inactive'])->save();
        EmployeeShiftAssignment::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $night->id,
            'shift_date' => today()->addDay(),
        ]);

        $this->actingAs($admin)->put(route('admin.employees.update', $employee), [
            'name' => $employeeUser->name,
            'employee_number' => $employee->employee_number,
            'username' => $employeeUser->username,
            'status' => 'inactive',
            'work_schedule_id' => $fixed->id,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $employeeUser->id, 'status' => 'inactive']);
        $this->assertNotSame('remember-before-inactive', $employeeUser->fresh()->remember_token);
        $this->assertDatabaseMissing('employee_shift_assignments', ['employee_id' => $employee->id]);
    }

    public function test_shift_cannot_be_changed_after_attendance_exists()
    {
        $admin = $this->user('admin');
        $day = $this->schedule('Shift Siang', 'day');
        $night = $this->schedule('Shift Malam', 'night');
        [, $employee] = $this->employee($day);
        $location = $this->location();
        EmployeeShiftAssignment::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $day->id,
            'shift_date' => today(),
        ]);
        Attendance::create([
            'employee_id' => $employee->id,
            'location_id' => $location->id,
            'work_schedule_id' => $day->id,
            'attendance_date' => today(),
            'check_in' => now(),
            'check_in_status' => 'present',
        ]);

        $this->actingAs($admin)->post(route('admin.shift-assignments.store'), [
            'shift_date' => today()->toDateString(),
            'assignments' => [$employee->id => $night->id],
        ])->assertSessionHasErrors('assignments.'.$employee->id);

        $this->assertDatabaseHas('employee_shift_assignments', [
            'employee_id' => $employee->id,
            'work_schedule_id' => $day->id,
        ]);
    }

    public function test_correction_clears_reasons_that_no_longer_match_status()
    {
        $admin = $this->user('admin');
        $schedule = $this->schedule('Jadwal Koreksi');
        [, $employee] = $this->employee($schedule);
        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'location_id' => $this->location()->id,
            'work_schedule_id' => $schedule->id,
            'attendance_date' => '2026-09-01',
            'check_in' => '2026-09-01 09:00:00',
            'check_in_status' => 'late',
            'late_reason' => 'Macet',
            'check_out' => '2026-09-01 15:00:00',
            'check_out_status' => 'early_checkout',
            'early_checkout_reason' => 'Keperluan keluarga',
        ]);

        $this->actingAs($admin)->patch(route('admin.attendances.correction.update', $attendance), [
            'check_in' => '2026-09-01T07:30',
            'check_out' => '2026-09-01T16:00',
            'reason' => 'Koreksi berdasarkan bukti manual.',
        ])->assertSessionHas('success');

        $attendance->refresh();
        $this->assertSame('present', $attendance->check_in_status);
        $this->assertNull($attendance->late_reason);
        $this->assertSame('normal', $attendance->check_out_status);
        $this->assertNull($attendance->early_checkout_reason);
    }

    public function test_admin_can_correct_security_checkout_across_dates()
    {
        $admin = $this->user('admin');
        $schedule = $this->schedule('Security Siang', 'day');
        [, $employee] = $this->employee($schedule);
        $employee->update(['uses_shift_schedule' => true]);
        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'location_id' => $this->location()->id,
            'work_schedule_id' => $schedule->id,
            'attendance_date' => '2026-09-01',
            'check_in' => '2026-09-01 07:00:00',
            'check_in_status' => 'present',
        ]);

        $this->actingAs($admin)->patch(route('admin.attendances.correction.update', $attendance), [
            'check_in' => '2026-09-01T07:00',
            'check_out' => '2026-09-02T01:00',
            'reason' => 'Petugas keamanan menyelesaikan tugas lintas tanggal.',
        ])->assertSessionHas('success');

        $this->assertSame('2026-09-02 01:00:00', $attendance->fresh()->check_out->format('Y-m-d H:i:s'));
    }

    public function test_fixed_schedule_correction_cannot_checkout_on_another_date()
    {
        $admin = $this->user('admin');
        $schedule = $this->schedule('Jadwal Tetap', 'fixed');
        [, $employee] = $this->employee($schedule);
        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'location_id' => $this->location()->id,
            'work_schedule_id' => $schedule->id,
            'attendance_date' => '2026-09-01',
            'check_in' => '2026-09-01 07:00:00',
            'check_in_status' => 'present',
        ]);

        $this->actingAs($admin)->patch(route('admin.attendances.correction.update', $attendance), [
            'check_in' => '2026-09-01T07:00',
            'check_out' => '2026-09-02T01:00',
            'reason' => 'Pengujian batas tanggal jadwal tetap.',
        ])->assertSessionHasErrors('check_out');

        $this->assertNull($attendance->fresh()->check_out);
    }

    public function test_attendance_detail_shows_dates_below_check_times()
    {
        $admin = $this->user('admin');
        $schedule = $this->schedule('Security Malam', 'night');
        [, $employee] = $this->employee($schedule);
        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'location_id' => $this->location()->id,
            'work_schedule_id' => $schedule->id,
            'attendance_date' => '2026-09-01',
            'check_in' => '2026-09-01 19:00:00',
            'check_in_status' => 'present',
            'check_out' => '2026-09-02 07:00:00',
            'check_out_status' => 'normal',
        ]);

        $this->actingAs($admin)->get(route('admin.attendances.show', $attendance))
            ->assertOk()
            ->assertSee('19:00:00')
            ->assertSee('01/09/2026')
            ->assertSee('07:00:00')
            ->assertSee('02/09/2026');
    }

    private function user(string $role, string $status = 'active'): User
    {
        return User::create([
            'name' => ucfirst($role).' Audit',
            'username' => $role.'_audit_'.uniqid(),
            'password' => Hash::make('password123'),
            'role' => $role,
            'status' => $status,
        ]);
    }

    private function employee(WorkSchedule $schedule): array
    {
        $user = $this->user('employee');
        $employee = Employee::create([
            'user_id' => $user->id,
            'work_schedule_id' => $schedule->id,
            'employee_number' => 'AUD-'.uniqid(),
            'position' => 'Petugas Keamanan',
        ]);
        return [$user, $employee];
    }

    private function schedule(string $name, string $type = 'fixed', string $status = 'active'): WorkSchedule
    {
        return WorkSchedule::create([
            'name' => $name.' '.uniqid(),
            'shift_type' => $type,
            'check_in_start' => $type === 'night' ? '19:00' : '07:00',
            'check_in_end' => $type === 'night' ? '19:30' : '08:00',
            'late_tolerance' => 0,
            'check_out_start' => $type === 'night' ? '07:00' : '16:00',
            'status' => $status,
        ]);
    }

    private function location(): Location
    {
        return Location::create([
            'name' => 'Lokasi Audit '.uniqid(),
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius' => 100,
            'accuracy_limit' => 50,
            'status' => 'active',
        ]);
    }
}
