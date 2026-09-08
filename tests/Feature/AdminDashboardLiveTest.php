<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDashboardLiveTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_fetch_live_dashboard_data()
    {
        $response = $this->actingAs($this->user('admin'))
            ->getJson(route('admin.dashboard.live', ['chart' => 1]));

        $response->assertOk()->assertJsonStructure([
            'statistics' => [
                'active_employees',
                'attendance_today',
                'late_today',
                'pending_leave_requests',
            ],
            'recent_attendances',
            'recent_leave_requests',
            'updated_at',
            'chart' => ['labels', 'present', 'late'],
        ]);
    }

    public function test_employee_cannot_fetch_live_admin_dashboard_data()
    {
        $this->actingAs($this->user('employee'))
            ->getJson(route('admin.dashboard.live'))
            ->assertForbidden();
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
