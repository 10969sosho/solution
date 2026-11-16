<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Lokasi;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index()
    {
        $devices = Device::with('lokasi')->orderBy('name')->get();
        return view('devices.index', compact('devices'));
    }

    public function create()
    {
        $lokasis = Lokasi::orderBy('name')->get();
        return view('devices.create', compact('lokasis'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'serial_number' => 'required|string|max:100|unique:devices,serial_number',
            'lokasi_id' => 'nullable|exists:lokasis,id',
            'ip_address' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->has('is_active');

        Device::create($validated);

        return redirect()->route('devices.index')->with('success', 'Mesin absensi berhasil ditambahkan.');
    }

    public function edit(Device $device)
    {
        $lokasis = Lokasi::orderBy('name')->get();
        return view('devices.edit', compact('device', 'lokasis'));
    }

    public function update(Request $request, Device $device)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'serial_number' => 'required|string|max:100|unique:devices,serial_number,' . $device->id,
            'lokasi_id' => 'nullable|exists:lokasis,id',
            'ip_address' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $device->update($validated);

        return redirect()->route('devices.index')->with('success', 'Mesin absensi berhasil diperbarui.');
    }

    public function destroy(Device $device)
    {
        $device->delete();

        return redirect()->route('devices.index')->with('success', 'Mesin absensi berhasil dihapus.');
    }
}
