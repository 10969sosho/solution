<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Permit;
use Illuminate\Http\Request;

class PermitController extends Controller
{
    public function index(Request $request)
    {
        $query = Permit::with('employee')->orderBy('permit_date', 'desc');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $permits = $query->paginate(25);
        $employees = Employee::where('status', 'active')->orderBy('name')->get();

        return view('permits.index', compact('permits', 'employees'));
    }

    public function create()
    {
        $employees = Employee::where('status', 'active')->orderBy('name')->get();
        return view('permits.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'permit_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'reason' => 'required|string|max:500',
        ]);

        $duration = $this->durationInMinutes($validated['start_time'], $validated['end_time']);
        $validated['duration_minutes'] = $duration;
        $validated['type'] = $this->determineType($duration);
        $validated['status'] = 'approved';

        Permit::create($validated);

        return redirect()->route('permits.index')->with('success', 'Izin berhasil dicatat');
    }

    public function updateStatus(Request $request, Permit $permit)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $permit->update($validated);

        return redirect()->route('permits.index')->with('success', 'Status izin diperbarui');
    }

    public function destroy(Permit $permit)
    {
        $permit->delete();
        return redirect()->route('permits.index')->with('success', 'Izin berhasil dihapus');
    }

    /**
     * Menentukan jenis izin berdasarkan durasi.
     * - < 15 menit  : tanpa potongan (mendadak/singkat)
     * - > 30 menit  : potong gaji (durasi lama)
     * - 15-30 menit : tanpa potongan (batas aman)
     */
    private function determineType(int $durationMinutes): string
    {
        return $durationMinutes > 30
            ? Permit::TYPE_SALARY_DEDUCTION
            : Permit::TYPE_NO_DEDUCTION;
    }

    private function durationInMinutes(string $start, string $end): int
    {
        $startTime = \Carbon\Carbon::parse($start);
        $endTime = \Carbon\Carbon::parse($end);

        return max(1, (int) $startTime->diffInMinutes($endTime));
    }
}