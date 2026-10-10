<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class GajiController extends Controller
{
    public function index()
    {
        $employees = Employee::where('status', 'active')
            ->with(['golongan', 'jabatan'])
            ->orderBy('name')
            ->get();

        return view('gajis.index', compact('employees'));
    }

    public function edit(Employee $employee)
    {
        if (! auth()->user()->isSuperAdmin() && $employee->isMandorOrAdmin()) {
            abort(403, 'Gaji pokok golongan Mandor dan Admin hanya dapat diakses dan diedit oleh Owner.');
        }

        return view('gajis.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        if (! auth()->user()->isSuperAdmin() && $employee->isMandorOrAdmin()) {
            abort(403, 'Gaji pokok golongan Mandor dan Admin hanya dapat diubah oleh Owner.');
        }

        $validated = $request->validate([
            'salary' => 'required|numeric|min:0',
        ]);

        $employee->update($validated);

        return redirect()->route('gajis.index')->with('success', 'Gaji ' . $employee->name . ' berhasil diupdate');
    }
}
