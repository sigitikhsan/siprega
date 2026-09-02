<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        RateLimiter::clear('login:account:unknown-login');
        RateLimiter::clear('login:ip:127.0.0.1');
        parent::tearDown();
    }

    public function test_admin_and_employee_are_redirected_to_their_own_dashboard()
    {
        $admin = $this->user('admin');
        $employeeUser = $this->user('employee');
        $schedule = $this->schedule();
        Employee::create(['user_id' => $employeeUser->id, 'work_schedule_id' => $schedule->id, 'employee_number' => 'LOGIN-'.uniqid()]);

        $this->withSession(['url.intended' => '/dashboard'])->post('/login', [
            'username' => $admin->username, 'password' => 'password123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->post('/logout');
        $this->withSession(['url.intended' => '/admin/dashboard'])->post('/login', [
            'username' => $employeeUser->username, 'password' => 'password123',
        ])->assertRedirect(route('employee.dashboard'));
    }

    public function test_remember_me_creates_recaller_cookie()
    {
        $admin = $this->user('admin');

        $response = $this->post('/login', [
            'username' => $admin->username,
            'password' => 'password123',
            'remember' => '1',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertTrue(auth()->viaRemember() || collect($response->headers->getCookies())->contains(function ($cookie) {
            return strpos($cookie->getName(), 'remember_') === 0;
        }));
    }

    public function test_login_is_blocked_for_five_minutes_after_five_failures()
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/login', ['username' => 'unknown-login', 'password' => 'wrong-password'])
                ->assertSessionHasErrors('username');
        }

        $this->post('/login', ['username' => 'unknown-login', 'password' => 'wrong-password'])
            ->assertRedirect()
            ->assertSessionHasErrors('throttle')
            ->assertSessionHas('retry_after');
        $this->assertGreaterThan(0, RateLimiter::availableIn('login:account:unknown-login'));
        $this->assertLessThanOrEqual(300, RateLimiter::availableIn('login:account:unknown-login'));
    }

    public function test_login_is_globally_limited_per_ip_across_usernames()
    {
        for ($attempt = 1; $attempt <= 20; $attempt++) {
            $this->post('/login', ['username' => 'unknown-'.$attempt, 'password' => 'wrong-password']);
        }

        $this->post('/login', ['username' => 'another-account', 'password' => 'wrong-password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('throttle')
            ->assertSessionHas('retry_after');
    }

    private function user(string $role): User
    {
        return User::create([
            'name' => ucfirst($role).' Login', 'username' => $role.'_login_'.uniqid(),
            'password' => Hash::make('password123'), 'role' => $role, 'status' => 'active',
        ]);
    }

    private function schedule(): WorkSchedule
    {
        return WorkSchedule::create([
            'name' => 'Login Schedule '.uniqid(), 'shift_type' => 'fixed',
            'check_in_start' => '07:00', 'check_in_end' => '08:00', 'late_tolerance' => 0,
            'check_out_start' => '16:00', 'status' => 'active',
        ]);
    }
}
