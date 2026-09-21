<?php

/**
 * CRUD akun dan profil pegawai, termasuk jadwal dasar, status akun, dan penghapusan aman.
 * Menulis User dan Employee dalam transaksi serta berelasi dengan WorkSchedule dan penugasan shift.
 * Catatan: riwayat absensi tidak dihapus; pegawai yang sudah memiliki histori dinonaktifkan untuk menjaga integritas data.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\AdminDashboardData;
use App\Services\ProfileAvatarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $search = trim((string) ($validated['search'] ?? ''));

        $employees = Employee::with(['user', 'workSchedule'])
            ->withCount(['attendances', 'leaveRequests'])
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('employee_number', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->appends([
                'search' => $search,
            ]);

        return view('admin.employees.employee-list', compact('employees', 'search'));
    }

    public function create()
    {
        $schedules = WorkSchedule::where('status', 'active')->orderBy('name')->get();

        return view('admin.employees.create', compact('schedules'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateEmployee($request);

        $employee = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'role' => 'employee',
                'status' => $validated['status'],
            ]);

            return $user->employee()->create($this->employeeData($validated));
        });
        app(AdminDashboardData::class)->forgetAll();

        return redirect()->route('admin.employees.index')
            ->with('success', 'Data pegawai berhasil ditambahkan.');
    }

    public function edit(Employee $employee)
    {
        $employee->load('user');
        $schedules = WorkSchedule::where('status', 'active')
            ->orWhere('id', $employee->work_schedule_id)
            ->orderBy('name')
            ->get();

        return view('admin.employees.edit', compact('employee', 'schedules'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $this->validateEmployee($request, $employee);

        DB::transaction(function () use ($validated, $employee) {
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'username' => $validated['username'],
                'status' => $validated['status'],
            ];

            if (!empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
                $userData['remember_token'] = Str::random(60);
            }

            $employee->user->forceFill($userData)->save();
            $employee->update($this->employeeData($validated));

            if ($validated['status'] === 'inactive') {
                $employee->shiftAssignments()->whereDate('shift_date', '>=', today())->delete();
            }
        });
        app(AdminDashboardData::class)->forgetAll();

        return redirect()->route('admin.employees.index')
            ->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function destroy(Employee $employee)
    {
        $hasHistory = $employee->attendances()->exists() || $employee->leaveRequests()->exists();

        if ($hasHistory) {
            DB::transaction(function () use ($employee) {
                $employee->user->update(['status' => 'inactive']);
                $employee->shiftAssignments()->whereDate('shift_date', '>=', today())->delete();
            });
            app(AdminDashboardData::class)->forgetAll();

            return redirect()->route('admin.employees.index')
                ->with('success', 'Pegawai tidak dihapus karena memiliki riwayat absensi/izin. Akun berhasil dinonaktifkan dan penugasan shift mendatang dibatalkan.');
        }

        $avatarPath = $employee->avatar_path;
        DB::transaction(function () use ($employee) {
            $employee->user->delete();
        });
        app(ProfileAvatarService::class)->delete($avatarPath);
        app(AdminDashboardData::class)->forgetAll();

        return redirect()->route('admin.employees.index')
            ->with('success', 'Data pegawai berhasil dihapus.');
    }

    private function validateEmployee(Request $request, Employee $employee = null)
    {
        $userId = $employee ? $employee->user_id : null;
        $employeeId = $employee ? $employee->id : null;

        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'string', 'not_regex:/[\r\n]/', 'email', 'max:150', Rule::unique('users')->ignore($userId)],
            'username' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($userId)],
            'password' => [$employee ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'employee_number' => ['required', 'string', 'max:30', Rule::unique('employees')->ignore($employeeId)],
            'work_schedule_id' => ['required', function ($attribute, $value, $fail) use ($employee) {
                if ($value === 'shift') {
                    if (!WorkSchedule::where('status', 'active')->whereIn('shift_type', ['day', 'night'])->exists()) {
                        $fail('Belum ada Shift Siang atau Shift Malam yang aktif.');
                    }
                    return;
                }

                $valid = WorkSchedule::whereKey($value)->where(function ($query) use ($employee) {
                    $query->where('status', 'active');
                    if ($employee && $employee->work_schedule_id) $query->orWhere('id', $employee->work_schedule_id);
                })->exists();
                if (!$valid) $fail('Jadwal kerja yang dipilih tidak aktif atau tidak valid.');
            }],
            'phone' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:150'],
            'join_date' => ['nullable', 'date'],
        ]);
    }

    private function employeeData(array $validated)
    {
        $usesShiftSchedule = $validated['work_schedule_id'] === 'shift';
        $scheduleId = $usesShiftSchedule
            ? WorkSchedule::where('status', 'active')->whereIn('shift_type', ['day', 'night'])->orderBy('id')->value('id')
            : $validated['work_schedule_id'];

        return [
            'work_schedule_id' => $scheduleId,
            'uses_shift_schedule' => $usesShiftSchedule,
            'employee_number' => $validated['employee_number'],
            'phone' => $validated['phone'] ?? null,
            'position' => $validated['position'] ?? null,
            'company' => $validated['company'] ?? null,
            'join_date' => $validated['join_date'] ?? null,
        ];
    }

}
