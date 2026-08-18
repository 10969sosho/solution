<?php

namespace App\Http\Controllers;

use App\Models\SeasonalSchedule;
use Illuminate\Http\Request;

class SeasonalScheduleController extends Controller
{
    public function index()
    {
        $schedules = SeasonalSchedule::orderBy('start_date', 'desc')->get();
        return view('seasonal.index', compact('schedules'));
    }

    public function create()
    {
        return view('seasonal.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateSchedule($request);
        $validated['is_active'] = $request->boolean('is_active');

        SeasonalSchedule::create($validated);

        return redirect()->route('seasonal.index')->with('success', 'Jam kerja musiman berhasil ditambahkan');
    }

    public function edit(SeasonalSchedule $seasonal)
    {
        return view('seasonal.edit', compact('seasonal'));
    }

    public function update(Request $request, SeasonalSchedule $seasonal)
    {
        $validated = $this->validateSchedule($request);
        $validated['is_active'] = $request->boolean('is_active');

        $seasonal->update($validated);

        return redirect()->route('seasonal.index')->with('success', 'Jam kerja musiman berhasil diupdate');
    }

    public function destroy(SeasonalSchedule $seasonal)
    {
        $seasonal->delete();
        return redirect()->route('seasonal.index')->with('success', 'Jam kerja musiman berhasil dihapus');
    }

    private function validateSchedule(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'check_in_delta_minutes' => 'required|integer|min:-180|max:180',
            'check_out_delta_minutes' => 'required|integer|min:-180|max:180',
            'force_check_in_time' => 'nullable|date_format:H:i',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);
    }
}