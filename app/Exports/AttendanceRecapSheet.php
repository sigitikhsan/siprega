<?php

/**
 * Mendefinisikan satu worksheet Excel: query, judul, heading, mapping, lebar, dan gaya kolom.
 * Data berasal dari Attendance beserta relasi pegawai/jadwal/lokasi yang disiapkan AttendanceRecapExport.
 * Catatan: nilai teks harus diamankan dari formula injection sebelum ditulis ke spreadsheet.
 */

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceRecapSheet implements FromQuery, WithHeadings, WithMapping, WithColumnWidths, WithStyles, WithTitle
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

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 21,
            'C' => 28,
            'D' => 18,
            'E' => 26,
            'F' => 24,
            'G' => 15,
            'H' => 15,
            'I' => 17,
            'J' => 20,
        ];
    }

    public function map($attendance): array
    {
        $schedule = $attendance->workSchedule ?: optional($attendance->employee)->workSchedule;
        // Each Eloquent date access creates a Carbon object; cast once per cell.
        $checkIn = $attendance->check_in;
        $checkOut = $attendance->check_out;
        return array_map([$this, 'asSafeSpreadsheetText'], [
            $attendance->attendance_date->format('d/m/Y'), optional($attendance->employee)->employee_number ?: '-',
            optional(optional($attendance->employee)->user)->name ?: '-',
            $schedule ? ($schedule->shift_type === 'night' ? 'Shift Malam' : ($schedule->shift_type === 'day' ? 'Shift Siang' : 'Jadwal Tetap')) : '-',
            $schedule ? $schedule->name : '-', optional($attendance->location)->name ?: '-',
            $checkIn ? $checkIn->format('H:i:s') : '-',
            $checkOut ? $checkOut->format('H:i:s') : '-',
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
