<?php

namespace Tests\Browser\Admin;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Location;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class OperationsTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_admin_can_assign_security_shift_and_day_off()
    {
        $admin = $this->admin();
        $day = $this->schedule('Shift Siang Dusk', 'day', '07:00', '08:00', '19:00');
        $this->schedule('Shift Malam Dusk', 'night', '19:00', '19:30', '07:00');
        $security = $this->employee('Security Dusk', $day, true, 'Petugas Keamanan');
        $date = now()->addDays(2)->toDateString();

        $this->browse(function (Browser $browser) use ($admin, $day, $security, $date) {
            $this->loginAsAdmin($browser, $admin);
            $browser->visit('/admin/shift-assignments?date='.$date)
                ->assertSee('Security Dusk')
                ->select('assignments['.$security->id.']', (string) $day->id);
            $this->slow($browser);
            $browser->press('Simpan Penugasan')->waitForText('berhasil disimpan');
            $this->slow($browser);
            $browser->select('assignments['.$security->id.']', 'off');
            $this->slow($browser);
            $browser->press('Simpan Penugasan')->waitForText('berhasil disimpan')
                ->assertSelected('assignments['.$security->id.']', 'off');
        });
    }

    public function test_admin_can_filter_view_and_correct_attendance()
    {
        $admin = $this->admin();
        $schedule = $this->schedule('Jadwal Absensi Dusk');
        $location = $this->location();
        $employee = $this->employee('Pegawai Absensi Dusk', $schedule);
        $date = now()->subDay()->toDateString();
        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'location_id' => $location->id,
            'work_schedule_id' => $schedule->id,
            'attendance_date' => $date,
            'check_in' => $date.' 07:15:00',
            'check_in_status' => 'present',
        ]);

        $this->browse(function (Browser $browser) use ($admin, $attendance, $date) {
            $this->loginAsAdmin($browser, $admin);
            $browser->visit('/admin/attendances')
                ->type('search', 'Pegawai Absensi Dusk')
                ->select('check_in_status', 'present');
            $this->slow($browser);
            $browser->press('Terapkan')->waitForText('Pegawai Absensi Dusk')
                ->clickLink('Detail')->waitForLocation('/admin/attendances/'.$attendance->id)
                ->assertSee('Pegawai Absensi Dusk');
            $this->slow($browser);
            $browser->clickLink('Koreksi Absensi')
                ->waitForLocation('/admin/attendances/'.$attendance->id.'/correction');
            $browser->script([
                "document.querySelector('[name=\"check_in\"]').value = '{$date}T07:20'",
                "document.querySelector('[name=\"check_out\"]').value = '{$date}T16:10'",
            ]);
            $browser->type('reason', 'Koreksi otomatis melalui pengujian browser');
            $this->slow($browser);
            $browser->scrollIntoView('button[type="submit"]');
            $this->slow($browser);
            $browser->press('Simpan Koreksi')->acceptDialog()
                ->waitForLocation('/admin/attendances/'.$attendance->id)
                ->assertSee('berhasil dikoreksi')
                ->assertSee('Riwayat Koreksi');
        });
    }

    public function test_admin_can_review_leave_request_and_open_recap()
    {
        $admin = $this->admin();
        $schedule = $this->schedule('Jadwal Izin Dusk');
        $employee = $this->employee('Pegawai Izin Dusk', $schedule);
        $leave = LeaveRequest::create([
            'employee_id' => $employee->id,
            'type' => 'permission',
            'start_date' => now()->addDays(5)->toDateString(),
            'duration' => 1,
            'reason' => 'Keperluan keluarga untuk pengujian Dusk',
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) use ($admin, $leave) {
            $this->loginAsAdmin($browser, $admin);
            $browser->visit('/admin/leave-requests')
                ->assertSee('Pegawai Izin Dusk')
                ->clickLink('Detail')->waitForLocation('/admin/leave-requests/'.$leave->id);
            $this->slow($browser);
            $browser->press('Setujui')->acceptDialog()
                ->waitForText('Pengajuan berhasil disetujui.')
                ->assertSee('Disetujui');
            $this->slow($browser);
            $browser->clickLink('Rekap Absensi')->waitForLocation('/admin/attendance-recap')
                ->type('date_from', now()->startOfMonth()->toDateString())
                ->type('date_to', now()->endOfMonth()->toDateString());
            $this->slow($browser);
            $browser->press('Terapkan')->assertSee('File Excel berisi worksheet terpisah');
        });
    }

    private function admin()
    {
        return User::create([
            'name' => 'Admin Operasional Dusk', 'username' => 'admin_operasional_dusk',
            'password' => Hash::make('password123'), 'role' => 'admin', 'status' => 'active',
        ]);
    }

    private function schedule($name, $type = 'fixed', $start = '07:00', $end = '08:00', $checkout = '16:00')
    {
        return WorkSchedule::create([
            'name' => $name, 'shift_type' => $type, 'check_in_start' => $start,
            'check_in_end' => $end, 'late_tolerance' => 10,
            'check_out_start' => $checkout, 'status' => 'active',
        ]);
    }

    private function employee($name, WorkSchedule $schedule, $usesShift = false, $position = 'Staf')
    {
        $user = User::create([
            'name' => $name, 'username' => 'dusk_'.strtolower(str_replace(' ', '_', $name)),
            'password' => Hash::make('password123'), 'role' => 'employee', 'status' => 'active',
        ]);

        return Employee::create([
            'user_id' => $user->id, 'work_schedule_id' => $schedule->id,
            'uses_shift_schedule' => $usesShift, 'employee_number' => 'DUSK-'.uniqid(),
            'position' => $position,
        ]);
    }

    private function location()
    {
        return Location::create([
            'name' => 'Lokasi Operasional Dusk', 'latitude' => -6.2,
            'longitude' => 106.8, 'radius' => 100,
            'accuracy_limit' => 50, 'status' => 'active',
        ]);
    }
}
