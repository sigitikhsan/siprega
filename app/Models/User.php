<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Kolom yang boleh diisi melalui mass assignment.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'role',
        'status',
    ];

    /**
     * Kolom yang disembunyikan ketika model diubah menjadi array/JSON.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Relasi User ke Employee.
     */
    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Relasi User ke LeaveRequest sebagai reviewer/admin.
     */
    public function reviewedLeaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'reviewed_by');
    }

    /**
     * Relasi User ke AttendanceCorrection sebagai pihak yang melakukan koreksi.
     */
    public function attendanceCorrections()
    {
        return $this->hasMany(AttendanceCorrection::class, 'corrected_by');
    }

}
