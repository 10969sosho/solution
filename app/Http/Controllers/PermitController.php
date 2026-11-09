<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Permit;
use App\Models\PotonganTerlambat;
use App\Models\WorkSetting;
use App\Services\AttendanceProcessingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PermitController extends Controller
{
    public function __construct(private AttendanceProcessingService $attendanceService) {}

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
        if ($request->has('permits')) {
            return $this->storeMultiple($request);
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'category' => 'required|in:tidak_masuk,terlambat,pulang_awal,libur',
            'late_type' => 'nullable|in:masuk_kerja,setelah_istirahat',
            'permit_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'reason' => 'required|string|max:500',
            'location' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'type' => 'nullable|in:no_deduction,salary_deduction,addition,libur',
            'leave_duration' => 'nullable|in:1_hari,setengah_hari',
            'deduction_type' => 'nullable|in:no_deduction,salary_deduction',
            'deduction_hours' => 'nullable|integer|min:0',
            'deduction_minutes' => 'nullable|integer|min:0',
            'late_minutes' => 'nullable|integer|min:1',
            'late_fine_amount' => 'nullable|numeric|min:0',
            'nominal' => 'nullable|numeric|min:0|required_if:type,addition',
            'permit_minutes' => 'nullable|integer|min:1|required_if:type,salary_deduction',
        ]);

        $duration = $this->durationInMinutes($validated['start_time'], $validated['end_time']);
        $validated['type'] = $validated['type'] ?? $this->determineType($duration, $validated['deduction_type'] ?? 'no_deduction');
        $minutes = $this->resolveDeductionMinutes($validated, $duration);
        $validated['duration_minutes'] = $validated['type'] === Permit::TYPE_LIBUR ? $duration : $minutes;
        unset($validated['permit_minutes']);

        // Potong gaji dihitung dari MENIT: gaji harian * (menit / menit kerja per hari).
        // Nominal eksplisit tetap menang (dipakai impor/laporan lama).
        $validated['nominal'] = $validated['nominal']
            ?? ($validated['type'] === Permit::TYPE_SALARY_DEDUCTION
                ? $this->deductionAmountFromMinutes($validated['employee_id'], $minutes)
                : 0);
        $validated['status'] = 'approved';

        Permit::create($validated);
        Permit::syncDailyAttendance((int) $validated['employee_id'], Carbon::parse($validated['permit_date'])->toDateString());

        if ($request->input('source') === 'attendance_daily') {
            return redirect()->route('attendance.daily', ['date' => $validated['permit_date']])
                ->with('success', 'Izin berhasil ditambahkan');
        }

        return redirect()->route('permits.index')->with('success', 'Izin berhasil dicatat');
    }

    /**
     * Modal Absen Daily: multiple izin untuk 1 karyawan + 1 tanggal.
     */
    private function storeMultiple(Request $request)
    {
        $rules = [
            'employee_id' => 'required|exists:employees,id',
            'permit_date' => 'required|date',
            'source' => 'nullable|string',
            'permits' => 'required|array|min:1',
            'permits.*.category' => 'required|in:tidak_masuk,terlambat,pulang_awal,libur',
            'permits.*.type' => 'required|in:no_deduction,salary_deduction,addition,libur',
            'permits.*.leave_duration' => 'nullable|in:1_hari,setengah_hari',
            'permits.*.start_time' => 'nullable|date_format:H:i',
            'permits.*.end_time' => 'nullable|date_format:H:i',
            'permits.*.reason' => 'required|string|max:500',
        ];

        $rows = $request->input('permits', []);
        if (is_array($rows)) {
            foreach (array_keys($rows) as $i) {
                $type = $rows[$i]['type'] ?? null;
                // Potong gaji WAJIB menyebut menit: nominal dihitung otomatis dari menit
                // (gaji harian * menit / menit kerja per hari). Izin tidak potong tidak
                // mengurangi gaji harian berapa pun lamanya.
                $rules["permits.$i.nominal"] = ['nullable', 'numeric', 'min:0'];
                $rules["permits.$i.permit_minutes"] = [
                    'nullable',
                    'integer',
                    'min:1',
                    Rule::requiredIf($type === 'salary_deduction'),
                ];
            }
        }

        $validated = $request->validate($rules);
        $employee = Employee::find((int) $validated['employee_id']);

        $count = 0;
        DB::transaction(function () use ($validated, $employee, &$count) {
            foreach ($validated['permits'] as $row) {
                $startTime = $row['start_time'] ?? '08:00';
                $endTime = $row['end_time'] ?? '17:00';
                $duration = $this->durationInMinutes($startTime, $endTime);
                $isLibur = $row['type'] === Permit::TYPE_LIBUR;
                $minutes = $this->resolveDeductionMinutes($row, $duration);

                if (array_key_exists('nominal', $row) && $row['nominal'] !== null) {
                    $nominal = (float) $row['nominal'];
                } elseif ($row['type'] === Permit::TYPE_SALARY_DEDUCTION) {
                    $nominal = $this->deductionAmountFromMinutes($employee, $minutes);
                } else {
                    $nominal = 0;
                }

                Permit::create([
                    'employee_id' => $validated['employee_id'],
                    'permit_date' => $validated['permit_date'],
                    'category' => $row['category'],
                    'type' => $row['type'],
                    'leave_duration' => $row['leave_duration'] ?? null,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'duration_minutes' => $isLibur ? $duration : $minutes,
                    'nominal' => round($nominal, 2),
                    'reason' => $row['reason'],
                    'status' => 'approved',
                ]);
                $count++;
            }
        });

        $dateStr = Carbon::parse($validated['permit_date'])->toDateString();
        Permit::syncDailyAttendance((int) $validated['employee_id'], $dateStr);

        if (($validated['source'] ?? null) === 'attendance_daily') {
            return redirect()->route('attendance.daily', ['date' => $dateStr])
                ->with('success', "{$count} izin berhasil ditambahkan");
        }

        return redirect()->route('permits.index')->with('success', "{$count} izin berhasil dicatat");
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
        $synced = [];
        DB::transaction(function () use ($validated, &$created, &$synced) {
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
                $synced[] = [(int) $permitData['employee_id'], Carbon::parse($permitData['permit_date'])->toDateString()];
                $created++;
            }
        });

        foreach ($synced as [$employeeId, $dateStr]) {
            Permit::syncDailyAttendance($employeeId, $dateStr);
        }

        return redirect()->route('permits.index')->with('success', "{$created} izin berhasil dicatat");
    }

    public function updateStatus(Request $request, Permit $permit)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $permit->update($validated);
        Permit::syncDailyAttendance((int) $permit->employee_id, $permit->permit_date->toDateString());

        return redirect()->route('permits.index')->with('success', 'Status izin diperbarui');
    }

    public function destroy(Permit $permit)
    {
        $employeeId = (int) $permit->employee_id;
        $dateStr = $permit->permit_date->toDateString();
        $permit->delete();
        Permit::syncDailyAttendance($employeeId, $dateStr);

        return redirect()->route('permits.index')->with('success', 'Izin berhasil dihapus');
    }

    /**
     * Nominal potongan izin dari durasi menit:
     * gaji harian (Gaji Pokok / daily_divider) * (menit / menit kerja per hari).
     * Contoh: 120 menit (2 jam) pada hari kerja 480 menit -> 1/4 gaji harian.
     */
    private function deductionAmountFromMinutes($employee, int $minutes): float
    {
        if (! $employee instanceof Employee) {
            $employee = Employee::find((int) $employee);
        }

        if (! $employee || (float) $employee->salary <= 0 || $minutes <= 0) {
            return 0;
        }

        $dailyDivider = max(1, (int) config('payroll_rules.potongan_izin.daily_divider', 30));
        $workMinutesPerDay = max(1, (int) config('payroll_rules.potongan_izin.work_minutes_per_day', 480));
        $dailyRate = (float) $employee->salary / $dailyDivider;

        return round($dailyRate * $minutes / $workMinutesPerDay, 2);
    }

    /**
     * Durasi menit yang dipakai perhitungan: menit izin (jika diisi),
     * lalu durasi jam+menit potongan, terakhir selisih jam mulai/selesai.
     */
    private function resolveDeductionMinutes(array $data, int $duration): int
    {
        $minutes = (int) ($data['permit_minutes'] ?? 0);

        if ($minutes <= 0) {
            $minutes = ((int) ($data['deduction_hours'] ?? 0)) * 60 + (int) ($data['deduction_minutes'] ?? 0);
        }

        if ($minutes <= 0) {
            $minutes = $duration;
        }

        return max(1, $minutes);
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
                if ($date->isWeekend()) {
                    continue;
                }

                $existingPermit = Permit::where('employee_id', $employee->id)
                    ->where('permit_date', $date->toDateString())
                    ->exists();

                if ($existingPermit) {
                    continue;
                }

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
