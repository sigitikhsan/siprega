<?php

namespace Tests\Performance;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\AdminDashboardData;
use Carbon\Carbon;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Session\SessionManager;
use Illuminate\Session\Store as SessionStore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

/** Explicit opt-in benchmark. All fixture/action database writes are rolled back. */
class ResponseBenchmarkTest extends TestCase
{
    use DatabaseTransactions;

    public function test_measure_responses()
    {
        if (!getenv('BENCHMARK_LABEL')) $this->markTestSkipped('Set BENCHMARK_LABEL to run benchmarks.');
        config(['hashing.bcrypt.rounds' => 10, 'cache.default' => 'array', 'session.driver' => 'array']);
        app('hash')->forgetDrivers();
        Carbon::setTestNow('2036-09-30 09:00:00');
        $prefix = 'bench_'.uniqid();
        $password = Hash::make('Benchmark-only-123');
        $makeUser = function ($suffix, $role = 'employee') use ($prefix, $password) {
            return User::create(['name' => 'Benchmark '.$suffix, 'username' => $prefix.$suffix,
                'password' => $password, 'role' => $role, 'status' => 'active']);
        };
        $admin = $makeUser('admin', 'admin');
        $schedule = WorkSchedule::create(['name' => $prefix.'fixed', 'shift_type' => 'fixed', 'check_in_start' => '05:00',
            'check_in_end' => '08:00', 'check_out_start' => '16:00', 'late_tolerance' => 0, 'status' => 'active']);
        $shift = WorkSchedule::create(['name' => $prefix.'day', 'shift_type' => 'day', 'check_in_start' => '06:00',
            'check_in_end' => '07:00', 'check_out_start' => '19:00', 'late_tolerance' => 0, 'status' => 'active']);
        $location = Location::create(['name' => $prefix.'location', 'latitude' => -6.2, 'longitude' => 106.8,
            'radius' => 100, 'accuracy_limit' => 50, 'status' => 'active']);
        $assignments = [];
        $employees = [];
        for ($i = 0; $i < 60; $i++) {
            $user = $makeUser((string) $i);
            $employee = Employee::create(['user_id' => $user->id, 'employee_number' => $prefix.$i,
                'work_schedule_id' => $schedule->id, 'position' => 'Petugas Keamanan']);
            $employees[] = [$user, $employee];
            $assignments[$employee->id] = (string) $shift->id;
            $rows = [];
            for ($day = 1; $day <= 30; $day++) {
                $date = sprintf('2036-08-%02d', $day);
                $rows[] = ['employee_id' => $employee->id, 'location_id' => $location->id, 'work_schedule_id' => $schedule->id,
                    'attendance_date' => $date, 'check_in' => $date.' 08:00:00', 'check_out' => $date.' 16:00:00',
                    'check_in_status' => $day % 3 === 0 ? 'late' : 'present', 'check_out_status' => 'normal'];
            }
            DB::table('attendances')->insert($rows);
        }
        [$employeeUser, $employee] = $employees[0];
        $leaves = [];
        for ($i = 0; $i < 240; $i++) {
            $leaves[] = ['employee_id' => $employee->id, 'type' => 'permission', 'start_date' => Carbon::parse('2030-01-01')->addDays($i * 3)->toDateString(),
                'duration' => 1, 'reason' => 'Historical benchmark fixture', 'status' => 'approved', 'created_at' => '2030-01-01 00:00:00'];
        }
        DB::table('leave_requests')->insert($leaves);
        $result = [];
        $dateFilters = ['date_from' => '2036-08-01', 'date_to' => '2036-08-30'];
        $cases = [
            'login_page' => [$admin, 'login', []],
            'admin_dashboard' => [$admin, 'admin.dashboard', []],
            'employee_dashboard' => [$employeeUser, 'employee.dashboard', []],
            'employee_list' => [$admin, 'admin.employees.index', ['search' => $prefix]],
            'location_list' => [$admin, 'admin.locations.index', []],
            'schedule_list' => [$admin, 'admin.work-schedules.index', []],
            'attendance_list' => [$admin, 'admin.attendances.index', $dateFilters],
            'attendance_recap' => [$admin, 'admin.attendance-recap.index', $dateFilters],
            'employee_history' => [$employeeUser, 'employee.attendances.index', $dateFilters],
        ];
        foreach ($cases as $name => [$actor, $route, $filters]) {
            $this->actingAs($actor);
            $result[$name] = $this->measure(function () use ($route, $filters) {
                $this->get(route($route, $filters))->assertOk();
            });
        }
        $result['login_success'] = $this->measure(function () use ($employeeUser) {
            app('auth')->forgetGuards();
            $this->post(route('login'), ['username' => $employeeUser->username, 'password' => 'Benchmark-only-123'])
                ->assertRedirect(route('employee.dashboard'));
        });
        $this->actingAs($admin);
        $result['employee_create'] = $this->measure(function () use ($prefix, $schedule) {
            $id = 'bench_new_'.uniqid();
            $this->post(route('admin.employees.store'), ['name' => $id, 'username' => $id, 'employee_number' => $id,
                'password' => 'Benchmark-only-123', 'password_confirmation' => 'Benchmark-only-123',
                'status' => 'active', 'work_schedule_id' => $schedule->id])->assertSessionHasNoErrors()->assertSessionHas('success');
        });
        $result['employee_update'] = $this->measure(function () use ($employee, $employeeUser, $schedule) {
            $this->put(route('admin.employees.update', $employee), ['name' => 'Benchmark updated', 'username' => $employeeUser->username,
                'employee_number' => $employee->employee_number, 'status' => 'active', 'work_schedule_id' => $schedule->id])
                ->assertSessionHasNoErrors()->assertSessionHas('success');
        });
        $result['assign_60_shifts'] = $this->measure(function () use ($assignments) {
            $this->post(route('admin.shift-assignments.store'), ['shift_date' => '2036-10-01', 'assignments' => $assignments])
                ->assertSessionHasNoErrors()->assertSessionHas('success');
        });
        $this->actingAs($employeeUser);
        $gps = function ($action) {
            $nonce = $this->getJson(route('employee.attendance.challenge', ['action' => $action]))->assertOk()->json('token');
            return ['latitude' => -6.2, 'longitude' => 106.8, 'accuracy' => 10,
                'captured_at' => now()->toIso8601String(), 'attendance_nonce' => $nonce];
        };
        $result['check_in_with_challenge'] = $this->measure(function () use ($gps) {
            $this->post(route('employee.attendance.check-in'), $gps('check_in'))->assertSessionHasNoErrors()->assertSessionHas('success');
        });
        Attendance::create(['employee_id' => $employee->id, 'location_id' => $location->id, 'work_schedule_id' => $schedule->id,
            'attendance_date' => '2036-09-30', 'check_in' => '2036-09-30 08:00:00', 'check_in_status' => 'present']);
        Carbon::setTestNow('2036-09-30 17:00:00');
        $result['check_out_with_challenge'] = $this->measure(function () use ($gps) {
            $this->patch(route('employee.attendance.check-out'), $gps('check_out'))->assertSessionHasNoErrors()->assertSessionHas('success');
        });
        $result['leave_submit'] = $this->measure(function () {
            $this->post(route('employee.leave-requests.store'), ['type' => 'permission', 'start_date' => '2036-10-02',
                'duration' => 1, 'reason' => 'Keperluan keluarga benchmark'])->assertSessionHasNoErrors()->assertSessionHas('success');
        });
        $this->actingAs($admin);
        $result['excel_1800_rows_8_sheets'] = $this->measure(function () use ($dateFilters) {
            $response = $this->get(route('admin.attendance-recap.export', $dateFilters))->assertOk();
            $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
            /** @var BinaryFileResponse $download */
            $download = $response->baseResponse;
            ob_start();
            $download->sendContent();
            $bytes = ob_get_clean();
            $this->assertSame('PK', substr($bytes, 0, 2));
        }, 5);
        $output = storage_path('framework/testing/performance');
        if (!is_dir($output)) mkdir($output, 0777, true);
        $label = preg_replace('/[^a-z0-9_-]/i', '', (string) getenv('BENCHMARK_LABEL'));
        /** @var DatabaseManager $database */
        $database = $this->app->make('db');
        /** @var Connection $connection */
        $connection = $database->connection();
        $report = ['environment' => ['php' => PHP_VERSION, 'database' => $connection->getDriverName(), 'bcrypt_cost' => 10,
            'fixtures' => '60 employees, 1800 attendances, 240 historical leave requests',
            'measurement' => 'Warm in-process HTTP kernel incl. rendering; array session/cache cleared per sample; DB writes rolled back; excludes GPS, network, browser, PHP bootstrap and durable commit/fsync'],
            'results' => $result];
        file_put_contents($output.'/'.$label.'.json', json_encode($report, JSON_PRETTY_PRINT));
        foreach ($result as $name => $row) echo sprintf("\n%s: p50 %.2f ms; p95 %.2f ms; queries %s", $name, $row['p50_ms'], $row['p95_ms'], $row['queries']);
        Carbon::setTestNow();
    }

