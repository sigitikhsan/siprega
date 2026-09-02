<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'type',
        'start_date',
        'duration',
        'reason',
        'attachment',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'start_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    /**
     * LeaveRequest dimiliki oleh satu Employee.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * LeaveRequest dapat direview oleh satu User/Admin.
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}