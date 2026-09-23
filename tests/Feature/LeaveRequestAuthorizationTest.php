<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

    public function test_attachment_is_private_to_owner_and_admin_and_rejects_invalid_storage_path()
    {
        Storage::fake('local');
        $schedule = WorkSchedule::create(['name' => 'Attachment Test '.uniqid(), 'shift_type' => 'fixed', 'check_in_start' => '08:00', 'check_in_end' => '08:30', 'late_tolerance' => 0, 'check_out_start' => '17:00', 'status' => 'active']);
        [$owner, $employee] = $this->employee($schedule);
        [$otherUser] = $this->employee($schedule);
        $admin = User::create(['name' => 'Leave Admin', 'username' => 'leave_admin_'.uniqid(), 'password' => Hash::make('password123'), 'role' => 'admin', 'status' => 'active']);
        $path = 'leave-attachments/'.$employee->id.'/evidence.pdf';
        Storage::disk('local')->put($path, '%PDF-test');
        $leave = LeaveRequest::create(['employee_id' => $employee->id, 'type' => 'sick', 'start_date' => today()->addDay(), 'duration' => 1, 'reason' => 'Memerlukan pemeriksaan kesehatan', 'attachment' => $path, 'status' => 'pending']);

        $this->actingAs($owner)->get(route('employee.leave-requests.attachment', $leave))->assertOk();
        $this->flushSession();
        $this->actingAs($otherUser)->get(route('employee.leave-requests.attachment', $leave))->assertForbidden();
        $this->flushSession();
        $this->actingAs($admin)->get(route('admin.leave-requests.attachment', $leave))->assertOk();

        Storage::disk('local')->put('outside/evidence.pdf', '%PDF-outside');
        $leave->update(['attachment' => 'outside/evidence.pdf']);
        $this->actingAs($admin)->get(route('admin.leave-requests.attachment', $leave))->assertNotFound();
    }

    public function test_inactive_employee_session_cannot_download_attachment()
    {
        Storage::fake('local');
        $schedule = WorkSchedule::create(['name' => 'Inactive Attachment '.uniqid(), 'shift_type' => 'fixed', 'check_in_start' => '08:00', 'check_in_end' => '08:30', 'late_tolerance' => 0, 'check_out_start' => '17:00', 'status' => 'active']);
        [$user, $employee] = $this->employee($schedule);
        $path = 'leave-attachments/'.$employee->id.'/evidence.pdf';
        Storage::disk('local')->put($path, '%PDF-test');
        $leave = LeaveRequest::create(['employee_id' => $employee->id, 'type' => 'permission', 'start_date' => today()->addDay(), 'duration' => 1, 'reason' => 'Keperluan keluarga yang penting', 'attachment' => $path, 'status' => 'pending']);
        $user->update(['status' => 'inactive']);

        $this->actingAs($user)->get(route('employee.leave-requests.attachment', $leave))
            ->assertRedirect(route('login'));
    }

    private function employee(WorkSchedule $schedule)
    {
        $user = User::create(['name' => 'Leave Employee', 'username' => 'leave_'.uniqid(), 'password' => Hash::make('password123'), 'role' => 'employee', 'status' => 'active']);
        $employee = Employee::create(['user_id' => $user->id, 'work_schedule_id' => $schedule->id, 'employee_number' => 'LEV-'.uniqid()]);
        return [$user, $employee];
    }
}
