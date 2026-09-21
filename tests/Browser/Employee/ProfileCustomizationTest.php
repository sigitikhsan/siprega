<?php

namespace Tests\Browser\Employee;

use App\Models\Employee;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProfileCustomizationTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_profile_is_responsive_and_stored_html_is_not_executed()
    {
        $schedule = WorkSchedule::create([
            'name' => 'Jadwal Profil Dusk', 'shift_type' => 'fixed',
            'check_in_start' => '08:00', 'check_in_end' => '08:30',
            'late_tolerance' => 0, 'check_out_start' => '17:00', 'status' => 'active',
        ]);
        $user = User::create([
            'name' => 'Pegawai Profil Dusk', 'username' => 'pegawai_profil_dusk',
            'password' => Hash::make('password123'), 'role' => 'employee', 'status' => 'active',
        ]);
        Employee::create([
            'user_id' => $user->id, 'work_schedule_id' => $schedule->id,
            'employee_number' => 'PROFILE-DUSK', 'position' => 'Petugas',
            'profile_accent' => 'red;xxx',
            'bio' => '<script>window.profileXssExecuted = true</script>',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('username', 'pegawai_profil_dusk')
                ->type('password', 'password123')
                ->press('Masuk')->waitForLocation('/dashboard')
                ->visit('/profile')->assertSee('Pegawai Profil Dusk');

            foreach ([390, 768, 1440] as $width) {
                $browser->resize($width, 900)->visit('/profile');
                $this->assertTrue((bool) $browser->script('return document.documentElement.scrollWidth <= window.innerWidth;')[0]);
                $this->assertSame('undefined', $browser->script('return typeof window.profileXssExecuted;')[0]);
            }

            $browser->visit('/profile/edit')
                ->assertSee('Edit dan Kustomisasi Profil')
                ->assertPresent('input[name="avatar"]')
                ->assertPresent('textarea[name="bio"]')
                ->assertPresent('input[name="profile_accent"]');
        });
    }
}
