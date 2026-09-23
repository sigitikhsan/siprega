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
            ['admin.attendance-recap.export', 'throttle:3,1,attendance-export:'],
            ['admin.employees.store', 'throttle:20,1,admin-employees-write:'],
            ['admin.employees.update', 'throttle:20,1,admin-employees-write:'],
            ['admin.employees.destroy', 'throttle:20,1,admin-employees-write:'],
            ['admin.work-schedules.store', 'throttle:20,1,admin-work-schedules-write:'],
            ['admin.locations.store', 'throttle:20,1,admin-locations-write:'],
            ['admin.shift-assignments.store', 'throttle:20,1,admin-shift-assignments-write:'],
            ['admin.attendances.correction.update', 'throttle:10,1,admin-attendance-correction:'],
            ['admin.leave-requests.review', 'throttle:10,1,admin-leave-review:'],
            ['admin.profile.password.update', 'throttle:5,1,admin-password-update:'],
            ['employee.leave-requests.store', 'throttle:6,1,employee-leave-store:'],
            ['employee.leave-requests.destroy', 'throttle:10,1,employee-leave-destroy:'],
            ['employee.profile.password.update', 'throttle:5,1,employee-password-update:'],
        ];
    }
}
