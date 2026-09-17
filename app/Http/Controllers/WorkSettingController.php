<?php

namespace App\Http\Controllers;

use App\Models\Golongan;
use App\Models\WorkSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WorkSettingController extends Controller
{
    public function index()
    {
        $settings = WorkSetting::with(['golongan', 'golongans'])->orderBy('golongan_id')->get();
        $golongans = Golongan::orderBy('name')->get();

        return view('settings.index', compact('settings', 'golongans'));
    }

    public function create()
    {
        $golongans = Golongan::orderBy('name')->get();
        return view('settings.create', compact('golongans'));
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['golongan_ids'] = $data['golongan_ids'] ?? [];

        $validated = Validator::make($data, [
            'name' => 'required|string|max:255',
            'day' => 'nullable|array',
            'day.*' => 'in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'golongan_ids' => 'nullable|array',
            'golongan_ids.*' => 'exists:golongans,id',
            'check_in_time' => 'required',
            'check_out_time' => 'required',
            'break_out_time' => 'required',
            'break_in_time' => 'required',
            'late_tolerance_minutes' => 'required|integer|min:0|max:120',
            'overtime_threshold_minutes' => 'required|integer|min:0|max:240',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ])->validate();

        foreach (['check_in_time', 'check_out_time', 'break_out_time', 'break_in_time'] as $field) {
            $validated[$field] = substr($validated[$field], 0, 5);
        }

        $validated['day'] = ! empty($validated['day']) ? implode(',', $validated['day']) : null;
        $validated['is_active'] = $request->boolean('is_active');

        $setting = WorkSetting::create(array_merge(
            array_diff_key($validated, ['golongan_ids' => true]),
            ['golongan_id' => null]
        ));
        $setting->golongans()->sync($validated['golongan_ids']);

        return redirect()->route('settings.index')->with('success', 'Setting jam kerja berhasil ditambahkan');
    }

    public function edit(WorkSetting $setting)
    {
        $golongans = Golongan::orderBy('name')->get();
        return view('settings.edit', compact('setting', 'golongans'));
    }

    public function update(Request $request, WorkSetting $setting)
    {
        $data = $request->all();
        $data['golongan_ids'] = $data['golongan_ids'] ?? [];

        $validated = Validator::make($data, [
            'name' => 'required|string|max:255',
            'day' => 'nullable|array',
            'day.*' => 'in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'golongan_ids' => 'nullable|array',
            'golongan_ids.*' => 'exists:golongans,id',
            'check_in_time' => 'required',
            'check_out_time' => 'required',
            'break_out_time' => 'required',
            'break_in_time' => 'required',
            'late_tolerance_minutes' => 'required|integer|min:0|max:120',
            'overtime_threshold_minutes' => 'required|integer|min:0|max:240',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ])->validate();

        foreach (['check_in_time', 'check_out_time', 'break_out_time', 'break_in_time'] as $field) {
            $validated[$field] = substr($validated[$field], 0, 5);
        }

        $validated['day'] = ! empty($validated['day']) ? implode(',', $validated['day']) : null;
        $validated['is_active'] = $request->boolean('is_active');

        $setting->update(array_merge(
            array_diff_key($validated, ['golongan_ids' => true]),
            ['golongan_id' => null]
        ));
        $setting->golongans()->sync($validated['golongan_ids']);

        return redirect()->route('settings.index')->with('success', 'Setting berhasil diupdate');
    }

    public function destroy(WorkSetting $setting)
    {
        $setting->delete();
        return redirect()->route('settings.index')->with('success', 'Setting berhasil dihapus');
    }
}
