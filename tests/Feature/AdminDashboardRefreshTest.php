<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminDashboardRefreshTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_dashboard_uses_page_load_data_without_polling()
    {
        $this->actingAs($this->user('admin'))->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Tren Absensi 7 Hari')
            ->assertDontSee('setInterval(refreshDashboard', false)
            ->assertDontSee('new AbortController()', false)
            ->assertDontSee('/dashboard/live', false);
    }

    public function test_unused_live_dashboard_route_is_not_registered()
    {
        $this->assertFalse(Route::has('admin.dashboard.live'));
    }

    private function user(string $role): User
    {
        return User::create([
            'name' => ucfirst($role).' Dashboard',
            'username' => $role.'_dashboard_'.uniqid(),
            'password' => Hash::make('password123'),
            'role' => $role,
            'status' => 'active',
        ]);
    }
}
