<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceRecapSheet implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    private $query;
    private $title;

    public function __construct(Builder $query, string $title)
    {
        $this->query = $query;
        $this->title = $title;
    }

    public function query() { return $this->query; }
    public function title(): string { return $this->title; }

    public function headings(): array
    {
        return ['Tanggal Shift', 'NIK/Nomor Pegawai', 'Nama Pegawai', 'Sistem Jadwal', 'Jadwal/Shift', 'Lokasi', 'Absen Masuk', 'Absen Pulang', 'Status Masuk', 'Status Pulang'];
    }

    public function map($attendance): array
    {
        $schedule = $attendance->workSchedule ?: $attendance->employee->workSchedule;
        return array_map([$this, 'asSafeSpreadsheetText'], [
            $attendance->attendance_date->format('d/m/Y'), $attendance->employee->employee_number,
            $attendance->employee->user->name,
            $schedule ? ($schedule->shift_type === 'night' ? 'Shift Malam' : ($schedule->shift_type === 'day' ? 'Shift Siang' : 'Jadwal Tetap')) : '-',
            $schedule ? $schedule->name : '-', $attendance->location->name,
            $attendance->check_in ? $attendance->check_in->format('H:i:s') : '-',
            $attendance->check_out ? $attendance->check_out->format('H:i:s') : '-',
            $attendance->check_in_status === 'late' ? 'Terlambat' : 'Tepat waktu',
            $attendance->check_out_status === 'early_checkout' ? 'Pulang lebih awal' : ($attendance->check_out_status === 'normal' ? 'Normal' : 'Belum pulang'),
        ]);
    }

    private function asSafeSpreadsheetText($value)
    {
        if (!is_string($value) || $value === '-') return $value;

        return preg_match('/^[\x00-\x20]*[=+\-@]/', $value) ? "'".$value : $value;
    }

    public function styles(Worksheet $sheet) { return [1 => ['font' => ['bold' => true]]]; }
}
