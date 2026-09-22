<?php

/**
 * Menampilkan daftar/detail absensi, rekap terfilter, dan ekspor Excel untuk admin.
 * Query utama memakai Attendance beserta Employee dan WorkSchedule; ekspor diteruskan ke AttendanceRecapExport.
 * Catatan: filter halaman dan ekspor harus menggunakan aturan query yang sama agar hasil laporan konsisten.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\AttendanceRecapExport;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    private const MAX_DIRECT_EXPORT_ROWS = 5000;

    public function index(Request $request)
    {
        $isRecap = $request->routeIs('admin.attendance-recap.*');
        $filters = $this->validateFilters($request, $isRecap);

        $search = trim((string) ($filters['search'] ?? ''));
        $filteredQuery = $this->filteredQuery($filters);
        $summary = [];
        if ($isRecap) {
            $summaryRow = (clone $filteredQuery)->setEagerLoads([])->reorder()->selectRaw(
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

            // The summary already counted these rows; avoid a second COUNT for pagination.
            $page = LengthAwarePaginator::resolveCurrentPage();
            $attendances = new LengthAwarePaginator(
                $summary['total'] ? $filteredQuery->forPage($page, 15)->get() : collect(),
                $summary['total'], 15, $page,
                ['path' => $request->url()]
            );
        } else {
            $attendances = $filteredQuery->paginate(15);
        }
        $attendances->appends($filters);

        // Rekap only exposes date filters. Do not fetch unused dropdown collections.
        $employees = $isRecap ? collect() : Employee::with('user:id,name')
            ->orderBy('employee_number')->get(['id', 'user_id', 'employee_number']);
        $schedules = $isRecap ? collect() : WorkSchedule::orderBy('name')->get(['id', 'name']);

        return view('admin.attendances.attendance-list', compact('attendances', 'employees', 'schedules', 'filters', 'search', 'summary'));
    }

    public function export(Request $request)
    {
        $filters = $this->validateFilters($request, $request->routeIs('admin.attendance-recap.*'), true);
        $exportQuery = $this->filteredQuery($filters);
        $rowCount = (clone $exportQuery)->setEagerLoads([])->reorder()->count();
        if ($rowCount > self::MAX_DIRECT_EXPORT_ROWS) {
            throw ValidationException::withMessages([
                'date_from' => 'Data ekspor melebihi 5.000 baris. Isi atau persempit filter tanggal sebelum mengekspor.',
            ]);
        }
        $fileName = 'rekap-absensi-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new AttendanceRecapExport($exportQuery), $fileName);
    }

    private function validateFilters(Request $request, bool $datesOnly = false, bool $forExport = false): array
    {
        $rules = [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
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

        if (!empty($validated['date_from']) && !empty($validated['date_to'])) {
            $dateFrom = Carbon::parse($validated['date_from']);
            $dateTo = Carbon::parse($validated['date_to']);

            if ($dateTo->lt($dateFrom)) {
                throw ValidationException::withMessages([
                    'date_to' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.',
                ]);
            }

            if ($forExport && $dateFrom->diffInDays($dateTo) > 30) {
                throw ValidationException::withMessages([
                    'date_to' => 'Rentang ekspor maksimal 31 hari.',
                ]);
            }
        }

        return $validated;
    }

    private function filteredQuery(array $filters)
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return Attendance::with([
            'employee:id,user_id,work_schedule_id,employee_number',
            'employee.user:id,name', 'employee.workSchedule:id,name,shift_type',
            'workSchedule:id,name,shift_type', 'location:id,name',
        ])
            ->when($search, function ($query, $search) {
                $query->whereHas('employee', function ($query) use ($search) {
                    $query->where('employee_number', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['date_from'] ?? null, function ($query, $date) {
                $query->where('attendance_date', '>=', Carbon::parse($date)->toDateString());
            })
            ->when($filters['date_to'] ?? null, function ($query, $date) {
                $query->where('attendance_date', '<=', Carbon::parse($date)->toDateString());
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
                $query->forShiftType($type);
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
            ->orderByDesc('check_in')
            ->orderByDesc('id');
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

        return view('admin.attendances.attendance-detail', compact('attendance'));
    }
}
