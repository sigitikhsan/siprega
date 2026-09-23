<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\LeaveRequest;
use App\Models\Location;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PerformanceRegressionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_bulk_shifts_preserve_ids_notes_and_handle_day_off_and_removal()
    {
        $schedule = $this->schedule();
        $first = $this->employee($schedule);
        $second = $this->employee($schedule);
        $third = $this->employee($schedule);
        $existing = EmployeeShiftAssignment::create(['employee_id' => $first->id, 'work_schedule_id' => $schedule->id,
            'shift_date' => '2038-05-01', 'notes' => 'Catatan harus tetap ada']);
        $removed = EmployeeShiftAssignment::create(['employee_id' => $third->id, 'work_schedule_id' => $schedule->id,
            'shift_date' => '2038-05-01']);

        $this->actingAs($this->user('admin'))->post(route('admin.shift-assignments.store'), [
            'shift_date' => '2038-05-01', 'assignments' => [$first->id => 'off', $second->id => $schedule->id, $third->id => ''],
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertTrue($existing->fresh()->is_day_off);
        $this->assertSame('Catatan harus tetap ada', $existing->fresh()->notes);
        $this->assertSame($schedule->id, $existing->fresh()->work_schedule_id);
        $this->assertDatabaseHas('employee_shift_assignments', ['employee_id' => $second->id, 'shift_date' => '2038-05-01', 'is_day_off' => false]);
        $this->assertNull($removed->fresh());
        $this->post(route('admin.shift-assignments.store'), [
            'shift_date' => '2038-05-01', 'assignments' => [$first->id => $schedule->id],
        ])->assertSessionHasNoErrors();
        $this->assertFalse($existing->fresh()->is_day_off);
        $this->assertSame('Catatan harus tetap ada', $existing->fresh()->notes);
    }

    public function test_bulk_shift_queries_do_not_grow_per_employee()
    {
        $schedule = $this->schedule();
        $assignments = [];
        for ($i = 0; $i < 12; $i++) $assignments[$this->employee($schedule)->id] = $schedule->id;
        $this->actingAs($this->user('admin'));
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $this->post(route('admin.shift-assignments.store'), ['shift_date' => '2038-05-01', 'assignments' => $assignments])
                ->assertSessionHasNoErrors()->assertSessionHas('success');
            $this->assertLessThanOrEqual(6, count(DB::getQueryLog()));
        } finally {
            DB::disableQueryLog();
        }
        $this->assertSame(12, EmployeeShiftAssignment::whereIn('employee_id', array_keys($assignments))->count());
    }

    public function test_bulk_shift_validation_does_not_partially_save_and_still_rejects_non_security()
    {
        $schedule = $this->schedule();
        $security = $this->employee($schedule);
        $cleaner = $this->employee($schedule, 'Petugas Kebersihan');
        $this->actingAs($this->user('admin'))->post(route('admin.shift-assignments.store'), [
            'shift_date' => '2038-05-01', 'assignments' => [$security->id => $schedule->id, $cleaner->id => 'off'],
        ])->assertSessionHasErrors('assignments');
        $this->assertSame(0, EmployeeShiftAssignment::whereIn('employee_id', [$security->id, $cleaner->id])->count());
    }

    public function test_an_attended_shift_blocks_the_entire_batch()
    {
        $schedule = $this->schedule();
        $first = $this->employee($schedule);
        $second = $this->employee($schedule);
        $assignment = EmployeeShiftAssignment::create(['employee_id' => $second->id, 'work_schedule_id' => $schedule->id, 'shift_date' => '2038-05-01']);
        $this->attendance($second, '2038-05-01');
        $this->actingAs($this->user('admin'))->post(route('admin.shift-assignments.store'), [
            'shift_date' => '2038-05-01', 'assignments' => [$first->id => $schedule->id, $second->id => 'off'],
        ])->assertSessionHasErrors('assignments.'.$second->id);
        $this->assertFalse($assignment->fresh()->is_day_off);
        $this->assertDatabaseMissing('employee_shift_assignments', ['employee_id' => $first->id]);
    }

    public function test_leave_overlap_is_inclusive_and_old_history_is_not_loaded()
    {
        $employee = $this->employee($this->schedule());
        $leave = LeaveRequest::create(['employee_id' => $employee->id, 'type' => 'permission', 'start_date' => '2038-05-10',
            'duration' => 3, 'reason' => 'Pengujian batas tanggal', 'status' => 'approved']);
        foreach (['2038-05-10', '2038-05-11', '2038-05-12'] as $date) {
            $this->assertSame($leave->id, $employee->leaveRequests()->overlappingDates($date)->first()->id);
        }
        $this->assertFalse($employee->leaveRequests()->overlappingDates('2038-05-09')->exists());
        $this->assertFalse($employee->leaveRequests()->overlappingDates('2038-05-13')->exists());
        $this->assertTrue($employee->leaveRequests()->overlappingDates('2038-05-01', '2038-05-10')->exists());
        $this->assertTrue($employee->leaveRequests()->overlappingDates('2038-05-12', '2038-05-20')->exists());
        Carbon::setTestNow('2038-05-01');
        try {
            $this->actingAs($employee->user)->post(route('employee.leave-requests.store'), [
                'type' => 'permission', 'start_date' => '2038-05-12', 'duration' => 2, 'reason' => 'Pengajuan yang bertabrakan',
            ])->assertSessionHasErrors('start_date');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_recap_reuses_summary_count_and_preserves_pagination()
    {
        $employee = $this->employee($this->schedule());
        for ($day = 1; $day <= 17; $day++) $this->attendance($employee, sprintf('2038-05-%02d', $day));
        $this->actingAs($this->user('admin'));
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $response = $this->get(route('admin.attendance-recap.index', ['date_from' => '2038-05-01', 'date_to' => '2038-05-17', 'page' => 2]))->assertOk();
            $this->assertSame(17, $response->viewData('summary')['total']);
            $this->assertSame(17, $response->viewData('attendances')->total());
            $this->assertCount(2, $response->viewData('attendances'));
            $this->assertCount(0, $response->viewData('employees'));
            $this->assertCount(0, $response->viewData('schedules'));
            $this->assertLessThanOrEqual(8, count(DB::getQueryLog()));
        } finally {
            DB::disableQueryLog();
        }
    }

    public function test_chart_is_only_loaded_on_admin_dashboard_and_optimized_bootstrap_is_local()
    {
        $employee = $this->employee($this->schedule());
        $this->actingAs($employee->user)->get(route('employee.dashboard'))
            ->assertOk()->assertDontSee('js/admin-chart.js')->assertDontSee('cdn.jsdelivr.net')
            ->assertDontSee('js/app.js')
            ->assertSee('js/employee-attendance.js?id=', false)
            ->assertSee('css/bootstrap-app.min.css');
        $this->flushSession();
        $this->actingAs($this->user('admin'))->get(route('admin.dashboard'))
            ->assertOk()->assertSee('js/admin-chart.js')->assertDontSee('js/app.js')
            ->assertDontSee('js/employee-attendance.js')
            ->assertSee('class="metric-progress"', false)
            ->assertSee('Proporsi pegawai terlambat dari seluruh pegawai aktif')
            ->assertSee('role="img"', false)
            ->assertSee('aria-valuetext=', false)
            ->assertDontSee('new AbortController()', false)
            ->assertDontSee('setInterval(refreshDashboard', false)
            ->assertDontSee('/dashboard/live', false)
            ->assertDontSee('if (!window.Chart) return;', false)
            ->assertDontSee('metric-icon');
        $this->flushSession();
        $this->get(route('login'))->assertOk()->assertDontSee('cdn.jsdelivr.net')
            ->assertDontSee('js/app.js')
            ->assertSee('css/bootstrap-app.min.css');
    }

    private function user($role = 'employee'): User
    {
        return User::create(['name' => 'Performance regression', 'username' => 'perf_'.uniqid(),
            'password' => Hash::make('password123'), 'role' => $role, 'status' => 'active']);
    }

    private function employee(WorkSchedule $schedule, $position = 'Petugas Keamanan'): Employee
    {
        return Employee::create(['user_id' => $this->user()->id, 'employee_number' => 'PERF-'.uniqid(),
            'work_schedule_id' => $schedule->id, 'position' => $position]);
    }

    private function schedule(): WorkSchedule
    {
        return WorkSchedule::create(['name' => 'Performance shift '.uniqid(), 'shift_type' => 'day',
            'check_in_start' => '06:00', 'check_in_end' => '07:00', 'late_tolerance' => 0, 'check_out_start' => '19:00', 'status' => 'active']);
    }

    private function attendance(Employee $employee, $date): Attendance
    {
        $location = Location::create(['name' => 'Performance location '.uniqid(), 'latitude' => -6.2, 'longitude' => 106.8,
            'radius' => 100, 'accuracy_limit' => 50, 'status' => 'active']);
        return Attendance::create(['employee_id' => $employee->id, 'work_schedule_id' => $employee->work_schedule_id,
            'location_id' => $location->id, 'attendance_date' => $date, 'check_in' => $date.' 07:00:00', 'check_in_status' => 'present']);
    }
}
