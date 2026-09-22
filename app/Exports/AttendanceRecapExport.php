<?php

/**
 * Orkestrator workbook rekap absensi dengan beberapa worksheet kategori.
 * Menerima query Attendance terfilter dari controller dan membentuk AttendanceRecapSheet tanpa mengubah database.
 * Catatan: clone query untuk tiap sheet agar filter tanggal dasar tetap sama dan tidak saling memengaruhi.
 */

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
            new AttendanceRecapSheet((clone $this->query)->forShiftType('day'), 'Shift Siang'),
            new AttendanceRecapSheet((clone $this->query)->forShiftType('night'), 'Shift Malam'),
            new AttendanceRecapSheet((clone $this->query)->forShiftType('fixed'), 'Jadwal Tetap'),
            new AttendanceRecapSheet((clone $this->query)->whereNotNull('check_out'), 'Sudah Pulang'),
            new AttendanceRecapSheet((clone $this->query)->whereNull('check_out'), 'Belum Pulang'),
        ];
    }

}
