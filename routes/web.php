<?php

/*
  Peta utama endpoint web SiPrega untuk autentikasi, area admin, dan area pegawai.
  Route menghubungkan URL ke controller serta middleware role; aturan bisnis tetap berada di controller/service.
  Catatan: pertahankan middleware auth/admin/employee saat menambah endpoint agar otorisasi tidak terlewati.
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\WorkScheduleController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\LeaveRequestController;
use App\Http\Controllers\Admin\AttendanceCorrectionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ShiftAssignmentController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Employee\AttendanceController as EmployeeAttendanceController;
use App\Http\Controllers\Employee\AttendanceHistoryController;
use App\Http\Controllers\Employee\LeaveRequestController as EmployeeLeaveRequestController;
use App\Http\Controllers\Employee\ProfileController as EmployeeProfileController;

Route::redirect('/', '/login');

Route::get('/login', [LoginController::class, 'showLoginForm'])
    ->name('login');

Route::post('/login', [LoginController::class, 'login']);

Route::post('/logout', [LoginController::class, 'logout'])
    ->name('logout');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('employees', EmployeeController::class)->only(['index', 'create', 'edit']);
    Route::resource('employees', EmployeeController::class)->only(['store', 'update', 'destroy'])
        ->middleware('throttle:20,1,admin-employees-write:');
    Route::resource('work-schedules', WorkScheduleController::class)->only(['index', 'create', 'edit']);
    Route::resource('work-schedules', WorkScheduleController::class)->only(['store', 'update', 'destroy'])
        ->middleware('throttle:20,1,admin-work-schedules-write:');
    Route::get('/shift-assignments', [ShiftAssignmentController::class, 'index'])->name('shift-assignments.index');
    Route::post('/shift-assignments', [ShiftAssignmentController::class, 'store'])
        ->middleware('throttle:20,1,admin-shift-assignments-write:')->name('shift-assignments.store');
    Route::resource('locations', LocationController::class)->only(['index', 'create', 'edit']);
    Route::resource('locations', LocationController::class)->only(['store', 'update', 'destroy'])
        ->middleware('throttle:20,1,admin-locations-write:');
    Route::get('/attendances', [AttendanceController::class, 'index'])->name('attendances.index');
    Route::get('/attendance-recap', [AttendanceController::class, 'index'])->name('attendance-recap.index');
    Route::get('/attendance-recap/export', [AttendanceController::class, 'export'])->middleware('throttle:3,1,attendance-export:')->name('attendance-recap.export');
    Route::get('/attendances/{attendance}/correction', [AttendanceCorrectionController::class, 'edit'])->name('attendances.correction.edit');
    Route::patch('/attendances/{attendance}/correction', [AttendanceCorrectionController::class, 'update'])
        ->middleware('throttle:10,1,admin-attendance-correction:')->name('attendances.correction.update');
    Route::get('/attendances/{attendance}', [AttendanceController::class, 'show'])->name('attendances.show');
    Route::get('/leave-requests', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
    Route::get('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'show'])->name('leave-requests.show');
    Route::get('/leave-requests/{leaveRequest}/attachment', [EmployeeLeaveRequestController::class, 'download'])
        ->middleware('throttle:12,1,admin-leave-attachment:')->name('leave-requests.attachment');
    Route::patch('/leave-requests/{leaveRequest}/review', [LeaveRequestController::class, 'review'])
        ->middleware('throttle:10,1,admin-leave-review:')->name('leave-requests.review');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/avatar', [ProfileController::class, 'avatar'])->name('profile.avatar');
    Route::get('/profile/account', [ProfileController::class, 'editAccount'])->name('profile.account');
    Route::put('/profile', [ProfileController::class, 'update'])->middleware('throttle:10,1,admin-profile-update:')->name('profile.update');
    Route::get('/profile/password', [ProfileController::class, 'editPassword'])->name('profile.password.edit');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])
        ->middleware('throttle:5,1,admin-password-update:')->name('profile.password.update');
});

Route::middleware(['auth', 'employee'])->group(function () {
    Route::get('/dashboard', [EmployeeDashboardController::class, 'index'])->name('employee.dashboard');
    Route::get('/attendance/challenge', [EmployeeAttendanceController::class, 'challenge'])->middleware('throttle:12,1,attendance-challenge:')->name('employee.attendance.challenge');
    Route::post('/attendance/check-in', [EmployeeAttendanceController::class, 'checkIn'])->middleware('throttle:6,1,attendance-check-in:')->name('employee.attendance.check-in');
    Route::patch('/attendance/check-out', [EmployeeAttendanceController::class, 'checkOut'])->middleware('throttle:6,1,attendance-check-out:')->name('employee.attendance.check-out');
    Route::get('/attendance-history', [AttendanceHistoryController::class, 'index'])->name('employee.attendances.index');
    Route::get('/attendance-history/{attendance}', [AttendanceHistoryController::class, 'show'])->name('employee.attendances.show');
    Route::get('/my-leave-requests', [EmployeeLeaveRequestController::class, 'index'])->name('employee.leave-requests.index');
    Route::get('/my-leave-requests/create', [EmployeeLeaveRequestController::class, 'create'])->name('employee.leave-requests.create');
    Route::post('/my-leave-requests', [EmployeeLeaveRequestController::class, 'store'])
        ->middleware('throttle:6,1,employee-leave-store:')->name('employee.leave-requests.store');
    Route::get('/my-leave-requests/{leaveRequest}', [EmployeeLeaveRequestController::class, 'show'])->name('employee.leave-requests.show');
    Route::get('/my-leave-requests/{leaveRequest}/attachment', [EmployeeLeaveRequestController::class, 'download'])
        ->middleware('throttle:12,1,employee-leave-attachment:')->name('employee.leave-requests.attachment');
    Route::delete('/my-leave-requests/{leaveRequest}', [EmployeeLeaveRequestController::class, 'destroy'])
        ->middleware('throttle:10,1,employee-leave-destroy:')->name('employee.leave-requests.destroy');
    Route::get('/profile', [EmployeeProfileController::class, 'show'])->name('employee.profile.show');
    Route::get('/profile/avatar', [EmployeeProfileController::class, 'avatar'])->name('employee.profile.avatar');
    Route::get('/profile/edit', [EmployeeProfileController::class, 'edit'])->name('employee.profile.edit');
    Route::put('/profile', [EmployeeProfileController::class, 'update'])->middleware('throttle:10,1,employee-profile-update:')->name('employee.profile.update');
    Route::get('/profile/password', [EmployeeProfileController::class, 'editPassword'])->name('employee.profile.password.edit');
    Route::put('/profile/password', [EmployeeProfileController::class, 'updatePassword'])
        ->middleware('throttle:5,1,employee-password-update:')->name('employee.profile.password.update');
});