    private function measure(callable $action, int $samples = 12): array
    {
        $times = $counts = $sqlTimes = [];
        /** @var CacheManager $cache */
        $cache = $this->app->make('cache');
        /** @var SessionManager $sessions */
        $sessions = $this->app->make('session');
        /** @var SessionStore $session */
        $session = $sessions->driver();
        /** @var DatabaseManager $database */
        $database = $this->app->make('db');
        /** @var Connection $connection */
        $connection = $database->connection();
        for ($i = -2; $i < $samples; $i++) {
            $cache->store('array')->getStore()->flush();
            $session->flush();
            $connection->beginTransaction();
            $connection->flushQueryLog();
            $connection->enableQueryLog();
            try {
                $start = hrtime(true);
                $action();
                $elapsed = (hrtime(true) - $start) / 1000000;
                $queries = $connection->getQueryLog();
                if ($i >= 0) {
                    $times[] = $elapsed;
                    $counts[] = count($queries);
                    $sqlTimes[] = array_sum(array_column($queries, 'time'));
                }
            } finally {
                $connection->disableQueryLog();
                $connection->rollBack();
            }
        }
        sort($times); sort($counts); sort($sqlTimes);
        return ['samples' => $samples, 'p50_ms' => round($times[(int) floor($samples / 2)], 2),
            'p95_ms' => round($times[(int) ceil($samples * .95) - 1], 2),
            'queries' => $counts[(int) floor($samples / 2)], 'sql_p50_ms' => round($sqlTimes[(int) floor($samples / 2)], 2)];
    }
}
