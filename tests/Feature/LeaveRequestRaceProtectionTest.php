<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaveRequestRaceProtectionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_overlapping_active_leave_is_rejected_and_uploaded_file_is_cleaned_up(): void
    {
        Storage::fake('local');
        [$user, $employee] = $this->employee();
        LeaveRequest::create([
            'employee_id' => $employee->id,
            'type' => 'permission',
            'start_date' => today()->addDay(),
            'duration' => 2,
            'reason' => 'Pengajuan pertama untuk keperluan keluarga.',
            'status' => 'pending',
        ]);
        $pdf = UploadedFile::fake()->createWithContent('bukti.pdf', "%PDF-1.4\n% test");

        $this->actingAs($user)->post(route('employee.leave-requests.store'), [
            'type' => 'sick',
            'start_date' => today()->addDays(2)->toDateString(),
            'duration' => 1,
            'reason' => 'Pengajuan kedua yang bertabrakan dengan periode pertama.',
            'attachment' => $pdf,
        ])->assertSessionHasErrors('start_date');

        $this->assertSame(1, LeaveRequest::where('employee_id', $employee->id)->count());
        $this->assertSame([], Storage::disk('local')->allFiles('leave-attachments/'.$employee->id));
    }

    public function test_non_overlapping_leave_is_created_normally(): void
    {
        [$user, $employee] = $this->employee();
        LeaveRequest::create([
            'employee_id' => $employee->id,
            'type' => 'permission',
            'start_date' => today()->addDay(),
            'duration' => 1,
            'reason' => 'Pengajuan pertama untuk keperluan keluarga.',
            'status' => 'pending',
        ]);

        $this->actingAs($user)->post(route('employee.leave-requests.store'), [
            'type' => 'sick',
            'start_date' => today()->addDays(3)->toDateString(),
            'duration' => 1,
            'reason' => 'Pengajuan terpisah untuk pemeriksaan kesehatan.',
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, LeaveRequest::where('employee_id', $employee->id)->count());
    }

    private function employee(): array
    {
        $schedule = WorkSchedule::create([
            'name' => 'Leave Race '.uniqid(),
            'shift_type' => 'fixed',
            'check_in_start' => '07:00',
            'check_in_end' => '08:00',
            'late_tolerance' => 0,
            'check_out_start' => '16:00',
            'status' => 'active',
        ]);
        $user = User::create([
            'name' => 'Leave Race Employee',
            'username' => 'leave_race_'.uniqid(),
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'status' => 'active',
        ]);
        $employee = Employee::create([
            'user_id' => $user->id,
            'work_schedule_id' => $schedule->id,
            'employee_number' => 'LEAVE-RACE-'.uniqid(),
        ]);

        return [$user, $employee];
    }
}
