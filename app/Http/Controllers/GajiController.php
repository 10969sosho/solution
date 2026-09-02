<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class GajiController extends Controller
{
    public function index()
    {
        $employees = Employee::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('gajis.index', compact('employees'));
    }

    public function edit(Employee $employee)
    {
        return view('gajis.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'salary' => 'required|numeric|min:0',
            'salary_tier' => 'nullable|string|max:50',
        ]);

        $employee->update($validated);

        return redirect()->route('gajis.index')->with('success', 'Gaji ' . $employee->name . ' berhasil diupdate');
    }
}
