<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Location;
use App\Services\EmployeeScheduleResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function checkIn(Request $request, EmployeeScheduleResolver $resolver)
    {
        $position = $this->validatePosition($request);
        $employee = $request->user()->employee()->with('workSchedule')->firstOrFail();
        list($schedule, $assignment, $shiftDate) = $resolver->forCurrentMoment($employee, now());
        if ($assignment && $assignment->is_day_off) {
            return back()->with('error', 'Hari ini Anda dijadwalkan libur sehingga absensi tidak dapat dilakukan.');
        }
        $approvedLeave = $employee->leaveRequests()->where('status', 'approved')
            ->whereDate('start_date', '<=', $shiftDate)->get()->first(function ($leave) use ($shiftDate) {
                return $leave->start_date->copy()->addDays($leave->duration - 1)->gte($shiftDate);
            });
        if ($approvedLeave) {
            return back()->with('error', 'Anda tercatat '.($approvedLeave->type === 'sick' ? 'sakit' : 'izin').' hari ini sehingga tidak perlu melakukan absen masuk.');
        }

        if (!$schedule || $schedule->status !== 'active') {
            return back()->with('error', 'Anda tidak memiliki jadwal aktif hari ini. Hubungi administrator.');
        }

        $now = now();
        $attendanceDate = $shiftDate->toDateString();
        $start = Carbon::parse($attendanceDate.' '.$schedule->check_in_start);
        if ($now->lt($start)) {
            return back()->with('error', 'Absen masuk belum dibuka. Jadwal dimulai pukul '.$start->format('H:i').'.');
        }

        [$location, $distance] = $this->resolveLocation($position);
        [$isSuspicious, $riskNote] = $this->locationRiskFromPreviousAttendance($employee->id, $position, $now);
        $lateAt = Carbon::parse($attendanceDate.' '.$schedule->check_in_end)
            ->addMinutes((int) $schedule->late_tolerance);

        try {
            $attendance = DB::transaction(function () use ($employee, $location, $position, $distance, $now, $lateAt, $schedule, $assignment, $attendanceDate, $isSuspicious, $riskNote) {
                if (Attendance::where('employee_id', $employee->id)->whereDate('attendance_date', $attendanceDate)->lockForUpdate()->exists()) {
                    throw ValidationException::withMessages(['attendance' => 'Anda sudah melakukan absen masuk hari ini.']);
                }

                return Attendance::create([
                    'employee_id' => $employee->id,
                    'location_id' => $location->id,
                    'work_schedule_id' => $schedule->id,
                    'shift_assignment_id' => $assignment ? $assignment->id : null,
                    'attendance_date' => $attendanceDate,
                    'check_in' => $now,
                    'check_in_latitude' => $position['latitude'],
                    'check_in_longitude' => $position['longitude'],
                    'check_in_accuracy' => $position['accuracy'],
                    'check_in_distance' => $distance,
                    'check_in_captured_at' => $position['captured_at'],
                    'check_in_location_suspicious' => $isSuspicious,
                    'check_in_risk_note' => $riskNote,
                    'check_in_status' => $now->gt($lateAt) ? 'late' : 'present',
                ]);
            });
        } catch (\Illuminate\Database\QueryException $exception) {
            return back()->with('error', 'Anda sudah melakukan absen masuk hari ini.');
        }

        return back()->with('success', 'Absen masuk berhasil dicatat pukul '.$now->format('H:i:s').'.');
    }

    public function checkOut(Request $request)
    {
        $position = $this->validatePosition($request);
        $validated = $request->validate(['early_checkout_reason' => ['nullable', 'string', 'max:1000']]);
        $employee = $request->user()->employee()->firstOrFail();
        [$location, $distance] = $this->resolveLocation($position);
        $now = now();
        $attendance = Attendance::with('workSchedule')->where('employee_id', $employee->id)
            ->whereNotNull('check_in')->whereNull('check_out')->where('check_in', '>=', now()->subDay())->latest('check_in')->first();

        if (!$attendance || !$attendance->check_in) return back()->with('error', 'Anda belum melakukan absen masuk hari ini.');
        $schedule = $attendance->workSchedule ?: $employee->workSchedule;
        $checkoutDate = $schedule->shift_type === 'night'
            ? $attendance->attendance_date->copy()->addDay()->toDateString()
            : $attendance->attendance_date->toDateString();
        $checkoutStart = Carbon::parse($checkoutDate.' '.$schedule->check_out_start);
        $isEarly = $now->lt($checkoutStart);
        if ($isEarly && empty($validated['early_checkout_reason'])) {
            return back()->with('error', 'Alasan wajib diisi karena Anda pulang sebelum pukul '.$checkoutStart->format('H:i').'.');
        }

        [$isSuspicious, $riskNote] = $this->locationRisk(
            $position,
            $now,
            $attendance->check_in_latitude,
            $attendance->check_in_longitude,
            $attendance->check_in
        );

        DB::transaction(function () use ($attendance, $now, $isEarly, $validated, $position, $distance, $isSuspicious, $riskNote) {
            $lockedAttendance = Attendance::whereKey($attendance->id)->lockForUpdate()->firstOrFail();
            if ($lockedAttendance->check_out) {
                throw ValidationException::withMessages(['attendance' => 'Absen pulang sudah pernah dicatat.']);
            }

            $lockedAttendance->update([
                'check_out' => $now,
                'check_out_status' => $isEarly ? 'early_checkout' : 'normal',
                'early_checkout_reason' => $validated['early_checkout_reason'] ?? null,
                'check_out_latitude' => $position['latitude'],
                'check_out_longitude' => $position['longitude'],
                'check_out_accuracy' => $position['accuracy'],
                'check_out_distance' => $distance,
                'check_out_captured_at' => $position['captured_at'],
                'check_out_location_suspicious' => $isSuspicious,
                'check_out_risk_note' => $riskNote,
            ]);
        });

        return back()->with('success', 'Absen pulang berhasil dicatat pukul '.$now->format('H:i:s').'.');
    }

    private function validatePosition(Request $request): array
    {
        $position = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['required', 'numeric', 'min:0.1', 'max:10000'],
            'captured_at' => ['required', 'date'],
        ]);

        $capturedAt = Carbon::parse($position['captured_at']);
        $now = now();
        if ($capturedAt->lt($now->copy()->subMinutes(2))) {
            throw ValidationException::withMessages(['location' => 'Data lokasi sudah kedaluwarsa. Ambil lokasi GPS baru lalu coba kembali.']);
        }
        if ($capturedAt->gt($now->copy()->addMinute())) {
            throw ValidationException::withMessages(['location' => 'Waktu perangkat tidak sesuai. Aktifkan pengaturan tanggal dan waktu otomatis.']);
        }

        // Browser mengirim timestamp ISO dalam UTC. Simpan sebagai waktu lokal aplikasi
        // agar konsisten dengan check_in/check_out yang menggunakan waktu server.
        $position['captured_at'] = $capturedAt->copy()->setTimezone(config('app.timezone'));
        return $position;
    }

    private function resolveLocation(array $position): array
    {
        $nearest = null;
        $nearestDistance = INF;
        foreach (Location::where('status', 'active')->get() as $location) {
            $distance = $this->distance($position['latitude'], $position['longitude'], $location->latitude, $location->longitude);
            if ($distance < $nearestDistance) { $nearest = $location; $nearestDistance = $distance; }
        }

        if (!$nearest) throw ValidationException::withMessages(['location' => 'Belum ada lokasi absensi yang aktif.']);
        if ((float) $position['accuracy'] > (float) $nearest->accuracy_limit) {
            throw ValidationException::withMessages(['accuracy' => 'Akurasi GPS terlalu rendah. Pindah ke area terbuka lalu coba kembali.']);
        }
        if ($nearestDistance > (float) $nearest->radius) {
            throw ValidationException::withMessages(['location' => 'Anda berada di luar radius lokasi absensi terdekat.']);
        }

        return [$nearest, round($nearestDistance, 2)];
    }

    private function distance($lat1, $lon1, $lat2, $lon2): float
    {
        $earth = 6371000;
        $latDelta = deg2rad((float) $lat2 - (float) $lat1);
        $lonDelta = deg2rad((float) $lon2 - (float) $lon1);
        $a = sin($latDelta / 2) ** 2 + cos(deg2rad((float) $lat1)) * cos(deg2rad((float) $lat2)) * sin($lonDelta / 2) ** 2;
        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function locationRiskFromPreviousAttendance(int $employeeId, array $position, Carbon $now): array
    {
        $previous = Attendance::where('employee_id', $employeeId)
            ->whereNotNull('check_in')
            ->latest('check_in')
            ->first();

        if (!$previous) return [false, null];

        if ($previous->check_out && $previous->check_out_latitude !== null && $previous->check_out_longitude !== null) {
            return $this->locationRisk($position, $now, $previous->check_out_latitude, $previous->check_out_longitude, $previous->check_out);
        }

        return $this->locationRisk($position, $now, $previous->check_in_latitude, $previous->check_in_longitude, $previous->check_in);
    }

    private function locationRisk(array $position, Carbon $now, $previousLatitude, $previousLongitude, $previousTime): array
    {
        if ($previousLatitude === null || $previousLongitude === null || !$previousTime) return [false, null];

        $elapsedSeconds = Carbon::parse($previousTime)->diffInSeconds($now, false);
        if ($elapsedSeconds <= 0) return [false, null];

        $travelDistance = $this->distance($previousLatitude, $previousLongitude, $position['latitude'], $position['longitude']);
        $speedKmh = ($travelDistance / $elapsedSeconds) * 3.6;

        if ($travelDistance >= 5000 && $speedKmh > 200) {
            return [true, 'Perpindahan lokasi tidak wajar: sekitar '.number_format($travelDistance / 1000, 1, ',', '.').' km dalam '.max(1, (int) round($elapsedSeconds / 60)).' menit.'];
        }

        return [false, null];
    }
}
