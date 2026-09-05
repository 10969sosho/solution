<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Permit;
use App\Models\PotonganTerlambat;
use App\Models\AttendanceLog;
use App\Models\WorkSetting;
use App\Services\AttendanceProcessingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PermitController extends Controller
{
    public function __construct(private AttendanceProcessingService $attendanceService)
    {
    }

    public function index(Request $request)
    {
        $query = Permit::with('employee')->orderBy('permit_date', 'desc');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('start_date')) {
            $query->where('permit_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('permit_date', '<=', $request->end_date);
        }

        $permits = $query->paginate(25);
        $employees = Employee::where('status', 'active')->orderBy('name')->get();

        // Get pending attendance issues for bulk processing section
        $pendingIssues = $this->getPendingAttendanceIssues($request);

        return view('permits.index', compact('permits', 'employees', 'pendingIssues'));
    }

    public function create()
    {
        $employees = Employee::where('status', 'active')->orderBy('name')->get();
        $potonganTerlamats = PotonganTerlambat::with('golongan')->get();
        return view('permits.create', compact('employees', 'potonganTerlamats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'category' => 'required|in:tidak_masuk,terlambat,pulang_awal',
            'late_type' => 'nullable|in:masuk_kerja,setelah_istirahat',
            'permit_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'reason' => 'required|string|max:500',
            'location' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'deduction_type' => 'required|in:no_deduction,salary_deduction',
            'deduction_hours' => 'nullable|integer|min:0',
            'deduction_minutes' => 'nullable|integer|min:0',
            'late_minutes' => 'nullable|integer|min:1',
            'late_fine_amount' => 'nullable|numeric|min:0',
        ]);

        $duration = $this->durationInMinutes($validated['start_time'], $validated['end_time']);
        $validated['duration_minutes'] = $duration;
        $validated['type'] = $this->determineType($duration, $validated['deduction_type']);
        $validated['status'] = 'approved';

        Permit::create($validated);

        return redirect()->route('permits.index')->with('success', 'Izin berhasil dicatat');
    }

    public function storeBulk(Request $request)
    {
        $validated = $request->validate([
            'permits' => 'required|array|min:1',
            'permits.*.employee_id' => 'required|exists:employees,id',
            'permits.*.permit_date' => 'required|date',
            'permits.*.category' => 'required|in:tidak_masuk,terlambat,pulang_awal',
            'permits.*.late_type' => 'nullable|in:masuk_kerja,setelah_istirahat',
            'permits.*.late_minutes' => 'nullable|integer|min:1',
            'permits.*.late_fine_amount' => 'nullable|numeric|min:0',
            'permits.*.deduction_type' => 'required|in:no_deduction,salary_deduction',
            'permits.*.reason' => 'nullable|string|max:500',
        ]);

        $created = 0;
        foreach ($validated['permits'] as $permitData) {
            $employee = Employee::find($permitData['employee_id']);
            $workSetting = WorkSetting::getActiveForGolongan($employee->golongan_id, Carbon::parse($permitData['permit_date']));

            $permitData['start_time'] = $workSetting ? substr($workSetting->check_in_time, 0, 5) : '07:00';
            $permitData['end_time'] = $workSetting ? substr($workSetting->check_out_time, 0, 5) : '17:00';
            $permitData['duration_minutes'] = $permitData['late_minutes'] ?? 0;
            $permitData['type'] = $this->determineType($permitData['duration_minutes'], $permitData['deduction_type']);
            $permitData['status'] = 'approved';
            $permitData['reason'] = $permitData['reason'] ?? 'Diproses dari laporan absensi';

            Permit::create($permitData);
            $created++;
        }

        return redirect()->route('permits.index')->with('success', "{$created} izin berhasil dicatat");
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

    private function getPendingAttendanceIssues(Request $request)
    {
        $startDate = $request->input('issue_start_date', now()->subDays(7)->toDateString());
        $endDate = $request->input('issue_end_date', now()->toDateString());

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $employees = Employee::where('status', 'active')->get();
        $issues = [];

        foreach ($employees as $employee) {
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                if ($date->isWeekend()) continue;

                $existingPermit = Permit::where('employee_id', $employee->id)
                    ->where('permit_date', $date->toDateString())
                    ->exists();

                if ($existingPermit) continue;

                $day = $this->attendanceService->processDay($employee, $date->copy());

                if ($day['late_minutes'] > 0 || $day['early_leave_minutes'] > 0) {
                    $issues[] = [
                        'employee' => $employee,
                        'date' => $date->toDateString(),
                        'check_in' => $day['check_locks']['check_in']
                            ? $day['check_locks']['check_in']['scan_time']->format('H:i')
                            : '-',
                        'check_out' => $day['check_locks']['check_out']
                            ? $day['check_locks']['check_out']['scan_time']->format('H:i')
                            : '-',
                        'late_minutes' => $day['late_minutes'],
                        'early_leave_minutes' => $day['early_leave_minutes'],
                    ];
                }
            }
        }

        return $issues;
    }

    private function determineType(int $durationMinutes, string $deductionType): string
    {
        if ($deductionType === 'salary_deduction') {
            return Permit::TYPE_SALARY_DEDUCTION;
        }
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