<?php

namespace App\Http\Controllers;

use App\Models\WorkSetting;
use Illuminate\Http\Request;

class WorkSettingController extends Controller
{
    public function index()
    {
        $settings = WorkSetting::all();
        return view('settings.index', compact('settings'));
    }

    public function edit(WorkSetting $setting)
    {
        return view('settings.edit', compact('setting'));
    }

    public function update(Request $request, WorkSetting $setting)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'check_in_time' => 'required|date_format:H:i',
            'check_out_time' => 'required|date_format:H:i|after:check_in_time',
            'late_tolerance_minutes' => 'required|integer|min:0|max:120',
            'overtime_threshold_minutes' => 'required|integer|min:0|max:240',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $setting->update($validated);

        return redirect()->route('settings.index')->with('success', 'Setting berhasil diupdate');
    }
}
