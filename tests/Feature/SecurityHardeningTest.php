<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Employee;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use DatabaseTransactions;

    public function test_responses_include_security_headers(): void
    {
        $response = $this->get('/login');

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'geolocation=(self), camera=(), microphone=()');

        $policy = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline'", $policy);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline'", $policy);
        $this->assertStringNotContainsString('cdn.jsdelivr.net', $policy);
    }

    public function test_admin_password_change_rotates_remember_token(): void
    {
        $user = User::create([
            'name' => 'Admin Security Test',
            'username' => 'admin_security_test',
            'email' => 'admin-security@example.test',
            'role' => 'admin',
            'status' => 'active',
            'password' => Hash::make('password-lama'),
        ]);
        $user->forceFill(['remember_token' => 'token-lama'])->save();

        $this->actingAs($user)->put(route('admin.profile.password.update'), [
            'current_password' => 'password-lama',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertRedirect(route('admin.profile.edit'));

        $user->refresh();
        $this->assertTrue(Hash::check('password-baru', $user->password));
        $this->assertNotSame('token-lama', $user->remember_token);
    }

    public function test_new_password_must_differ_from_current_password(): void
    {
        $user = User::create([
            'name' => 'Admin Password Test',
            'username' => 'admin_password_test',
            'email' => 'admin-password@example.test',
            'role' => 'admin',
            'status' => 'active',
            'password' => Hash::make('password-sama'),
        ]);

        $this->actingAs($user)->from(route('admin.profile.password.edit'))
            ->put(route('admin.profile.password.update'), [
                'current_password' => 'password-sama',
                'password' => 'password-sama',
                'password_confirmation' => 'password-sama',
            ])->assertRedirect(route('admin.profile.password.edit'))
            ->assertSessionHasErrors('password');
    }

    public function test_admin_password_reset_revokes_employee_remember_token(): void
    {
        $admin = User::create([
            'name' => 'Admin Reset Test', 'username' => 'admin_reset_test',
            'password' => Hash::make('password-admin'), 'role' => 'admin', 'status' => 'active',
        ]);
        $employeeUser = User::create([
            'name' => 'Employee Reset Test', 'username' => 'employee_reset_test',
            'password' => Hash::make('password-lama'), 'role' => 'employee', 'status' => 'active',
        ]);
        $employeeUser->forceFill(['remember_token' => 'employee-token-lama'])->save();
        $schedule = WorkSchedule::create([
            'name' => 'Security Reset Test', 'shift_type' => 'fixed',
            'check_in_start' => '07:00', 'check_in_end' => '08:00',
            'late_tolerance' => 0, 'check_out_start' => '16:00', 'status' => 'active',
        ]);
        $employee = Employee::create([
            'user_id' => $employeeUser->id, 'work_schedule_id' => $schedule->id,
            'employee_number' => 'RESET-SECURITY-TEST', 'position' => 'Petugas Keamanan',
        ]);

        $this->actingAs($admin)->put(route('admin.employees.update', $employee), [
            'name' => $employeeUser->name,
            'username' => $employeeUser->username,
            'email' => '',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
            'status' => 'active',
            'employee_number' => $employee->employee_number,
            'work_schedule_id' => (string) $schedule->id,
            'phone' => '',
            'position' => $employee->position,
            'company' => '',
            'join_date' => '',
        ])->assertRedirect(route('admin.employees.index'));

        $employeeUser->refresh();
        $this->assertTrue(Hash::check('password-baru', $employeeUser->password));
        $this->assertNotSame('employee-token-lama', $employeeUser->remember_token);
    }
}
