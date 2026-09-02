<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payroll;
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
        $baseSalary = (float) ($employee->gaji?->amount ?? 0);

        $lateDeduction = $this->calculateLateFine($attendance['total_late_minutes']);
        $loanDeduction = $this->calculateLoanDeduction($employee, $year, $month);
        $absenceDeduction = $this->calculateAbsenceDeduction($employee, $year, $month, $attendance);
        $attendanceBonus = $this->calculateAttendanceBonus($employee, $year, $month);

        $totalDeduction = round($lateDeduction + $loanDeduction + $absenceDeduction, 2);
        $totalIncentive = round($attendanceBonus, 2);
        $netSalary = round($baseSalary - $totalDeduction + $totalIncentive, 2);

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
                'total_late_minutes' => $attendance['total_late_minutes'],
                'days_late' => $attendance['days_late'],
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
        $baseSalary = (float) ($employee->gaji?->amount ?? 0);
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

    private function calculateLateFine(int $minutes): float
    {
        if ($minutes <= 0) {
            return 0;
        }

        $tiers = config('payroll.late_fine.tiers', []);
        $fine = 0;
        $remaining = $minutes;
        $previousMax = 0;

        foreach ($tiers as $tier) {
            $max = $tier['max_minutes'] ?? null;
            $rate = (int) $tier['per_minute'];

            if ($max === null) {
                $fine += $remaining * $rate;
                break;
            }

            $tierMinutes = min($remaining, $max - $previousMax);
            if ($tierMinutes > 0) {
                $fine += $tierMinutes * $rate;
                $remaining -= $tierMinutes;
            }

            $previousMax = $max;
            if ($remaining <= 0) {
                break;
            }
        }

        return $fine;
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

        $baseSalary = (float) ($employee->gaji?->amount ?? 0);
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

    private function calculateAttendanceBonus(Employee $employee, int $year, int $month): float
    {
        if (! config('payroll.attendance_bonus.enabled', true)) {
            return 0;
        }

        $hasPermit = $employee->permits()
            ->where('status', 'approved')
            ->whereYear('permit_date', $year)
            ->whereMonth('permit_date', $month)
            ->exists();

        if ($hasPermit) {
            return 0;
        }

        $tier = $employee->salary_tier;
        $byTier = config('payroll.attendance_bonus.by_tier', []);

        return (float) ($byTier[$tier] ?? config('payroll.attendance_bonus.default', 0));
    }
}