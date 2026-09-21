<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

    public function test_employee_can_customize_profile_and_avatar_is_reencoded_as_small_webp()
    {
        Storage::fake('local');
        [$user, $employee] = $this->employeeProfile();

        $this->actingAs($user)->put(route('employee.profile.update'), [
            'name' => 'Pegawai Kustom',
            'username' => $user->username,
            'email' => 'kustom@example.test',
            'phone' => '+62 812-3456-7890',
            'bio' => 'Petugas operasional dan pelayanan publik.',
            'profile_accent' => '#059669',
            'avatar' => UploadedFile::fake()->image('foto-besar.png', 1200, 800),
        ])->assertRedirect(route('employee.profile.show'));

        $employee->refresh();
        Storage::disk('local')->assertExists($employee->avatar_path);
        $image = getimagesize(Storage::disk('local')->path($employee->avatar_path));
        $this->assertSame([320, 320], [$image[0], $image[1]]);
        $this->assertSame('image/webp', $image['mime']);
        $this->assertLessThan(150000, Storage::disk('local')->size($employee->avatar_path));
        $this->assertSame('#059669', $employee->profile_accent);
        $this->assertSame('Petugas operasional dan pelayanan publik.', $employee->bio);

        $this->actingAs($user)->get(route('employee.profile.avatar'))
            ->assertOk()->assertHeader('Content-Type', 'image/webp');
    }

    public function test_profile_rejects_html_script_invalid_color_and_fake_image()
    {
        Storage::fake('local');
        [$user, $employee] = $this->employeeProfile();
        $fakeImage = UploadedFile::fake()->createWithContent('foto.png', '<svg onload="alert(1)"><script>alert(1)</script></svg>');

        $this->actingAs($user)->from(route('employee.profile.edit'))->put(route('employee.profile.update'), [
            'name' => '<script>alert(1)</script>',
            'username' => $user->username,
            'email' => 'aman@example.test',
            'phone' => '081234567890',
            'bio' => '<img src=x onerror=alert(1)>',
            'profile_accent' => 'url(javascript:alert(1))',
            'avatar' => $fakeImage,
        ])->assertRedirect(route('employee.profile.edit'))
            ->assertSessionHasErrors(['name', 'bio', 'profile_accent', 'avatar']);

        $this->assertSame('Profile Employee', $user->fresh()->name);
        $this->assertNull($employee->fresh()->avatar_path);
    }

    private function employeeProfile(): array
    {
        $schedule = WorkSchedule::create(['name' => 'Custom Profile '.uniqid(), 'shift_type' => 'fixed', 'check_in_start' => '08:00', 'check_in_end' => '08:30', 'late_tolerance' => 0, 'check_out_start' => '17:00', 'status' => 'active']);
        $user = User::create(['name' => 'Profile Employee', 'username' => 'custom_'.uniqid(), 'password' => Hash::make('password123'), 'role' => 'employee', 'status' => 'active']);
        $employee = Employee::create(['user_id' => $user->id, 'work_schedule_id' => $schedule->id, 'employee_number' => 'CUS-'.uniqid(), 'position' => 'Petugas']);

        return [$user, $employee];
    }
}
