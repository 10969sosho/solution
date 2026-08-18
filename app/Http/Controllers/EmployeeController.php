<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::query()
            ->when(! auth()->user()->isSuperAdmin(), fn ($q) => $this->operationalScope($q))
            ->orderBy('employee_id')
            ->get();

        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|unique:employees,employee_id',
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'join_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,resigned',
            'salary' => 'nullable|numeric|min:0',
            'salary_tier' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        if (! auth()->user()->isSuperAdmin()) {
            $this->ensureOperationalPosition($validated);
            unset($validated['salary'], $validated['salary_tier']);
        }

        Employee::create($validated);

        return redirect()->route('employees.index')->with('success', 'Karyawan berhasil ditambahkan');
    }

    public function edit(Employee $employee)
    {
        $this->authorizeEmployee($employee);

        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $this->authorizeEmployee($employee);

        $validated = $request->validate([
            'employee_id' => 'required|unique:employees,employee_id,' . $employee->id,
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'join_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,resigned',
            'salary' => 'nullable|numeric|min:0',
            'salary_tier' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        if (! auth()->user()->isSuperAdmin()) {
            $this->ensureOperationalPosition($validated);
            unset($validated['salary'], $validated['salary_tier']);
        }

        $employee->update($validated);

        return redirect()->route('employees.index')->with('success', 'Karyawan berhasil diupdate');
    }

    public function destroy(Employee $employee)
    {
        $this->authorizeEmployee($employee);

        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Karyawan berhasil dihapus');
    }

    private function operationalScope($query)
    {
        return $query->whereIn('position', config('hrms.operational_positions', []));
    }

    private function authorizeEmployee(Employee $employee): void
    {
        if (! auth()->user()->isSuperAdmin()
            && ! in_array($employee->position, config('hrms.operational_positions', []), true)) {
            abort(403, 'Anda tidak memiliki akses ke karyawan non-operasional.');
        }
    }

    private function ensureOperationalPosition(array &$validated): void
    {
        $position = $validated['position'] ?? null;

        if (! in_array($position, config('hrms.operational_positions', []), true)) {
            abort(403, 'Admin operasional hanya dapat mengelola karyawan Gudang/Kandang.');
        }
    }
}