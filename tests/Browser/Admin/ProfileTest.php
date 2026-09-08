<?php

namespace Tests\Browser\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProfileTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_admin_can_update_profile_and_password_then_login_again()
    {
        $admin = User::create([
            'name' => 'Admin Profil Dusk', 'username' => 'admin_profil_dusk',
            'email' => 'admin.profil@example.test',
            'password' => Hash::make('password123'), 'role' => 'admin', 'status' => 'active',
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $this->loginAsAdmin($browser, $admin);
            $browser->visit('/admin/profile/account')
                ->type('name', 'Admin Profil Diperbarui')
                ->type('email', 'admin.baru@example.test');
            $this->slow($browser);
            $browser->press('Simpan Profil')->waitForLocation('/admin/profile')
                ->assertSee('Admin Profil Diperbarui');
            $this->slow($browser);
            $browser->visit('/admin/profile/password')
                ->type('current_password', 'password123')
                ->type('password', 'password456')
                ->type('password_confirmation', 'password456');
            $this->slow($browser);
            $browser->press('Perbarui Password')->waitForLocation('/admin/profile')
                ->assertSee('Password berhasil diperbarui.')
                ->scrollIntoView('.logout-button');
            $this->slow($browser);
            $browser->press('Keluar')->waitForLocation('/login');
            $this->slow($browser);
            $browser->type('username', 'admin_profil_dusk')
                ->type('password', 'password456')
                ->press('Masuk')->waitForLocation('/admin/dashboard')
                ->assertSee('Dashboard Admin');
        });
    }
}
