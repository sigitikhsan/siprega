<?php

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

Route::get('/leave-requests/{leaveRequest}/attachment', [EmployeeLeaveRequestController::class, 'download'])
    ->middleware('auth')->name('leave-requests.attachment');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('employees', EmployeeController::class)->except('show');
    Route::resource('work-schedules', WorkScheduleController::class)->except('show');
    Route::get('/shift-assignments', [ShiftAssignmentController::class, 'index'])->name('shift-assignments.index');
    Route::post('/shift-assignments', [ShiftAssignmentController::class, 'store'])->name('shift-assignments.store');
    Route::resource('locations', LocationController::class)->except('show');
    Route::get('/attendances', [AttendanceController::class, 'index'])->name('attendances.index');
    Route::get('/attendances/export', [AttendanceController::class, 'export'])->name('attendances.export');
    Route::get('/attendance-recap', [AttendanceController::class, 'index'])->name('attendance-recap.index');
    Route::get('/attendance-recap/export', [AttendanceController::class, 'export'])->name('attendance-recap.export');
    Route::get('/attendances/{attendance}/correction', [AttendanceCorrectionController::class, 'edit'])->name('attendances.correction.edit');
    Route::patch('/attendances/{attendance}/correction', [AttendanceCorrectionController::class, 'update'])->name('attendances.correction.update');
    Route::get('/attendances/{attendance}', [AttendanceController::class, 'show'])->name('attendances.show');
    Route::get('/leave-requests', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
    Route::get('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'show'])->name('leave-requests.show');
    Route::patch('/leave-requests/{leaveRequest}/review', [LeaveRequestController::class, 'review'])->name('leave-requests.review');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/account', [ProfileController::class, 'editAccount'])->name('profile.account');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/password', [ProfileController::class, 'editPassword'])->name('profile.password.edit');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
});

Route::middleware(['auth', 'employee'])->group(function () {
    Route::get('/dashboard', [EmployeeDashboardController::class, 'index'])->name('employee.dashboard');
    Route::post('/attendance/check-in', [EmployeeAttendanceController::class, 'checkIn'])->middleware('throttle:6,1')->name('employee.attendance.check-in');
    Route::patch('/attendance/check-out', [EmployeeAttendanceController::class, 'checkOut'])->middleware('throttle:6,1')->name('employee.attendance.check-out');
    Route::get('/attendance-history', [AttendanceHistoryController::class, 'index'])->name('employee.attendances.index');
    Route::get('/attendance-history/{attendance}', [AttendanceHistoryController::class, 'show'])->name('employee.attendances.show');
    Route::get('/my-leave-requests', [EmployeeLeaveRequestController::class, 'index'])->name('employee.leave-requests.index');
    Route::get('/my-leave-requests/create', [EmployeeLeaveRequestController::class, 'create'])->name('employee.leave-requests.create');
    Route::post('/my-leave-requests', [EmployeeLeaveRequestController::class, 'store'])->name('employee.leave-requests.store');
    Route::get('/my-leave-requests/{leaveRequest}', [EmployeeLeaveRequestController::class, 'show'])->name('employee.leave-requests.show');
    Route::delete('/my-leave-requests/{leaveRequest}', [EmployeeLeaveRequestController::class, 'destroy'])->name('employee.leave-requests.destroy');
    Route::get('/profile', [EmployeeProfileController::class, 'show'])->name('employee.profile.show');
    Route::get('/profile/edit', [EmployeeProfileController::class, 'edit'])->name('employee.profile.edit');
    Route::put('/profile', [EmployeeProfileController::class, 'update'])->name('employee.profile.update');
    Route::get('/profile/password', [EmployeeProfileController::class, 'editPassword'])->name('employee.profile.password.edit');
    Route::put('/profile/password', [EmployeeProfileController::class, 'updatePassword'])->name('employee.profile.password.update');
});
