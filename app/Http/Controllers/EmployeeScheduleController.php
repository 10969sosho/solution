<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use Illuminate\Http\Request;

class EmployeeScheduleController extends Controller
{
    public function index()
    {
        $schedules = EmployeeSchedule::with('employee')->orderBy('employee_id')->get();
        return view('schedules.index', compact('schedules'));
    }

    public function create()
    {
        $employees = Employee::where('status', 'active')->orderBy('name')->get();
        return view('schedules.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateSchedule($request);

        if ($request->boolean('is_active')) {
            EmployeeSchedule::where('employee_id', $validated['employee_id'])->update(['is_active' => false]);
        }

        $validated['is_active'] = $request->boolean('is_active');

        EmployeeSchedule::create($validated);

        return redirect()->route('schedules.index')->with('success', 'Jam kerja khusus berhasil ditambahkan');
    }

    public function edit(EmployeeSchedule $schedule)
    {
        $employees = Employee::where('status', 'active')->orderBy('name')->get();
        return view('schedules.edit', compact('schedule', 'employees'));
    }

    public function update(Request $request, EmployeeSchedule $schedule)
    {
        $validated = $this->validateSchedule($request);

        if ($request->boolean('is_active')) {
            EmployeeSchedule::where('employee_id', $validated['employee_id'])
                ->where('id', '!=', $schedule->id)
                ->update(['is_active' => false]);
        }

        $validated['is_active'] = $request->boolean('is_active');

        $schedule->update($validated);

        return redirect()->route('schedules.index')->with('success', 'Jam kerja khusus berhasil diupdate');
    }

    public function destroy(EmployeeSchedule $schedule)
    {
        $schedule->delete();
        return redirect()->route('schedules.index')->with('success', 'Jam kerja khusus berhasil dihapus');
    }

    private function validateSchedule(Request $request): array
    {
        return $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'name' => 'nullable|string|max:255',
            'check_in_time' => 'required|date_format:H:i',
            'break_out_time' => 'required|date_format:H:i',
            'break_in_time' => 'required|date_format:H:i|after:break_out_time',
            'check_out_time' => 'required|date_format:H:i|after:break_in_time',
            'late_tolerance_minutes' => 'required|integer|min:0|max:120',
            'effective_from' => 'nullable|date',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);
    }
}