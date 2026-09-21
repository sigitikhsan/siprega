<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Location;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\EmployeeScheduleResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_position_outside_location_radius_is_rejected()
    {
        Carbon::setTestNow('2026-09-03 09:00:00');
        [$user, $employee] = $this->fixedEmployee();
        $this->location();

        $this->actingAs($user);
        $this->post(route('employee.attendance.check-in'), $this->gps(-6.25, 106.85))
            ->assertSessionHasErrors('location');

        $this->assertDatabaseMissing('attendances', ['employee_id' => $employee->id]);
    }

    public function test_position_with_poor_accuracy_is_rejected()
    {
        Carbon::setTestNow('2026-09-03 09:00:00');
        [$user, $employee] = $this->fixedEmployee();
        $this->location();

        $this->actingAs($user);
        $this->post(route('employee.attendance.check-in'), $this->gps(-6.2, 106.8, 75))
            ->assertSessionHasErrors('accuracy');

        $this->assertDatabaseMissing('attendances', ['employee_id' => $employee->id]);
    }

    public function test_employee_cannot_check_in_twice_for_same_shift_date()
    {
        Carbon::setTestNow('2026-09-03 09:00:00');
        [$user, $employee] = $this->fixedEmployee();
        $this->location();

        $this->actingAs($user);
        $this->post(route('employee.attendance.check-in'), $this->gps())->assertSessionHas('success');
        $this->post(route('employee.attendance.check-in'), $this->gps())->assertSessionHasErrors('attendance');

        $this->assertSame(1, Attendance::where('employee_id', $employee->id)->count());
    }

    public function test_check_in_resolves_the_schedule_inside_the_database_transaction()
    {
        Carbon::setTestNow('2026-09-03 09:00:00');
        [$user] = $this->fixedEmployee();
        $this->location();

        $resolver = new class extends EmployeeScheduleResolver {
            public $transactionLevel = 0;

            public function forCurrentMoment(Employee $employee, Carbon $now): array
            {
                $this->transactionLevel = DB::transactionLevel();

                return parent::forCurrentMoment($employee, $now);
            }
        };
        $this->app->instance(EmployeeScheduleResolver::class, $resolver);

        $this->actingAs($user)
            ->post(route('employee.attendance.check-in'), $this->gps())
            ->assertSessionHas('success');

        $this->assertGreaterThan(0, $resolver->transactionLevel);
    }

    public function test_employee_cannot_check_out_without_check_in()
    {
        Carbon::setTestNow('2026-09-03 17:00:00');
        [$user] = $this->fixedEmployee();
        $this->location();

        $this->actingAs($user);
        $this->patch(route('employee.attendance.check-out'), $this->gps(-6.2, 106.8, 10, 'check_out'))
            ->assertSessionHas('error');
    }

    public function test_employee_cannot_check_in_when_an_older_attendance_is_still_open()
    {
        Carbon::setTestNow('2026-09-03 09:00:00');
        [$user, $employee] = $this->fixedEmployee();
        $location = $this->location();
        $oldAttendance = Attendance::create([
            'employee_id' => $employee->id,
            'location_id' => $location->id,
            'work_schedule_id' => $employee->work_schedule_id,
            'attendance_date' => '2026-08-30',
            'check_in' => '2026-08-30 08:00:00',
            'check_in_status' => 'present',
        ]);

        $this->actingAs($user);
        $this->post(route('employee.attendance.check-in'), $this->gps())
            ->assertSessionHasErrors('attendance');

        $this->assertSame(1, Attendance::where('employee_id', $employee->id)->count());
        $this->assertNull($oldAttendance->fresh()->check_out);
    }

    public function test_dashboard_warns_about_an_older_open_attendance()
    {
        Carbon::setTestNow('2026-09-03 09:00:00');
        [$user, $employee] = $this->fixedEmployee();
        $location = $this->location();
        Attendance::create([
            'employee_id' => $employee->id,
            'location_id' => $location->id,
            'work_schedule_id' => $employee->work_schedule_id,
            'attendance_date' => '2026-08-30',
            'check_in' => '2026-08-30 08:00:00',
            'check_in_status' => 'present',
        ]);

        $this->actingAs($user)->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSee('30/08/2026')
            ->assertSee('yang belum diselesaikan')
            ->assertViewHas('scheduleTicker', function ($ticker) {
                return strpos($ticker['startsAt'], '2026-08-30T05:00:00') === 0;
            });
    }

    public function test_employee_can_check_out_an_attendance_older_than_twenty_four_hours()
    {
        Carbon::setTestNow('2026-09-03 17:00:00');
        [$user, $employee] = $this->fixedEmployee();
        $location = $this->location();
        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'location_id' => $location->id,
            'work_schedule_id' => $employee->work_schedule_id,
            'attendance_date' => '2026-08-30',
            'check_in' => '2026-08-30 08:00:00',
            'check_in_latitude' => -6.2,
            'check_in_longitude' => 106.8,
            'check_in_status' => 'present',
        ]);

        $this->actingAs($user);
        $this->patch(route('employee.attendance.check-out'), $this->gps(-6.2, 106.8, 10, 'check_out'))
            ->assertSessionHas('success');

        $this->assertSame('2026-09-03 17:00:00', $attendance->fresh()->check_out->format('Y-m-d H:i:s'));
    }

    public function test_night_shift_can_check_in_and_check_out_after_date_changes()
    {
        Carbon::setTestNow('2026-09-03 19:31:00');
        $night = $this->schedule('Shift Malam', 'night', '19:00', '19:30', '07:00');
        [$user, $employee] = $this->employee($night);
        $location = $this->location();
        $assignment = EmployeeShiftAssignment::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $night->id,
            'shift_date' => '2026-09-03',
        ]);

        $this->actingAs($user);
        $this->post(route('employee.attendance.check-in'), $this->gps())->assertSessionHas('success');

        $attendance = Attendance::where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame('2026-09-03', $attendance->attendance_date->toDateString());
        $this->assertSame('late', $attendance->check_in_status);
        $this->assertSame($assignment->id, $attendance->shift_assignment_id);
        $this->assertSame($location->id, $attendance->location_id);

        Carbon::setTestNow('2026-09-04 07:05:00');
        $this->patch(route('employee.attendance.check-out'), $this->gps(-6.2, 106.8, 10, 'check_out'))->assertSessionHas('success');

        $attendance->refresh();
        $this->assertSame('2026-09-04 07:05:00', $attendance->check_out->format('Y-m-d H:i:s'));
        $this->assertSame('normal', $attendance->check_out_status);
    }

    public function test_dashboard_checkout_confirmation_uses_next_day_for_night_shift()
    {
        Carbon::setTestNow('2026-09-03 20:00:00');
        $night = $this->schedule('Shift Malam', 'night', '19:00', '19:30', '07:00');
        [$user, $employee] = $this->employee($night);
        $location = $this->location();
        Attendance::create([
            'employee_id' => $employee->id,
            'location_id' => $location->id,
            'work_schedule_id' => $night->id,
            'attendance_date' => '2026-09-03',
            'check_in' => '2026-09-03 19:00:00',
            'check_in_status' => 'present',
        ]);

        $this->actingAs($user)->get(route('employee.dashboard'))
            ->assertOk()
            ->assertViewHas('checkoutAt', function ($checkoutAt) {
                return $checkoutAt->format('Y-m-d H:i:s') === '2026-09-04 07:00:00';
            })
            ->assertSee('id="checkoutModal"', false)
            ->assertViewHas('scheduleTicker', function ($ticker) {
                return strpos($ticker['startsAt'], '2026-09-03T19:00:00') === 0
                    && strpos($ticker['endsAt'], '2026-09-04T07:00:00') === 0;
            })
            ->assertSee('19:00:00')
            ->assertDontSee('Status GPS');
    }

    public function test_early_checkout_still_requires_a_reason_on_the_server()
    {
        Carbon::setTestNow('2026-09-03 09:00:00');
        [$user, $employee] = $this->fixedEmployee();
        $this->location();
        $this->actingAs($user);
        $this->post(route('employee.attendance.check-in'), $this->gps())->assertSessionHas('success');

        $this->get(route('employee.dashboard'))->assertOk()->assertViewHas('checkoutAt', function ($checkoutAt) {
            return $checkoutAt->format('Y-m-d H:i:s') === '2026-09-03 16:00:00';
        });

        Carbon::setTestNow('2026-09-03 15:00:00');
        $this->patch(route('employee.attendance.check-out'), $this->gps(-6.2, 106.8, 10, 'check_out'))
            ->assertSessionHas('error');
        $this->assertNull(Attendance::where('employee_id', $employee->id)->firstOrFail()->check_out);

        $payload = $this->gps(-6.2, 106.8, 10, 'check_out');
        $payload['early_checkout_reason'] = 'Keperluan keluarga mendesak';
        $this->patch(route('employee.attendance.check-out'), $payload)->assertSessionHas('success');
        $attendance = Attendance::where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame('early_checkout', $attendance->check_out_status);
        $this->assertSame($payload['early_checkout_reason'], $attendance->early_checkout_reason);
    }

    public function test_day_off_and_missing_schedule_have_no_ticker_range()
    {
        Carbon::setTestNow('2026-09-10 09:00:00');
        [$user, $employee] = $this->fixedEmployee();
        $employee->update(['uses_shift_schedule' => true]);
        $this->actingAs($user)->get(route('employee.dashboard'))->assertOk()
            ->assertViewHas('scheduleTicker', function ($ticker) {
                return $ticker['state'] === 'unavailable' && $ticker['startsAt'] === null;
            });
        EmployeeShiftAssignment::create([
            'employee_id' => $employee->id,
            'shift_date' => '2026-09-10',
            'is_day_off' => true,
            'work_schedule_id' => $employee->work_schedule_id,
        ]);
        $this->get(route('employee.dashboard'))->assertOk()
            ->assertViewHas('scheduleTicker', function ($ticker) {
                return $ticker['state'] === 'day_off' && $ticker['startsAt'] === null && $ticker['endsAt'] === null;
            });
    }

    private function gps(float $latitude = -6.2, float $longitude = 106.8, float $accuracy = 10, string $action = 'check_in'): array
    {
        $nonce = $this->getJson(route('employee.attendance.challenge', ['action' => $action]))
            ->assertOk()->json('token');

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'captured_at' => now()->toIso8601String(),
            'attendance_nonce' => $nonce,
        ];
    }

    private function fixedEmployee(): array
    {
        return $this->employee($this->schedule('Jadwal Tetap', 'fixed', '05:00', '08:00', '16:00'));
    }

    private function employee(WorkSchedule $schedule): array
    {
        $user = User::create([
            'name' => 'Attendance Flow Employee',
            'username' => 'flow_'.uniqid(),
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'status' => 'active',
        ]);
        $employee = Employee::create([
            'user_id' => $user->id,
            'work_schedule_id' => $schedule->id,
            'employee_number' => 'FLOW-'.uniqid(),
            'position' => 'Pegawai',
        ]);

        return [$user, $employee];
    }

    private function schedule(string $name, string $type, string $start, string $end, string $checkout): WorkSchedule
    {
        return WorkSchedule::create([
            'name' => $name.' '.uniqid(),
            'shift_type' => $type,
            'check_in_start' => $start,
            'check_in_end' => $end,
            'late_tolerance' => 0,
            'check_out_start' => $checkout,
            'status' => 'active',
        ]);
    }

    private function location(): Location
    {
        return Location::create([
            'name' => 'Attendance Flow Location '.uniqid(),
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius' => 100,
            'accuracy_limit' => 50,
            'status' => 'active',
        ]);
    }
}
