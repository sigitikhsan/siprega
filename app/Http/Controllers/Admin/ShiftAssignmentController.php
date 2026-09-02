<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Attendance;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate(['date' => ['nullable', 'date']]);
        $date = $validated['date'] ?? today()->toDateString();
        $employees = $this->securityEmployees()->with('user')->orderBy('employee_number')->get();
        $shiftSchedules = WorkSchedule::where('status', 'active')->whereIn('shift_type', ['day', 'night'])->orderBy('name')->get();
        $assignments = EmployeeShiftAssignment::whereDate('shift_date', $date)->get()->keyBy('employee_id');

        return view('admin.shift-assignments.index', compact('date', 'employees', 'shiftSchedules', 'assignments'));
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
        $allowedEmployees = $this->securityEmployees()->pluck('id')->map(function ($id) { return (string) $id; })->all();

        foreach ($validated['assignments'] ?? [] as $employeeId => $scheduleId) {
            if (!in_array((string) $employeeId, $allowedEmployees, true)) {
                throw ValidationException::withMessages([
                    'assignments' => 'Penugasan memuat pegawai yang tidak aktif atau tidak valid.',
                ]);
            }
            if ($scheduleId && $scheduleId !== 'off' && !in_array((string) $scheduleId, $allowedSchedules, true)) {
                throw ValidationException::withMessages([
                    'assignments.'.$employeeId => 'Jadwal harus berupa Shift Siang/Malam yang masih aktif.',
                ]);
            }

            $attendanceExists = Attendance::where('employee_id', $employeeId)
                ->whereDate('attendance_date', $validated['shift_date'])
                ->exists();
            if ($attendanceExists) {
                $currentAssignment = EmployeeShiftAssignment::where('employee_id', $employeeId)
                    ->whereDate('shift_date', $validated['shift_date'])
                    ->first();
                $currentValue = $currentAssignment && $currentAssignment->is_day_off
                    ? 'off'
                    : (string) optional($currentAssignment)->work_schedule_id;
                if ($currentValue !== (string) ($scheduleId ?: '')) {
                    throw ValidationException::withMessages([
                        'assignments.'.$employeeId => 'Shift tidak dapat diubah karena pegawai sudah memiliki absensi pada tanggal tersebut.',
                    ]);
                }
            }
        }

        DB::transaction(function () use ($validated, $allowedSchedules, $allowedEmployees) {
            foreach ($validated['assignments'] ?? [] as $employeeId => $scheduleId) {
                if (!$scheduleId) {
                    EmployeeShiftAssignment::where('employee_id', $employeeId)->whereDate('shift_date', $validated['shift_date'])->delete();
                    continue;
                }
                if ($scheduleId === 'off') {
                    $employee = Employee::findOrFail($employeeId);
                    $fallbackScheduleId = optional(EmployeeShiftAssignment::where('employee_id', $employeeId)
                        ->whereDate('shift_date', $validated['shift_date'])->first())->work_schedule_id
                        ?: $employee->work_schedule_id;
                    EmployeeShiftAssignment::updateOrCreate(
                        ['employee_id' => $employeeId, 'shift_date' => $validated['shift_date']],
                        ['work_schedule_id' => $fallbackScheduleId, 'is_day_off' => true]
                    );
                    continue;
                }
                EmployeeShiftAssignment::updateOrCreate(
                    ['employee_id' => $employeeId, 'shift_date' => $validated['shift_date']],
                    ['work_schedule_id' => $scheduleId, 'is_day_off' => false]
                );
            }
        });

        return redirect()->route('admin.shift-assignments.index', ['date' => $validated['shift_date']])
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
