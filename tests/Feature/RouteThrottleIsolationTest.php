<?php

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Tests\TestCase;

class RouteThrottleIsolationTest extends TestCase
{
    /** @dataProvider throttledRoutes */
    public function test_sensitive_routes_have_separate_rate_limit_buckets(string $routeName, string $expectedMiddleware)
    {
        $route = app('router')->getRoutes()->getByName($routeName);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertContains($expectedMiddleware, $route->gatherMiddleware());
    }

    public function throttledRoutes(): array
    {
        return [
            ['employee.attendance.challenge', 'throttle:12,1,attendance-challenge:'],
            ['employee.attendance.check-in', 'throttle:6,1,attendance-check-in:'],
            ['employee.attendance.check-out', 'throttle:6,1,attendance-check-out:'],
            ['employee.profile.update', 'throttle:10,1,employee-profile-update:'],
            ['admin.profile.update', 'throttle:10,1,admin-profile-update:'],
            ['employee.leave-requests.attachment', 'throttle:12,1,employee-leave-attachment:'],
            ['admin.leave-requests.attachment', 'throttle:12,1,admin-leave-attachment:'],
        ];
    }
}
