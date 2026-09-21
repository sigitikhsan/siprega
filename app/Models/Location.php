<?php

/**
 * Master titik kantor yang sah untuk absensi berbasis GPS.
 * Location memiliki banyak Attendance dan menyediakan radius serta batas akurasi untuk validasi posisi.
 * Catatan: koordinat browser adalah bukti pendukung; keputusan jarak tetap dihitung ulang di server.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'radius',
        'accuracy_limit',
        'status',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'radius' => 'decimal:2',
        'accuracy_limit' => 'decimal:2',
    ];

    /**
     * Location memiliki banyak Attendance.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
