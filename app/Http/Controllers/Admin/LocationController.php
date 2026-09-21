<?php

/**
 * CRUD master lokasi dan batas validasi GPS untuk kegiatan absensi.
 * Location memiliki banyak Attendance; radius dan accuracy_limit digunakan AttendanceController saat verifikasi posisi.
 * Catatan: lokasi yang sudah menjadi bukti absensi tidak boleh dihapus dengan cara merusak histori.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $search = trim((string) ($validated['search'] ?? ''));

        $locations = Location::withCount('attendances')
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->appends(['search' => $search]);

        return view('admin.locations.location-list', compact('locations', 'search'));
    }

    public function create()
    {
        return view('admin.locations.create');
    }

    public function store(Request $request)
    {
        $location = Location::create($this->validateLocation($request));

        return redirect()->route('admin.locations.index')
            ->with('success', 'Lokasi absensi berhasil ditambahkan.');
    }

    public function edit(Location $location)
    {
        return view('admin.locations.edit', compact('location'));
    }

    public function update(Request $request, Location $location)
    {
        $location->update($this->validateLocation($request, $location));

        return redirect()->route('admin.locations.index')
            ->with('success', 'Lokasi absensi berhasil diperbarui.');
    }

    public function destroy(Location $location)
    {
        if ($location->attendances()->exists()) {
            return redirect()->route('admin.locations.index')
                ->with('error', 'Lokasi tidak dapat dihapus karena sudah digunakan pada data absensi.');
        }

        $location->delete();

        return redirect()->route('admin.locations.index')
            ->with('success', 'Lokasi absensi berhasil dihapus.');
    }

    private function validateLocation(Request $request, Location $location = null)
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('locations')->ignore($location ? $location->id : null),
            ],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['required', 'numeric', 'min:1', 'max:10000'],
            'accuracy_limit' => ['required', 'numeric', 'min:1', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }
}
