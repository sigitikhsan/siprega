<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AttendanceRecapExport implements WithMultipleSheets
{
    private $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function sheets(): array
    {
        return [
            new AttendanceRecapSheet(clone $this->query, 'Semua Absensi'),
            new AttendanceRecapSheet((clone $this->query)->where('check_in_status', 'present'), 'Tepat Waktu'),
            new AttendanceRecapSheet((clone $this->query)->where('check_in_status', 'late'), 'Terlambat'),
            new AttendanceRecapSheet($this->byShiftType('day'), 'Shift Siang'),
            new AttendanceRecapSheet($this->byShiftType('night'), 'Shift Malam'),
            new AttendanceRecapSheet($this->byShiftType('fixed'), 'Jadwal Tetap'),
            new AttendanceRecapSheet((clone $this->query)->whereNotNull('check_out'), 'Sudah Pulang'),
            new AttendanceRecapSheet((clone $this->query)->whereNull('check_out'), 'Belum Pulang'),
        ];
    }

    private function byShiftType(string $type): Builder
    {
        return (clone $this->query)->where(function ($query) use ($type) {
            $query->whereHas('workSchedule', function ($scheduleQuery) use ($type) {
                $scheduleQuery->where('shift_type', $type);
            })->orWhere(function ($legacyQuery) use ($type) {
                $legacyQuery->whereNull('work_schedule_id')->whereHas('employee.workSchedule', function ($scheduleQuery) use ($type) {
                    $scheduleQuery->where('shift_type', $type);
                });
            });
        });
    }
}
