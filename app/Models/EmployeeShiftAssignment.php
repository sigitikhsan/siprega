<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeShiftAssignment extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'work_schedule_id', 'shift_date', 'is_day_off', 'notes'];
    protected $casts = ['shift_date' => 'date', 'is_day_off' => 'boolean'];

    public function employee() { return $this->belongsTo(Employee::class); }
    public function workSchedule() { return $this->belongsTo(WorkSchedule::class); }
    public function attendance() { return $this->hasOne(Attendance::class, 'shift_assignment_id'); }
}
