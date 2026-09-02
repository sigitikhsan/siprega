<?php

namespace Tests\Feature;

use App\Exports\AttendanceRecapExport;
use App\Exports\AttendanceRecapSheet;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AttendanceRecapExportTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_recap_page_and_excel_export_use_the_same_filters()
    {
        Carbon::setTestNow('2035-09-05 10:00:00');
        $admin = $this->admin();
        $location = $this->location();
        $night = $this->schedule('Security Malam', 'night');
        $fixed = $this->schedule('Pegawai Tetap', 'fixed');
        $nightEmployee = $this->employee('Security Rekap', $night);
        $fixedEmployee = $this->employee('Pegawai Rekap', $fixed);

        $nightAttendance = $this->attendance($nightEmployee, $night, $location, '2035-09-02', 'late', true);
        $fixedAttendance = $this->attendance($fixedEmployee, $fixed, $location, '2035-09-02', 'present', false);
        $this->attendance($nightEmployee, $night, $location, '2035-08-20', 'late', true);

        $filters = [
            'date_from' => '2035-09-01',
            'date_to' => '2035-09-03',
        ];

        $this->actingAs($admin)->get(route('admin.attendance-recap.index', $filters))
            ->assertOk()
            ->assertViewHas('attendances', function ($paginator) {
                $this->assertSame(2, $paginator->count());
                return true;
            })
            ->assertViewHas('summary', function ($summary) {
                return $summary === ['total' => 2, 'present' => 1, 'late' => 1, 'complete' => 1, 'incomplete' => 1];
            });

        Excel::fake();
        $this->actingAs($admin)->get(route('admin.attendance-recap.export', $filters))->assertOk();

        Excel::assertDownloaded('rekap-absensi-20350905-100000.xlsx', function (AttendanceRecapExport $export) use ($nightAttendance, $fixedAttendance) {
            $ids = $export->query()->get()->pluck('id')->all();
            $titles = collect($export->sheets())->map->title()->all();
            return collect($ids)->sort()->values()->all() === collect([$nightAttendance->id, $fixedAttendance->id])->sort()->values()->all()
                && $titles === ['Semua Absensi', 'Tepat Waktu', 'Terlambat', 'Shift Siang', 'Shift Malam', 'Jadwal Tetap', 'Sudah Pulang', 'Belum Pulang'];
        });
    }

    public function test_recap_rejects_end_date_before_start_date()
    {
        $this->actingAs($this->admin())->get(route('admin.attendance-recap.index', [
            'date_from' => '2026-09-05',
            'date_to' => '2026-09-01',
        ]))->assertSessionHasErrors('date_to');
    }

    public function test_export_escapes_values_that_excel_could_interpret_as_formulas()
    {
        $location = $this->location();
        $schedule = $this->schedule('Formula Schedule', 'fixed');
        $employee = $this->employee('=HYPERLINK("https://example.test")', $schedule);
        $employee->update(['employee_number' => '+12345']);
        $schedule->update(['name' => '-Formula Schedule']);
        $location->update(['name' => '@Formula Location']);
        $attendance = $this->attendance($employee, $schedule, $location, '2035-09-02', 'present', false);

        $row = (new AttendanceRecapSheet(Attendance::query(), 'Test'))
            ->map($attendance->fresh(['employee.user', 'employee.workSchedule', 'workSchedule', 'location']));

        $this->assertSame("'+12345", $row[1]);
        $this->assertStringStartsWith("'=HYPERLINK", $row[2]);
        $this->assertSame("'-Formula Schedule", $row[4]);
        $this->assertSame("'@Formula Location", $row[5]);
        $this->assertSame('-', $row[7]);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Rekap',
            'username' => 'admin_rekap_'.uniqid(),
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    private function employee(string $name, WorkSchedule $schedule): Employee
    {
        $user = User::create([
            'name' => $name,
            'username' => 'rekap_'.uniqid(),
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'status' => 'active',
        ]);

        return Employee::create([
            'user_id' => $user->id,
            'work_schedule_id' => $schedule->id,
            'employee_number' => 'RKP-'.uniqid(),
        ]);
    }

    private function schedule(string $name, string $type): WorkSchedule
    {
        return WorkSchedule::create([
            'name' => $name.' '.uniqid(),
            'shift_type' => $type,
            'check_in_start' => $type === 'night' ? '19:00' : '07:00',
            'check_in_end' => $type === 'night' ? '19:30' : '08:00',
            'late_tolerance' => 0,
            'check_out_start' => $type === 'night' ? '07:00' : '16:00',
            'status' => 'active',
        ]);
    }

    private function location(): Location
    {
        return Location::create([
            'name' => 'Lokasi Rekap '.uniqid(),
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius' => 100,
            'accuracy_limit' => 50,
            'status' => 'active',
        ]);
    }

    private function attendance(Employee $employee, WorkSchedule $schedule, Location $location, string $date, string $status, bool $complete): Attendance
    {
        return Attendance::create([
            'employee_id' => $employee->id,
            'location_id' => $location->id,
            'work_schedule_id' => $schedule->id,
            'attendance_date' => $date,
            'check_in' => $date.' '.($schedule->shift_type === 'night' ? '19:35:00' : '07:30:00'),
            'check_in_status' => $status,
            'check_out' => $complete ? Carbon::parse($date.' '.$schedule->check_out_start)->addDay($schedule->shift_type === 'night') : null,
            'check_out_status' => $complete ? 'normal' : null,
        ]);
    }
}
