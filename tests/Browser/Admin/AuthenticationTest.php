<?php

namespace Tests\Browser\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AuthenticationTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_admin_can_login_navigate_dashboard_and_logout()
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin) {
            $this->loginAsAdmin($browser, $admin)
                ->assertPathIs('/admin/dashboard')
                ->assertSee('Dashboard Admin')
                ->assertSee('Pegawai aktif')
                ->clickLink('Data Pegawai')
                ->waitForLocation('/admin/employees');
            $this->slow($browser);
            $browser->assertSee('Data Pegawai')
                ->scrollIntoView('.logout-button');
            $this->slow($browser);
            $browser
                ->press('Keluar')
                ->waitForLocation('/login')
                ->assertPathIs('/login');
            $this->slow($browser);
        });
    }

    private function admin()
    {
        return User::create([
            'name' => 'Admin Browser Test',
            'username' => 'admin_browser',
            'email' => 'admin.browser@example.test',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);
    }
}
