<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Jabatan;
use App\Models\Golongan;
use App\Models\Lokasi;
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
        $jabatans = Jabatan::orderBy('name')->get();
        $golongans = Golongan::orderBy('name')->get();
        $lokasis = Lokasi::orderBy('name')->get();

        return view('employees.create', compact('jabatans', 'golongans', 'lokasis'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|unique:employees,employee_id',
            'name' => 'required|string|max:255',
            'jabatan_id' => 'nullable|exists:jabatans,id',
            'golongan_id' => 'nullable|exists:golongans,id',
            'lokasi_id' => 'nullable|exists:lokasis,id',
            'department' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'join_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,resigned',
            'tanggal_keluar' => 'nullable|date',
            'jam_masuk_normal' => 'nullable|string',
            'salary' => 'nullable|numeric|min:0',
            'salary_tier' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        if (isset($validated['jam_masuk_normal']) && $validated['jam_masuk_normal']) {
            $validated['jam_masuk_normal'] = substr($validated['jam_masuk_normal'], 0, 5);
        }

        if (! auth()->user()->isSuperAdmin()) {
            $this->ensureOperationalPosition($validated);
            unset($validated['salary'], $validated['salary_tier']);
        }

        $validated['position'] = $validated['jabatan_id'] ? Jabatan::find($validated['jabatan_id'])->name : null;

        Employee::create($validated);

        return redirect()->route('employees.index')->with('success', 'Karyawan berhasil ditambahkan');
    }

    public function show(Employee $employee)
    {
        $this->authorizeEmployee($employee);
        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $this->authorizeEmployee($employee);

        $jabatans = Jabatan::orderBy('name')->get();
        $golongans = Golongan::orderBy('name')->get();
        $lokasis = Lokasi::orderBy('name')->get();

        return view('employees.edit', compact('employee', 'jabatans', 'golongans', 'lokasis'));
    }

    public function update(Request $request, Employee $employee)
    {
        $this->authorizeEmployee($employee);

        $validated = $request->validate([
            'employee_id' => 'required|unique:employees,employee_id,' . $employee->id,
            'name' => 'required|string|max:255',
            'jabatan_id' => 'nullable|exists:jabatans,id',
            'golongan_id' => 'nullable|exists:golongans,id',
            'lokasi_id' => 'nullable|exists:lokasis,id',
            'department' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'join_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,resigned',
            'tanggal_keluar' => 'nullable|date',
            'jam_masuk_normal' => 'nullable|string',
            'salary' => 'nullable|numeric|min:0',
            'salary_tier' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        if (isset($validated['jam_masuk_normal']) && $validated['jam_masuk_normal']) {
            $validated['jam_masuk_normal'] = substr($validated['jam_masuk_normal'], 0, 5);
        }

        if (! auth()->user()->isSuperAdmin()) {
            $this->ensureOperationalPosition($validated);
            unset($validated['salary'], $validated['salary_tier']);
        }

        $validated['position'] = $validated['jabatan_id'] ? Jabatan::find($validated['jabatan_id'])->name : null;

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