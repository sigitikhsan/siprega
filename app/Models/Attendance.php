<?php

/**
 * Rekaman presensi per pegawai dan tanggal shift, termasuk waktu, status, lokasi, serta bukti GPS masuk/pulang.
 * BelongsTo Employee, Location, WorkSchedule, dan EmployeeShiftAssignment; memiliki banyak AttendanceCorrection.
 * Catatan: attendance_date adalah tanggal shift, sehingga shift malam dapat selesai pada tanggal kalender berikutnya.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'location_id',
        'work_schedule_id',
        'shift_assignment_id',
        'attendance_date',

        'check_in',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_accuracy',
        'check_in_distance',
        'check_in_captured_at',
        'check_in_location_suspicious',
        'check_in_risk_note',
        'check_in_status',
        'late_reason',

        'check_out',
        'check_out_status',
        'early_checkout_reason',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_accuracy',
        'check_out_distance',
        'check_out_captured_at',
        'check_out_location_suspicious',
        'check_out_risk_note',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'check_in_captured_at' => 'datetime',
        'check_out_captured_at' => 'datetime',
        'check_in_location_suspicious' => 'boolean',
        'check_out_location_suspicious' => 'boolean',
    ];

    /**
     * Attendance dimiliki oleh satu Employee.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Attendance menggunakan satu Location.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function workSchedule()
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    public function shiftAssignment()
    {
        return $this->belongsTo(EmployeeShiftAssignment::class);
    }

    /**
     * Attendance dapat memiliki banyak riwayat koreksi.
     */
    public function corrections()
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    /**
     * Membatasi absensi berdasarkan jenis jadwal yang tersimpan pada absensi,
     * dengan fallback ke jadwal dasar pegawai untuk data lama.
     */
    public function scopeForShiftType($query, string $type)
    {
        return $query->where(function ($query) use ($type) {
            $query->whereHas('workSchedule', function ($scheduleQuery) use ($type) {
                $scheduleQuery->where('shift_type', $type);
            })->orWhere(function ($legacyQuery) use ($type) {
                $legacyQuery->whereNull('work_schedule_id')
                    ->whereHas('employee.workSchedule', function ($scheduleQuery) use ($type) {
                        $scheduleQuery->where('shift_type', $type);
                    });
            });
        });
    }
}
