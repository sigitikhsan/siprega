<?php

/**
 * Mengatur shift harian atau hari libur khusus pegawai keamanan.
 * Berelasi dengan Employee, WorkSchedule, EmployeeShiftAssignment, dan Attendance untuk mencegah perubahan shift yang sudah dipakai.
 * Catatan: urutan lock pegawai lalu absensi/penugasan harus sama dengan check-in agar aman dari race condition.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Attendance;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate(['date' => ['nullable', 'date']]);
        $date = Carbon::parse($validated['date'] ?? today())->toDateString();
        $employees = $this->securityEmployees()->with('user')->orderBy('employee_number')->get();
        $shiftSchedules = WorkSchedule::where('status', 'active')->whereIn('shift_type', ['day', 'night'])->orderBy('name')->get();
        $assignments = EmployeeShiftAssignment::where('shift_date', $date)->get()->keyBy('employee_id');

        return view('admin.shift-assignments.security-shift-schedule', compact('date', 'employees', 'shiftSchedules', 'assignments'));
    }

    public function store(Request $request)
    {
        $allowedSchedules = WorkSchedule::where('status', 'active')->whereIn('shift_type', ['day', 'night'])
            ->pluck('id')->map(function ($id) { return (string) $id; })->all();
        $validated = $request->validate([
            'shift_date' => ['required', 'date'],
            'assignments' => ['nullable', 'array'],
            'assignments.*' => ['nullable', function ($attribute, $value, $fail) use ($allowedSchedules) {
                if ($value !== 'off' && !in_array((string) $value, $allowedSchedules, true)) {
                    $fail('Pilihan harus berupa Shift Siang, Shift Malam, atau Libur yang masih valid.');
                }
            }],
        ]);
        $employees = $this->securityEmployees()->get(['id', 'work_schedule_id'])->keyBy('id');
        $assignments = $validated['assignments'] ?? [];
        $date = Carbon::parse($validated['shift_date'])->toDateString();

        foreach ($assignments as $employeeId => $scheduleId) {
            if (!$employees->has($employeeId)) {
                throw ValidationException::withMessages([
                    'assignments' => 'Penugasan memuat pegawai yang tidak aktif atau tidak valid.',
                ]);
            }
            if ($scheduleId && $scheduleId !== 'off' && !in_array((string) $scheduleId, $allowedSchedules, true)) {
                throw ValidationException::withMessages([
                    'assignments.'.$employeeId => 'Jadwal harus berupa Shift Siang/Malam yang masih aktif.',
                ]);
            }

        }

        DB::transaction(function () use ($assignments, $date) {
            if (!$assignments) return;

            $employeeIds = array_keys($assignments);
            // Use the same parent-row lock order as employee check-in so a shift
            // cannot change while the attendance snapshot is being created.
            $lockedEmployees = Employee::whereIn('id', $employeeIds)
                ->lockForUpdate()->get(['id', 'work_schedule_id'])->keyBy('id');
            $attended = Attendance::whereIn('employee_id', $employeeIds)
                ->where('attendance_date', $date)->lockForUpdate()->pluck('employee_id')->flip();
            $currentAssignments = EmployeeShiftAssignment::whereIn('employee_id', $employeeIds)
                ->where('shift_date', $date)->lockForUpdate()->get()->keyBy('employee_id');
            $rows = $deleteIds = [];
            foreach ($assignments as $employeeId => $scheduleId) {
                $currentAssignment = $currentAssignments->get($employeeId);
                $currentValue = $currentAssignment && $currentAssignment->is_day_off
                    ? 'off'
                    : (string) optional($currentAssignment)->work_schedule_id;
                if ($attended->has($employeeId) && $currentValue !== (string) ($scheduleId ?: '')) {
                    throw ValidationException::withMessages([
                        'assignments.'.$employeeId => 'Shift tidak dapat diubah karena pegawai sudah memiliki absensi pada tanggal tersebut.',
                    ]);
                }
                // Preserve the row ID, notes and timestamps for unchanged assignments.
                if ($currentValue === (string) ($scheduleId ?: '')) continue;
                if (!$scheduleId) {
                    $deleteIds[] = $employeeId;
                    continue;
                }
                $rows[] = [
                    'employee_id' => $employeeId, 'shift_date' => $date,
                    'work_schedule_id' => $scheduleId === 'off'
                        ? (optional($currentAssignment)->work_schedule_id ?: $lockedEmployees->get($employeeId)->work_schedule_id)
                        : $scheduleId,
                    'is_day_off' => $scheduleId === 'off',
                ];
            }
            if ($deleteIds) {
                EmployeeShiftAssignment::whereIn('employee_id', $deleteIds)->where('shift_date', $date)->delete();
            }
            if ($rows) {
                EmployeeShiftAssignment::upsert($rows, ['employee_id', 'shift_date'], ['work_schedule_id', 'is_day_off']);
            }
        });

        return redirect()->route('admin.shift-assignments.index', ['date' => $date])
            ->with('success', 'Penugasan shift berhasil disimpan.');
    }

    private function securityEmployees()
    {
        return Employee::query()
            ->whereHas('user', function ($query) { $query->where('status', 'active'); })
            ->where(function ($query) {
                $query->whereRaw('LOWER(position) LIKE ?', ['%security%'])
                    ->orWhereRaw('LOWER(position) LIKE ?', ['%petugas keamanan%'])
                    ->orWhereRaw('LOWER(position) LIKE ?', ['%satpam%']);
            });
    }
}
