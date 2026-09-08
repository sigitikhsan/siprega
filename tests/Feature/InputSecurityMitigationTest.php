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

class InputSecurityMitigationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_html_disguised_as_jpg_is_rejected()
    {
        Storage::fake('local');
        $user = $this->employeeUser();
        $file = UploadedFile::fake()->createWithContent('surat.jpg', '<html><script>alert(1)</script></html>');

        $this->actingAs($user)->post(route('employee.leave-requests.store'), [
            'type' => 'sick',
            'start_date' => today()->toDateString(),
            'duration' => 1,
            'reason' => 'Sedang sakit dan membutuhkan waktu untuk beristirahat.',
            'attachment' => $file,
        ])->assertSessionHasErrors('attachment');

        $this->assertSame([], Storage::disk('local')->allFiles('leave-attachments'));
    }

    public function test_email_with_newline_header_is_rejected()
    {
        $admin = User::create([
            'name' => 'Admin Input Security',
            'username' => 'admin_input_'.uniqid(),
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin)->put(route('admin.profile.update'), [
            'name' => $admin->name,
            'username' => $admin->username,
            'email' => "admin@example.test\r\nBcc:attacker@example.test",
        ])->assertSessionHasErrors('email');
    }

    public function test_valid_png_is_stored_using_server_detected_extension()
    {
        Storage::fake('local');
        $user = $this->employeeUser();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $file = UploadedFile::fake()->createWithContent('surat.php.png', $png);

        $this->actingAs($user)->post(route('employee.leave-requests.store'), [
            'type' => 'permission',
            'start_date' => today()->toDateString(),
            'duration' => 1,
            'reason' => 'Mengajukan izin untuk keperluan keluarga yang penting.',
            'attachment' => $file,
        ])->assertSessionHasNoErrors();

        $attachment = $user->employee->leaveRequests()->firstOrFail()->attachment;
        $this->assertStringEndsWith('.png', $attachment);
        $this->assertStringNotContainsString('.php.', $attachment);
        Storage::disk('local')->assertExists($attachment);
    }

    private function employeeUser(): User
    {
        $schedule = WorkSchedule::create([
            'name' => 'Upload Security '.uniqid(),
            'shift_type' => 'fixed',
            'check_in_start' => '07:00',
            'check_in_end' => '08:00',
            'late_tolerance' => 0,
            'check_out_start' => '16:00',
            'status' => 'active',
        ]);
        $user = User::create([
            'name' => 'Upload Security Employee',
            'username' => 'upload_security_'.uniqid(),
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'status' => 'active',
        ]);
        Employee::create([
            'user_id' => $user->id,
            'work_schedule_id' => $schedule->id,
            'employee_number' => 'UPLOAD-'.uniqid(),
        ]);

        return $user;
    }
}
