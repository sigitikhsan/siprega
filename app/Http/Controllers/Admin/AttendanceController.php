<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\AttendanceRecapExport;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Location;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->validateFilters($request, $request->routeIs('admin.attendance-recap.*'));

        $search = trim((string) ($filters['search'] ?? ''));
        $filteredQuery = $this->filteredQuery($filters);
        $summaryRow = (clone $filteredQuery)->reorder()->selectRaw(
            "COUNT(*) as total,
            SUM(CASE WHEN check_in_status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN check_in_status = 'late' THEN 1 ELSE 0 END) as late_count,
            SUM(CASE WHEN check_out IS NOT NULL THEN 1 ELSE 0 END) as complete_count"
        )->first();
        $summary = [
            'total' => (int) ($summaryRow->total ?? 0),
            'present' => (int) ($summaryRow->present_count ?? 0),
            'late' => (int) ($summaryRow->late_count ?? 0),
            'complete' => (int) ($summaryRow->complete_count ?? 0),
        ];
        $summary['incomplete'] = $summary['total'] - $summary['complete'];

        $attendances = $filteredQuery
            ->paginate(15)
            ->appends($filters);

        $employees = Employee::with('user')->orderBy('employee_number')->get();
        $locations = Location::orderBy('name')->get();
        $schedules = WorkSchedule::orderBy('name')->get();

        return view('admin.attendances.index', compact('attendances', 'employees', 'locations', 'schedules', 'filters', 'search', 'summary'));
    }

    public function export(Request $request)
    {
        $filters = $this->validateFilters($request, $request->routeIs('admin.attendance-recap.*'), true);
        $fileName = 'rekap-absensi-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new AttendanceRecapExport($this->filteredQuery($filters)), $fileName);
    }

    private function validateFilters(Request $request, bool $datesOnly = false, bool $forExport = false): array
    {
        $rules = [
            'date_from' => [$forExport ? 'required' : 'nullable', 'date'],
            'date_to' => [$forExport ? 'required' : 'nullable', 'date', 'after_or_equal:date_from'],
        ];

        if (!$datesOnly) {
            $rules += [
                'search' => ['nullable', 'string', 'max:100'],
                'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
                'location_id' => ['nullable', 'integer', 'exists:locations,id'],
                'work_schedule_id' => ['nullable', 'integer', 'exists:work_schedules,id'],
                'shift_type' => ['nullable', 'in:fixed,day,night'],
                'check_in_status' => ['nullable', 'in:present,late'],
                'completion' => ['nullable', 'in:complete,incomplete'],
            ];
        }

        $validated = $request->validate($rules);

        if ($forExport && Carbon::parse($validated['date_from'])->diffInDays(Carbon::parse($validated['date_to'])) > 30) {
            throw ValidationException::withMessages([
                'date_to' => 'Rentang ekspor maksimal 31 hari.',
            ]);
        }

        return $validated;
    }

    private function filteredQuery(array $filters)
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return Attendance::with(['employee.user', 'employee.workSchedule', 'workSchedule', 'location'])
            ->when($search, function ($query, $search) {
                $query->whereHas('employee', function ($query) use ($search) {
                    $query->where('employee_number', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['date_from'] ?? null, function ($query, $date) {
                $query->whereDate('attendance_date', '>=', $date);
            })
            ->when($filters['date_to'] ?? null, function ($query, $date) {
                $query->whereDate('attendance_date', '<=', $date);
            })
            ->when($filters['employee_id'] ?? null, function ($query, $employeeId) {
                $query->where('employee_id', $employeeId);
            })
            ->when($filters['location_id'] ?? null, function ($query, $locationId) {
                $query->where('location_id', $locationId);
            })
            ->when($filters['work_schedule_id'] ?? null, function ($query, $scheduleId) {
                $query->where(function ($query) use ($scheduleId) {
                    $query->where('work_schedule_id', $scheduleId)
                        ->orWhere(function ($query) use ($scheduleId) {
                            $query->whereNull('work_schedule_id')->whereHas('employee', function ($query) use ($scheduleId) {
                                $query->where('work_schedule_id', $scheduleId);
                            });
                        });
                });
            })
            ->when($filters['shift_type'] ?? null, function ($query, $type) {
                $query->where(function ($query) use ($type) {
                    $query->whereHas('workSchedule', function ($query) use ($type) {
                        $query->where('shift_type', $type);
                    })->orWhere(function ($query) use ($type) {
                        $query->whereNull('work_schedule_id')->whereHas('employee.workSchedule', function ($query) use ($type) {
                            $query->where('shift_type', $type);
                        });
                    });
                });
            })
            ->when($filters['check_in_status'] ?? null, function ($query, $status) {
                $query->where('check_in_status', $status);
            })
            ->when(($filters['completion'] ?? null) === 'complete', function ($query) {
                $query->whereNotNull('check_out');
            })
            ->when(($filters['completion'] ?? null) === 'incomplete', function ($query) {
                $query->whereNull('check_out');
            })
            ->orderByDesc('attendance_date')
            ->orderByDesc('check_in');
    }

    public function show(Attendance $attendance)
    {
        $attendance->load([
            'employee.user',
            'employee.workSchedule',
            'workSchedule',
            'location',
            'corrections.correctedBy',
        ]);

        return view('admin.attendances.show', compact('attendance'));
    }
}
