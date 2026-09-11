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
            ->when(! auth()->user()->isSuperAdmin(), function ($q) {
                $q->whereHas('golongan', fn ($g) => $g->where('is_confidential', false))
                  ->whereIn('position', config('hrms.operational_positions', []));
            })
            ->orderBy('name')
            ->get();

        return view('gajis.index', compact('employees'));
    }

    public function edit(Employee $employee)
    {
        if (! auth()->user()->isSuperAdmin() && $employee->isConfidential()) {
            abort(403, 'Hanya Owner yang berhak melihat dan mengubah gaji karyawan golongan khusus ini.');
        }

        return view('gajis.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        if (! auth()->user()->isSuperAdmin() && $employee->isConfidential()) {
            abort(403, 'Hanya Owner yang berhak melihat dan mengubah gaji karyawan golongan khusus ini.');
        }

        $validated = $request->validate([
            'salary' => 'required|numeric|min:0',
        ]);

        $employee->update($validated);

        return redirect()->route('gajis.index')->with('success', 'Gaji ' . $employee->name . ' berhasil diupdate');
    }
}
