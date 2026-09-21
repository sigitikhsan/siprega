<?php

// Browser fixture: render the actual Blade view with in-memory models only.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'array', 'cache.default' => 'array']);
$request = Illuminate\Http\Request::create('http://dashboard.test/dashboard');
$request->setLaravelSession(app('session')->driver());
$app->instance('request', $request);
app('url')->forceRootUrl('http://dashboard.test');
$scenario = $argv[1] ?? 'default';
if ($scenario === 'admin') {
    auth()->setUser(new App\Models\User(['name' => 'Admin Contoh', 'role' => 'admin', 'status' => 'active']));
    $chart = collect(range(1, 7))->map(function ($day) {
        return ['date' => '2026-09-0'.$day, 'label' => $day.' Sep', 'present' => 2, 'late' => 1, 'total' => 3];
    });
    echo view('admin.dashboard', [
        'errors' => new Illuminate\Support\ViewErrorBag(),
        'statistics' => ['active_employees' => 5, 'attendance_today' => 3, 'late_today' => 1, 'pending_leave_requests' => 0],
        'recentAttendances' => collect(), 'recentLeaveRequests' => collect(), 'attendanceChart' => $chart,
        'chartMaximum' => 3, 'chartTotals' => ['present' => 14, 'late' => 7, 'total' => 21],
    ])->render();
    exit;
}
auth()->setUser(new App\Models\User(['name' => 'Pegawai Contoh', 'role' => 'employee', 'status' => 'active']));
$activeSchedule = new App\Models\WorkSchedule([
    'name' => 'Jadwal Petugas Kebersihan', 'shift_type' => 'fixed', 'status' => 'active',
    'check_in_start' => '05:00:00', 'check_in_end' => '08:00:00', 'check_out_start' => '16:00:00',
]);
$scheduleTicker = [
    'serverNow' => '2026-09-10T10:35:24+07:00', 'timezone' => 'Asia/Jakarta',
    'scheduleName' => $activeSchedule->name, 'shiftType' => 'fixed', 'shiftDate' => '2026-09-10T00:00:00+07:00',
    'startsAt' => '2026-09-10T05:00:00+07:00', 'endsAt' => '2026-09-10T16:00:00+07:00',
    'state' => 'scheduled', 'attendanceStatus' => 'Belum absen masuk',
];
$openAttendance = null;
$checkoutAt = null;
if (in_array($scenario, ['open', 'normal'], true)) {
    $openAttendance = new App\Models\Attendance([
        'attendance_date' => '2026-09-10', 'check_in' => '2026-09-10 07:45:00', 'check_in_status' => 'present',
    ]);
    $checkoutAt = Carbon\Carbon::parse('2026-09-10 16:00:00');
    $scheduleTicker['attendanceStatus'] = 'Sudah masuk';
    if ($scenario === 'normal') $scheduleTicker['serverNow'] = '2026-09-10T17:00:00+07:00';
}
if (in_array($scenario, ['day_off', 'sick', 'leave', 'unavailable'], true)) {
    $scheduleTicker['state'] = $scenario;
    $scheduleTicker['startsAt'] = $scheduleTicker['endsAt'] = null;
}
echo view('employee.dashboard', [
    'errors' => new Illuminate\Support\ViewErrorBag(),
    'activeSchedule' => $activeSchedule, 'scheduleTicker' => $scheduleTicker,
    'employee' => new App\Models\Employee(), 'activeAssignment' => null,
    'todayAttendance' => $openAttendance, 'openAttendance' => $openAttendance, 'checkoutAt' => $checkoutAt,
    'todayLeave' => null, 'isDayOff' => false, 'recentAttendances' => collect(),
])->render();
