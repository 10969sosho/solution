<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Permit;
use App\Models\PotonganTerlambat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function __construct(private AttendanceProcessingService $attendanceService)
    {
    }

    /**
     * Hitung rincian gaji satu karyawan untuk periode tertentu.
     *
     * Komponen:
     * - Deduksi: cicilan pinjaman, denda keterlambatan (bertingkat), pemotongan ketidakhadiran.
     * - Insentif: bonus kehadiran (tidak mengambil jatah libur).
     */
    public function calculate(Employee $employee, int $year, int $month): array
    {
        $attendance = $this->attendanceService->processMonth($employee, $year, $month);
        $baseSalary = (float) $employee->salary;

        // Get all approved permits for this month
        $permits = Permit::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereYear('permit_date', $year)
            ->whereMonth('permit_date', $month)
            ->get();

        $lateDeduction = $this->calculateLateDeductionWithPermits($employee, $attendance, $permits);
        $loanDeduction = $this->calculateLoanDeduction($employee, $year, $month);
        $absenceDeduction = $this->calculateAbsenceDeduction($employee, $year, $month, $attendance);
        $attendanceBonus = $this->calculateAttendanceBonus($employee, $year, $month, $permits);

        $totalDeduction = round($lateDeduction + $loanDeduction + $absenceDeduction, 2);
        $totalIncentive = round($attendanceBonus, 2);
        $netSalary = round($baseSalary - $totalDeduction + $totalIncentive, 2);

        // Calculate actual late minutes (after permit deductions)
        $actualLateMinutes = $this->calculateActualLateMinutes($attendance, $permits);

        return [
            'employee_id' => $employee->id,
            'period_year' => $year,
            'period_month' => $month,
            'base_salary' => $baseSalary,
            'late_deduction' => $lateDeduction,
            'loan_deduction' => $loanDeduction,
            'absence_deduction' => $absenceDeduction,
            'total_deduction' => $totalDeduction,
            'attendance_bonus' => $attendanceBonus,
            'total_incentive' => $totalIncentive,
            'net_salary' => $netSalary,
            'breakdown' => [
                'total_late_minutes' => $actualLateMinutes['total_late'],
                'days_late' => $actualLateMinutes['days_late'],
                'total_late_break_in_minutes' => $actualLateMinutes['total_late_break_in'],
                'days_late_break_in' => $actualLateMinutes['days_late_break_in'],
                'days_present' => $attendance['days_present'],
                'total_work_minutes' => $attendance['total_work_minutes'],
            ],
        ];
    }

    /**
     * Simpan payroll (upsert) untuk satu karyawan. Tidak bisa digenerate ulang jika sudah paid.
     */
    public function generate(Employee $employee, int $year, int $month): Payroll
    {
        if (Payroll::where('employee_id', $employee->id)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->where('status', 'paid')
            ->exists()) {
            throw new \RuntimeException('Payroll periode ini sudah berstatus paid dan tidak dapat diubah.');
        }

        $data = $this->calculate($employee, $year, $month);

        return Payroll::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'period_year' => $year,
                'period_month' => $month,
            ],
            $data
        );
    }

    public function generateAll(int $year, int $month): array
    {
        $employees = Employee::where('status', 'active')->get();
        $created = [];

        DB::transaction(function () use ($employees, $year, $month, &$created) {
            foreach ($employees as $employee) {
                $created[] = $this->generate($employee, $year, $month);
            }
        });

        return $created;
    }

    public function markPaid(Payroll $payroll): Payroll
    {
        $payroll->update(['status' => 'paid']);
        return $payroll;
    }

    /**
     * Kalkulasi THR dengan diferensiasi masa kerja.
     * Masa kerja >= 5 tahun: THR penuh (1x gaji pokok).
     * Masa kerja < 5 tahun: proporsional sesuai bulan kerja pada tahun berjalan.
     */
    public function calculateThr(Employee $employee, int $year): array
    {
        $baseSalary = (float) $employee->salary;
        $longServiceMonths = (int) config('payroll.thr.long_service_months', 60);

        if (! $employee->join_date) {
            return ['thr' => 0, 'tenure_months' => 0, 'long_service' => false];
        }

        $reference = Carbon::create($year, 12, 31);
        $tenureMonths = max(0, (int) $employee->join_date->startOfDay()->diffInMonths($reference));
        $longService = $tenureMonths >= $longServiceMonths;

        if ($longService) {
            $thr = round($baseSalary * (float) config('payroll.thr.long_service_rate', 1.0), 2);
        } else {
            $start = $employee->join_date->copy()->startOfMonth()->greaterThan(Carbon::create($year, 1, 1))
                ? $employee->join_date->copy()->startOfMonth()
                : Carbon::create($year, 1, 1);
            $monthsThisYear = max(1, (int) $start->diffInMonths($reference->copy()->startOfMonth()) + 1);
            $ratio = min(1.0, $monthsThisYear / 12);
            $thr = round($baseSalary * $ratio, 2);
        }

        return [
            'thr' => $thr,
            'tenure_months' => $tenureMonths,
            'long_service' => $longService,
        ];
    }

    private function calculateLateFine(Employee $employee, int $minutes, string $type = 'masuk_kerja'): float
    {
        if ($minutes <= 0) {
            return 0;
        }

        $golonganId = $employee->golongan_id;

        if (! $golonganId) {
            return 0;
        }

        $potongan = PotonganTerlambat::where('golongan_id', $golonganId)
            ->where('type', $type)
            ->where('min_minutes', '<=', $minutes)
            ->where(function ($query) use ($minutes) {
                $query->whereNull('max_minutes')
                    ->orWhere('max_minutes', '>=', $minutes);
            })
            ->orderBy('min_minutes', 'desc')
            ->first();

        return $potongan ? (float) $potongan->amount : 0;
    }

    private function calculateLoanDeduction(Employee $employee, int $year, int $month): float
    {
        return (float) $employee->loans()
            ->where('status', 'active')
            ->with(['payments' => function ($q) use ($year, $month) {
                $q->whereYear('payment_date', $year)->whereMonth('payment_date', $month);
            }])
            ->get()
            ->pluck('payments')
            ->flatten()
            ->sum('amount');
    }

    private function calculateAbsenceDeduction(Employee $employee, int $year, int $month, array $attendance): float
    {
        if (! config('payroll.absence_deduction.enabled', true)) {
            return 0;
        }

        $baseSalary = (float) $employee->salary;
        if ($baseSalary <= 0) {
            return 0;
        }

        $workDays = (int) config('payroll.work_days_per_month', 22);
        $dailyRate = $baseSalary / max(1, $workDays);

        $absentDays = collect($attendance['daily_details'])
            ->filter(fn ($day) => ! $day['is_weekend'] && ! $day['present'])
            ->count();

        return round($absentDays * $dailyRate, 2);
    }

    private function calculateAttendanceBonus(Employee $employee, int $year, int $month, $permits = null): float
    {
        if (! config('payroll.attendance_bonus.enabled', true)) {
            return 0;
        }

        if ($permits === null) {
            $permits = $employee->permits()
                ->where('status', 'approved')
                ->whereYear('permit_date', $year)
                ->whereMonth('permit_date', $month)
                ->get();
        }

        if ($permits->isNotEmpty()) {
            return 0;
        }

        $tier = $employee->salary_tier;
        $byTier = config('payroll.attendance_bonus.by_tier', []);

        return (float) ($byTier[$tier] ?? config('payroll.attendance_bonus.default', 0));
    }

    /**
     * Hitung potongan terlambat dengan mempertimbangkan izin karyawan.
     * - Jika ada izin dengan no_deduction untuk hari tersebut → tidak ada potongan
     * - Jika ada izin dengan salary_deduction → gunakan denda dari izin (bukan ketentuan standar)
     * - Jika tidak ada izin → gunakan potongan sesuai ketentuan
     */
    private function calculateLateDeductionWithPermits(Employee $employee, array $attendance, $permits): float
    {
        $totalDeduction = 0;

        foreach ($attendance['daily_details'] as $day) {
            if (!$day['present']) continue;

            $date = $day['date'];
            $lateMinutes = $day['late_minutes'];
            $lateBreakInMinutes = $day['late_break_in_minutes'] ?? 0;

            // Check for permits on this date
            $dayPermits = $permits->filter(fn ($p) => $p->permit_date->toDateString() === $date);

            // Handle late masuk kerja
            if ($lateMinutes > 0) {
                $latePermit = $dayPermits->firstWhere('category', 'terlambat');

                if ($latePermit && $latePermit->late_type === 'masuk_kerja') {
                    // If no_deduction, skip fine
                    if ($latePermit->deduction_type === 'no_deduction') {
                        // No fine
                    } else {
                        // salary_deduction: use permit's fine amount
                        $totalDeduction += (float) ($latePermit->late_fine_amount ?? 0);
                    }
                } else {
                    // No permit, apply standard potongan
                    $totalDeduction += $this->calculateLateFine($employee, $lateMinutes, 'masuk_kerja');
                }
            }

            // Handle late masuk istirahat (setelah istirahat)
            if ($lateBreakInMinutes > 0) {
                $lateBreakPermit = $dayPermits->firstWhere('category', 'terlambat');

                if ($lateBreakPermit && $lateBreakPermit->late_type === 'setelah_istirahat') {
                    if ($lateBreakPermit->deduction_type === 'no_deduction') {
                        // No fine
                    } else {
                        $totalDeduction += (float) ($lateBreakPermit->late_fine_amount ?? 0);
                    }
                } else {
                    $totalDeduction += $this->calculateLateFine($employee, $lateBreakInMinutes, 'setelah_istirahat');
                }
            }

            // Handle pulang awal
            if ($day['early_leave_minutes'] > 0) {
                $earlyPermit = $dayPermits->firstWhere('category', 'pulang_awal');
                if ($earlyPermit && $earlyPermit->deduction_type === 'salary_deduction') {
                    $totalDeduction += (float) ($earlyPermit->late_fine_amount ?? 0);
                }
            }
        }

        return round($totalDeduction, 2);
    }

    /**
     * Hitung menit terlambat aktual (setelah dikurangi izin no_deduction).
     */
    private function calculateActualLateMinutes(array $attendance, $permits): array
    {
        $totalLate = 0;
        $daysLate = 0;
        $totalLateBreakIn = 0;
        $daysLateBreakIn = 0;

        foreach ($attendance['daily_details'] as $day) {
            if (!$day['present']) continue;

            $date = $day['date'];
            $dayPermits = $permits->filter(fn ($p) => $p->permit_date->toDateString() === $date);

            // Late masuk kerja
            if ($day['late_minutes'] > 0) {
                $latePermit = $dayPermits->firstWhere('category', 'terlambat');
                if ($latePermit && $latePermit->late_type === 'masuk_kerja' && $latePermit->deduction_type === 'no_deduction') {
                    // Exempt from late count
                } else {
                    $totalLate += $day['late_minutes'];
                    $daysLate++;
                }
            }

            // Late masuk istirahat
            if (($day['late_break_in_minutes'] ?? 0) > 0) {
                $lateBreakPermit = $dayPermits->firstWhere('category', 'terlambat');
                if ($lateBreakPermit && $lateBreakPermit->late_type === 'setelah_istirahat' && $lateBreakPermit->deduction_type === 'no_deduction') {
                    // Exempt
                } else {
                    $totalLateBreakIn += $day['late_break_in_minutes'];
                    $daysLateBreakIn++;
                }
            }
        }

        return [
            'total_late' => $totalLate,
            'days_late' => $daysLate,
            'total_late_break_in' => $totalLateBreakIn,
            'days_late_break_in' => $daysLateBreakIn,
        ];
    }
}