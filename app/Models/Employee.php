<?php

/**
 * Profil kepegawaian yang memperluas akun User dengan nomor, jabatan, telepon, dan konfigurasi jadwal.
 * Berelasi ke User, WorkSchedule, Attendance, LeaveRequest, dan EmployeeShiftAssignment.
 * Catatan: uses_shift_schedule membedakan pegawai fleksibel dari pegawai dengan jadwal tetap.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_schedule_id',
        'uses_shift_schedule',
        'employee_number',
        'phone',
        'avatar_path',
        'position',
        'company',
        'join_date',
    ];

    protected $casts = [
        'join_date' => 'date',
        'uses_shift_schedule' => 'boolean',
    ];

    /**
     * Employee memiliki satu User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Employee memiliki satu Work Schedule.
     */
    public function workSchedule()
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    /**
     * Employee memiliki banyak Attendance.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Employee memiliki banyak Leave Request.
     */
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function shiftAssignments()
    {
        return $this->hasMany(EmployeeShiftAssignment::class);
    }
}
