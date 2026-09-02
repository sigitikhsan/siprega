<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'shift_type',
        'check_in_start',
        'check_in_end',
        'late_tolerance',
        'check_out_start',
        'status',
    ];

    /**
     * WorkSchedule memiliki banyak Employee.
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function shiftAssignments()
    {
        return $this->hasMany(EmployeeShiftAssignment::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
