<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Collection;
use App\Services\AdminDashboardData;
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

    public function test_admin_dashboard_remains_available_when_chart_collection_is_empty()
    {
        $this->mock(AdminDashboardData::class, function ($mock) {
            $mock->shouldReceive('statistics')->once()->andReturn([
                'active_employees' => 0,
                'attendance_today' => 0,
                'late_today' => 0,
                'pending_leave_requests' => 0,
            ]);
            $mock->shouldReceive('recentAttendances')->once()->andReturn(collect());
            $mock->shouldReceive('recentLeaveRequests')->once()->andReturn(collect());
            $mock->shouldReceive('attendanceChart')->once()->andReturn(new Collection());
        });

        $this->actingAs($this->user('admin'))->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.attendances.index'), false);
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
