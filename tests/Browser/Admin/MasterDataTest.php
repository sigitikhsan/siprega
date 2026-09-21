<?php

namespace Tests\Browser\Admin;

use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class MasterDataTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_admin_can_manage_location_through_browser()
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin) {
            $this->loginAsAdmin($browser, $admin)
                ->clickLink('Data Lokasi')->waitForLocation('/admin/locations');
            $this->slow($browser);
            $browser->clickLink('+ Tambah Lokasi')->waitForLocation('/admin/locations/create');
            $this->slow($browser);
            $browser->type('name', 'Lokasi Dusk')
                ->type('latitude', '-6.20000000')
                ->type('longitude', '106.81666600')
                ->type('radius', '150')
                ->type('accuracy_limit', '40')
                ->select('status', 'active');
            $this->slow($browser);
            $browser->press('Simpan')->waitForLocation('/admin/locations')
                ->assertSee('Lokasi Dusk')->assertSee('berhasil ditambahkan');
            $this->slow($browser);
            $browser->clickLink('Edit')->waitUntilMissing('.alert-success')
                ->type('name', 'Lokasi Dusk Diperbarui');
            $this->slow($browser);
            $browser->press('Simpan')->waitForLocation('/admin/locations')
                ->assertSee('Lokasi Dusk Diperbarui');
            $this->slow($browser);
            $browser->press('Hapus')->acceptDialog()->waitForText('berhasil dihapus')
                ->assertDontSee('Lokasi Dusk Diperbarui');
        });
    }

    public function test_admin_can_manage_work_schedule_through_browser()
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin) {
            $this->loginAsAdmin($browser, $admin)
                ->clickLink('Jadwal Kerja')->waitForLocation('/admin/work-schedules');
            $this->slow($browser);
            $browser->clickLink('+ Tambah Jadwal')->waitForLocation('/admin/work-schedules/create')
                ->type('name', 'Jadwal Dusk')
                ->select('shift_type', 'fixed')
                ->type('check_in_start', '07:00')
                ->type('check_in_end', '08:00')
                ->type('late_tolerance', '15')
                ->type('check_out_start', '16:00')
                ->select('status', 'active');
            $this->slow($browser);
            $browser->scrollIntoView('button[type="submit"]');
            $this->slow($browser);
            $browser->press('Simpan')->waitForLocation('/admin/work-schedules')
                ->assertSee('Jadwal Dusk')->assertSee('berhasil ditambahkan');
            $this->slow($browser);
            $browser->clickLink('Edit')->type('name', 'Jadwal Dusk Diperbarui');
            $this->slow($browser);
            $browser->scrollIntoView('button[type="submit"]');
            $this->slow($browser);
            $browser->press('Simpan')->waitForLocation('/admin/work-schedules')
                ->assertSee('Jadwal Dusk Diperbarui');
            $this->slow($browser);
            $browser->press('Hapus')->acceptDialog()->waitForText('berhasil dihapus')
                ->assertDontSee('Jadwal Dusk Diperbarui');
        });
    }

    public function test_admin_can_manage_employee_through_browser()
    {
        $admin = $this->admin();
        $schedule = WorkSchedule::create([
            'name' => 'Jadwal Pegawai Dusk', 'shift_type' => 'fixed',
            'check_in_start' => '07:00', 'check_in_end' => '08:00',
            'late_tolerance' => 10, 'check_out_start' => '16:00', 'status' => 'active',
        ]);

        $this->browse(function (Browser $browser) use ($admin, $schedule) {
            $this->loginAsAdmin($browser, $admin)
                ->clickLink('Data Pegawai')->waitForLocation('/admin/employees');
            $this->slow($browser);
            $browser->clickLink('+ Tambah Pegawai')->waitForLocation('/admin/employees/create')
                ->type('name', 'Pegawai Dusk')
                ->type('employee_number', 'DUSK-001')
                ->type('username', 'pegawai_dusk')
                ->type('email', 'pegawai.dusk@example.test')
                ->type('password', 'password123')
                ->type('password_confirmation', 'password123')
                ->select('work_schedule_id', (string) $schedule->id)
                ->select('status', 'active')
                ->type('position', 'Staf Pengujian');
            $this->slow($browser);
            $browser->scrollIntoView('button[type="submit"]');
            $this->slow($browser);
            $browser->press('Simpan')->waitForLocation('/admin/employees')
                ->assertSee('Pegawai Dusk')->assertSee('berhasil ditambahkan');
            $this->slow($browser);
            $browser->clickLink('Edit')->type('name', 'Pegawai Dusk Diperbarui');
            $this->slow($browser);
            $browser->scrollIntoView('button[type="submit"]');
            $this->slow($browser);
            $browser->press('Simpan')->waitForLocation('/admin/employees')
                ->assertSee('Pegawai Dusk Diperbarui');
            $this->slow($browser);
            $browser->press('Hapus')->acceptDialog()->waitForText('berhasil dihapus')
                ->assertDontSee('Pegawai Dusk Diperbarui');
        });
    }

    private function admin()
    {
        return User::create([
            'name' => 'Admin Master Dusk', 'username' => 'admin_master_dusk',
            'password' => Hash::make('password123'), 'role' => 'admin', 'status' => 'active',
        ]);
    }
}
