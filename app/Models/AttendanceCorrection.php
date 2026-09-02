<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'corrected_by',
        'old_check_in',
        'new_check_in',
        'old_check_out',
        'new_check_out',
        'reason',
    ];

    protected $casts = [
        'old_check_in' => 'datetime',
        'new_check_in' => 'datetime',
        'old_check_out' => 'datetime',
        'new_check_out' => 'datetime',
    ];

    /**
     * Koreksi dimiliki oleh satu Attendance.
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * User/Admin yang melakukan koreksi.
     */
    public function correctedBy()
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }
}