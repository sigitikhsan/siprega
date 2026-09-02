<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
            ->latest('start_date')->paginate(10)->appends($filters);
        return view('employee.leave-requests.index', compact('leaveRequests', 'filters'));
    }

    public function create()
    {
        return view('employee.leave-requests.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['permission', 'sick'])],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'duration' => ['required', 'integer', 'min:1', 'max:30'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], ['start_date.after_or_equal' => 'Tanggal mulai tidak boleh sebelum hari ini.', 'attachment.max' => 'Ukuran lampiran maksimal 5 MB.']);
        $employee = $request->user()->employee()->firstOrFail();
        $start = Carbon::parse($validated['start_date']);
        $end = $start->copy()->addDays($validated['duration'] - 1);
        $overlaps = $employee->leaveRequests()->whereIn('status', ['pending', 'approved'])->get()->contains(function ($leave) use ($start, $end) {
            $existingStart = $leave->start_date->copy()->startOfDay();
            $existingEnd = $existingStart->copy()->addDays($leave->duration - 1);
            return $existingStart->lte($end) && $existingEnd->gte($start);
        });
        if ($overlaps) throw ValidationException::withMessages(['start_date' => 'Tanggal tersebut bertabrakan dengan pengajuan lain yang masih aktif.']);

        $path = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->storeAs('leave-attachments/'.$employee->id, Str::uuid().'.'.$file->getClientOriginalExtension(), 'local');
        }
        $employee->leaveRequests()->create([
            'type' => $validated['type'], 'start_date' => $validated['start_date'], 'duration' => $validated['duration'],
            'reason' => $validated['reason'], 'attachment' => $path, 'status' => 'pending',
        ]);
        return redirect()->route('employee.leave-requests.index')->with('success', 'Pengajuan berhasil dikirim dan menunggu keputusan admin.');
    }

    public function show(Request $request, LeaveRequest $leaveRequest)
    {
        $this->authorizeOwner($request, $leaveRequest);
        $leaveRequest->load('reviewer');
        return view('employee.leave-requests.show', compact('leaveRequest'));
    }

    public function destroy(Request $request, LeaveRequest $leaveRequest)
    {
        $this->authorizeOwner($request, $leaveRequest);
        if ($leaveRequest->status !== 'pending') return back()->with('error', 'Hanya pengajuan yang masih menunggu yang dapat dibatalkan.');
        if ($leaveRequest->attachment) Storage::disk('local')->delete($leaveRequest->attachment);
        $leaveRequest->delete();
        return redirect()->route('employee.leave-requests.index')->with('success', 'Pengajuan berhasil dibatalkan.');
    }

    public function download(Request $request, LeaveRequest $leaveRequest)
    {
        if ($request->user()->role !== 'admin') $this->authorizeOwner($request, $leaveRequest);
        abort_unless($leaveRequest->attachment && Storage::disk('local')->exists($leaveRequest->attachment), 404);
        return Storage::disk('local')->download($leaveRequest->attachment);
    }

    private function authorizeOwner(Request $request, LeaveRequest $leaveRequest)
    {
        $employee = $request->user()->employee()->firstOrFail();
        abort_unless($leaveRequest->employee_id === $employee->id, 403);
    }
}
