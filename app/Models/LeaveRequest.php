<?php

/**
 * Pengajuan izin atau sakit untuk rentang hari tertentu beserta status persetujuan dan lampiran.
 * BelongsTo Employee; scope tanggal dipakai dashboard, validasi benturan, dan pencegahan check-in saat disetujui.
 * Catatan: tanggal akhir dihitung inklusif dari start_date dan duration.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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

    /** Filter overlapping calendar dates in SQL, without loading the employee's entire history. */
    public function scopeOverlappingDates(Builder $query, $start, $end = null): Builder
    {
        $start = Carbon::parse($start)->toDateString();
        $end = Carbon::parse($end ?? $start)->toDateString();

        return $query->where('start_date', '<=', $end)
            ->whereRaw('DATE_ADD(start_date, INTERVAL (duration - 1) DAY) >= ?', [$start]);
    }

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
