<?php

/**
 * Memproses daftar, persetujuan, dan penolakan pengajuan izin/sakit oleh admin.
 * Menghubungkan LeaveRequest dengan Employee dan Attendance serta menginvalidasi ringkasan dashboard.
 * Catatan: persetujuan harus tetap memeriksa benturan absensi dan dilakukan secara transaksional.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Attendance;
use App\Services\AdminDashboardData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'type' => ['nullable', Rule::in(['permission', 'sick'])],
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));

        $leaveRequests = LeaveRequest::with(['employee.user', 'reviewer'])
            ->when($search, function ($query, $search) {
                $query->whereHas('employee', function ($query) use ($search) {
                    $query->where('employee_number', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['employee_id'] ?? null, function ($query, $employeeId) {
                $query->where('employee_id', $employeeId);
            })
            ->when($filters['type'] ?? null, function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['date_from'] ?? null, function ($query, $date) {
                $query->whereDate('start_date', '>=', $date);
            })
            ->when($filters['date_to'] ?? null, function ($query, $date) {
                $query->whereDate('start_date', '<=', $date);
            })
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest('created_at')
            ->latest('id')
            ->paginate(15)
            ->appends($filters);

        $employees = Employee::with('user')->orderBy('employee_number')->get();
        $pendingCount = LeaveRequest::where('status', 'pending')->count();

        return view('admin.leave-requests.request-list', compact('leaveRequests', 'employees', 'pendingCount', 'filters', 'search'));
    }

    public function show(LeaveRequest $leaveRequest)
    {
        $leaveRequest->load(['employee.user', 'employee.workSchedule', 'reviewer']);

        return view('admin.leave-requests.request-detail', compact('leaveRequest'));
    }

    public function review(Request $request, LeaveRequest $leaveRequest)
    {
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'review_note' => ['nullable', 'required_if:decision,rejected', 'string', 'max:1000'],
        ], [
            'review_note.required_if' => 'Catatan wajib diisi ketika pengajuan ditolak.',
        ]);

        DB::transaction(function () use ($leaveRequest, $validated, $request) {
            // Keep the lock order consistent with check-in and employee leave creation.
            Employee::whereKey($leaveRequest->employee_id)->lockForUpdate()->firstOrFail();
            $lockedRequest = LeaveRequest::whereKey($leaveRequest->id)->lockForUpdate()->firstOrFail();

            if ($lockedRequest->status !== 'pending') {
                throw ValidationException::withMessages([
                    'decision' => 'Pengajuan ini sudah diproses sebelumnya.',
                ]);
            }

            if ($validated['decision'] === 'approved') {
                $endDate = $lockedRequest->start_date->copy()->addDays($lockedRequest->duration - 1);
                $hasAttendance = Attendance::where('employee_id', $lockedRequest->employee_id)
                    ->whereBetween('attendance_date', [$lockedRequest->start_date->toDateString(), $endDate->toDateString()])
                    ->lockForUpdate()
                    ->exists();
                if ($hasAttendance) {
                    throw ValidationException::withMessages([
                        'decision' => 'Pengajuan tidak dapat disetujui karena pegawai sudah memiliki data absensi pada periode tersebut. Koreksi data terlebih dahulu.',
                    ]);
                }
            }

            $lockedRequest->update([
                'status' => $validated['decision'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_note' => $validated['review_note'] ?? null,
            ]);
        });

        $leaveRequest->refresh();
        app(AdminDashboardData::class)->forgetLeaveRequests();

        $message = $validated['decision'] === 'approved'
            ? 'Pengajuan berhasil disetujui.'
            : 'Pengajuan berhasil ditolak.';

        return redirect()->route('admin.leave-requests.show', $leaveRequest)
            ->with('success', $message);
    }
}
