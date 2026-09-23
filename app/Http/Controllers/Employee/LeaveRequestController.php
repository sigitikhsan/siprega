<?php

/**
 * CRUD pengajuan izin/sakit milik pegawai beserta lampiran pendukung.
 * LeaveRequest terikat ke Employee; validasi file memakai AllowedLeaveAttachment dan perubahan memengaruhi dashboard admin.
 * Catatan: file disimpan dengan nama buatan server dan pengajuan tidak boleh saling bertumpang tindih.
 */

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Rules\AllowedLeaveAttachment;
use App\Services\AdminDashboardData;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['type' => ['nullable', Rule::in(['permission', 'sick'])], 'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])]]);
        $employee = $request->user()->employee()->firstOrFail();
        $leaveRequests = $employee->leaveRequests()->with('reviewer')
            ->when($filters['type'] ?? null, function ($query, $type) { $query->where('type', $type); })
            ->when($filters['status'] ?? null, function ($query, $status) { $query->where('status', $status); })
            ->latest('start_date')->latest('id')->paginate(10)->appends($filters);
        return view('employee.leave-requests.request-list', compact('leaveRequests', 'filters'));
    }

    public function create()
    {
        return view('employee.leave-requests.create');
    }

    public function store(Request $request)
    {
        $attachmentRule = new AllowedLeaveAttachment();
        $validated = $request->validate([
            'type' => ['required', Rule::in(['permission', 'sick'])],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'duration' => ['required', 'integer', 'min:1', 'max:30'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:5120', $attachmentRule],
        ], ['start_date.after_or_equal' => 'Tanggal mulai tidak boleh sebelum hari ini.', 'attachment.max' => 'Ukuran lampiran maksimal 5 MB.']);
        $employee = $request->user()->employee()->firstOrFail();
        $start = Carbon::parse($validated['start_date']);
        $end = $start->copy()->addDays($validated['duration'] - 1);

        $path = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->storeAs('leave-attachments/'.$employee->id, Str::uuid().'.'.$attachmentRule->extension(), 'local');
        }

        try {
            DB::transaction(function () use ($employee, $validated, $start, $end, $path) {
                // Serialize leave creation, approval, and check-in for one employee.
                $lockedEmployee = Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
                $overlaps = $lockedEmployee->leaveRequests()->whereIn('status', ['pending', 'approved'])
                    ->overlappingDates($start, $end)->exists();
                if ($overlaps) {
                    throw ValidationException::withMessages([
                        'start_date' => 'Tanggal tersebut bertabrakan dengan pengajuan lain yang masih aktif.',
                    ]);
                }

                $lockedEmployee->leaveRequests()->create([
                    'type' => $validated['type'], 'start_date' => $validated['start_date'], 'duration' => $validated['duration'],
                    'reason' => $validated['reason'], 'attachment' => $path, 'status' => 'pending',
                ]);
            });
        } catch (\Throwable $error) {
            if ($path) Storage::disk('local')->delete($path);
            throw $error;
        }

        app(AdminDashboardData::class)->forgetLeaveRequests();
        return redirect()->route('employee.leave-requests.index')->with('success', 'Pengajuan berhasil dikirim dan menunggu keputusan admin.');
    }

    public function show(Request $request, LeaveRequest $leaveRequest)
    {
        $this->authorizeOwner($request, $leaveRequest);
        $leaveRequest->load('reviewer');
        return view('employee.leave-requests.request-detail', compact('leaveRequest'));
    }

    public function destroy(Request $request, LeaveRequest $leaveRequest)
    {
        $this->authorizeOwner($request, $leaveRequest);
        $result = DB::transaction(function () use ($leaveRequest) {
            Employee::whereKey($leaveRequest->employee_id)->lockForUpdate()->firstOrFail();
            $lockedRequest = LeaveRequest::whereKey($leaveRequest->id)->lockForUpdate()->firstOrFail();
            if ($lockedRequest->status !== 'pending') {
                return ['deleted' => false, 'attachment' => null];
            }
            $attachment = $lockedRequest->attachment;
            $lockedRequest->delete();

            return ['deleted' => true, 'attachment' => $attachment];
        });
        if (!$result['deleted']) {
            return back()->with('error', 'Hanya pengajuan yang masih menunggu yang dapat dibatalkan.');
        }
        if ($result['attachment']) Storage::disk('local')->delete($result['attachment']);
        app(AdminDashboardData::class)->forgetLeaveRequests();
        return redirect()->route('employee.leave-requests.index')->with('success', 'Pengajuan berhasil dibatalkan.');
    }

    public function download(Request $request, LeaveRequest $leaveRequest)
    {
        if ($request->user()->role !== 'admin') $this->authorizeOwner($request, $leaveRequest);
        $expectedPrefix = 'leave-attachments/'.$leaveRequest->employee_id.'/';
        abort_unless(
            $leaveRequest->attachment
            && Str::startsWith($leaveRequest->attachment, $expectedPrefix)
            && Storage::disk('local')->exists($leaveRequest->attachment),
            404
        );
        return Storage::disk('local')->download($leaveRequest->attachment);
    }

    private function authorizeOwner(Request $request, LeaveRequest $leaveRequest)
    {
        $employee = $request->user()->employee()->firstOrFail();
        abort_unless($leaveRequest->employee_id === $employee->id, 403);
    }
}
