<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkScheduleController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $search = trim((string) ($validated['search'] ?? ''));

        $schedules = WorkSchedule::withCount([
            'employees',
            'attendances',
            'shiftAssignments',
            'shiftAssignments as future_shift_assignments_count' => function ($query) {
                $query->whereDate('shift_date', '>=', today());
            },
        ])
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->appends(['search' => $search]);

        return view('admin.work-schedules.index', compact('schedules', 'search'));
    }

    public function create()
    {
        return view('admin.work-schedules.create');
    }

    public function store(Request $request)
    {
        $schedule = WorkSchedule::create($this->validateSchedule($request));

        return redirect()->route('admin.work-schedules.index')
            ->with('success', 'Jadwal kerja berhasil ditambahkan.');
    }

    public function edit(WorkSchedule $workSchedule)
    {
        $isScheduleInUse = $workSchedule->employees()->exists()
            || $workSchedule->shiftAssignments()->whereDate('shift_date', '>=', today())->exists();

        return view('admin.work-schedules.edit', compact('workSchedule', 'isScheduleInUse'));
    }

    public function update(Request $request, WorkSchedule $workSchedule)
    {
        $validated = $this->validateSchedule($request, $workSchedule);

        if ($validated['status'] === 'inactive' && (
            $workSchedule->employees()->exists() ||
            $workSchedule->shiftAssignments()->whereDate('shift_date', '>=', today())->exists()
        )) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => 'Jadwal tidak dapat dinonaktifkan karena masih digunakan oleh pegawai atau penugasan shift mendatang. Pindahkan pengguna jadwal terlebih dahulu.',
            ]);
        }

        $workSchedule->update($validated);

        return redirect()->route('admin.work-schedules.index')
            ->with('success', 'Jadwal kerja berhasil diperbarui.');
    }

    public function destroy(WorkSchedule $workSchedule)
    {
        if ($workSchedule->employees()->exists() || $workSchedule->shiftAssignments()->whereDate('shift_date', '>=', today())->exists()) {
            return redirect()->route('admin.work-schedules.index')
                ->with('error', 'Jadwal masih digunakan oleh pegawai atau penugasan shift mendatang. Pindahkan pegawai dan penugasannya terlebih dahulu.');
        }

        if ($workSchedule->attendances()->exists() || $workSchedule->shiftAssignments()->exists()) {
            $workSchedule->update(['status' => 'inactive']);

            return redirect()->route('admin.work-schedules.index')
                ->with('success', 'Jadwal memiliki riwayat absensi sehingga tidak dihapus permanen. Jadwal berhasil dinonaktifkan dan tetap tersedia untuk rekap lama.');
        }

        $workSchedule->delete();

        return redirect()->route('admin.work-schedules.index')
            ->with('success', 'Jadwal kerja berhasil dihapus.');
    }

    private function validateSchedule(Request $request, WorkSchedule $workSchedule = null)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('work_schedules')->ignore($workSchedule ? $workSchedule->id : null),
            ],
            'shift_type' => ['required', Rule::in(['fixed', 'day', 'night'])],
            'check_in_start' => ['required', 'date_format:H:i'],
            'check_in_end' => ['required', 'date_format:H:i', 'after:check_in_start'],
            'late_tolerance' => ['required', 'integer', 'min:0', 'max:180'],
            'check_out_start' => ['required', 'date_format:H:i'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'check_in_end.after' => 'Batas akhir absen masuk harus setelah waktu mulai.',
        ]);

        if ($validated['shift_type'] !== 'night' && $validated['check_out_start'] <= $validated['check_in_end']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'check_out_start' => 'Waktu mulai absen pulang harus setelah batas akhir absen masuk.',
            ]);
        }

        return $validated;
    }
}
