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
     * Hitung rincian gaji satu karyawan untuk periode tertentu sesuai Briefing.
     * Formula:
     * GRAND TOTAL = GAJI POKOK - POTONGAN TERLAMBAT - IZIN POTONG GAJI + IZIN TAMBAH GAJI
     *               - PINJAMAN - POTONGAN MASUK + BONUSAN + UANG JAGA MALAM + NOMINAL LEMBUR
     */
    public function calculate(Employee $employee, int $year, int $month, ?float $manualLoanDeduction = null): array
    {
        $baseSalary = (float) $employee->salary;
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        // 1. Ambil data absensi harian dari DailyAttendance jika ada
        $dailies = \App\Models\DailyAttendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $totalLateInMinutes = 0;
        $totalLateBreakInMinutes = 0;
        $totalIzinCount = 0;
        $izinPotongGaji = 0;
        $izinTambahGaji = 0;
        $totalHariLibur = 0;
        $totalOvertimeMinutes = 0;
        $daysPresent = 0;
        $totalWorkMinutes = 0;

        if ($dailies->isNotEmpty()) {
            foreach ($dailies as $d) {
                if ($d->check_in || $d->check_out) {
                    $daysPresent++;
                }

                if ($d->keterangan === 'izin') {
                    $totalIzinCount++;
                }

                if ($d->isLateIgnored()) {
                    if ($d->tipe_nominal_izin === 'potong_gaji') {
                        $izinPotongGaji += (float) $d->nominal_izin;
                    } elseif ($d->tipe_nominal_izin === 'tambah_gaji') {
                        $izinTambahGaji += (float) $d->nominal_izin;
                    }
                } else {
                    $totalLateInMinutes += (int) $d->late_check_in_minutes;
                    $totalLateBreakInMinutes += (int) $d->late_break_in_minutes;
                }

                if ($d->keterangan === 'libur') {
                    $totalHariLibur++;
                }

                $totalOvertimeMinutes += (int) $d->overtime_minutes;
            }

            // 2. Hitung Potongan Keterlambatan sebulan akumulasi vs Master Potongan Terlambat
            $potonganTerlambatMasuk = $this->calculateLateFine($employee, $totalLateInMinutes, 'masuk_kerja');
            $potonganTerlambatIstirahat = $this->calculateLateFine($employee, $totalLateBreakInMinutes, 'setelah_istirahat');
            $totalPotonganTerlambat = round($potonganTerlambatMasuk + $potonganTerlambatIstirahat, 2);

            // 3. Hitung Jatah Libur & Potongan Masuk
            $quota = $employee->getEffectiveLeaveQuota();
            $hariPotongMasuk = max(0, $totalHariLibur - $quota);
            $sisaLibur = max(0, $quota - $totalHariLibur);

            $dailyDivider = (int) config('payroll_rules.potongan_masuk.divider', 30);
            $dailyRate = $baseSalary > 0 ? ($baseSalary / $dailyDivider) : 0;
            $potonganMasuk = round($dailyRate * $hariPotongMasuk, 2);

            // 4. Hitung Bonus Libur (Pencairan Sisa Libur)
            $bonusLibur = 0;
            if ($sisaLibur > 0 && $baseSalary > 0) {
                $salaryThreshold = (float) config('payroll_rules.bonus_libur.salary_threshold', 2250000);
                if ($baseSalary >= $salaryThreshold) {
                    $multiplier = (float) config('payroll_rules.bonus_libur.high_salary_multiplier', 1.5);
                    $bonusLibur = round($dailyRate * $multiplier * $sisaLibur, 2);
                } else {
                    $perDayRate = (float) config('payroll_rules.bonus_libur.low_salary_per_day', 75000);
                    $bonusLibur = round($perDayRate * $sisaLibur, 2);
                }
            }

            // 5. Uang Jaga Malam
            $uangJagaMalam = 0;
            if ($employee->is_night_guard) {
                if ($employee->isMandor()) {
                    $uangJagaMalam = (float) config('payroll_rules.uang_jaga_malam.mandor_bonus', 600000);
                } else {
                    $uangJagaMalam = (float) config('payroll_rules.uang_jaga_malam.default_bonus', 0);
                }
            }

            // 6. Nominal Lembur
            $hourlyRate = $baseSalary > 0 ? ($baseSalary / 173) : 0;
            $nominalLembur = round(($totalOvertimeMinutes / 60) * $hourlyRate, 2);

            // 7. Pinjaman Karyawan
            $pinjamanDeduction = $manualLoanDeduction !== null
                ? (float) $manualLoanDeduction
                : $this->calculateLoanDeduction($employee, $year, $month);

            // 8. Rumus Grand Total Gaji (Section 14 briefing):
            // GRAND TOTAL = GAJI POKOK - POTONGAN TERLAMBAT - IZIN POTONG GAJI + IZIN TAMBAH GAJI
            //               - PINJAMAN - POTONGAN MASUK + BONUSAN + UANG JAGA MALAM + NOMINAL LEMBUR
            $grandTotal = round(
                $baseSalary
                - $totalPotonganTerlambat
                - $izinPotongGaji
                + $izinTambahGaji
                - $pinjamanDeduction
                - $potonganMasuk
                + $bonusLibur
                + $uangJagaMalam
                + $nominalLembur,
                2
            );

            $totalDeduction = round($totalPotonganTerlambat + $izinPotongGaji + $pinjamanDeduction + $potonganMasuk, 2);
            $totalIncentive = round($izinTambahGaji + $bonusLibur + $uangJagaMalam + $nominalLembur, 2);
            $attendanceBonus = $bonusLibur;
        } else {
            // Fallback memproses via attendance log langsung (backward compatible)
            $attendance = $this->attendanceService->processMonth($employee, $year, $month);
            $permits = Permit::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereYear('permit_date', $year)
                ->whereMonth('permit_date', $month)
                ->get();

            $actualLate = $this->calculateActualLateMinutes($attendance, $permits);
            $totalLateInMinutes = $actualLate['total_late'];
            $totalLateBreakInMinutes = $actualLate['total_late_break_in'];
            $daysPresent = $attendance['days_present'];
            $totalWorkMinutes = $attendance['total_work_minutes'];

            $lateDeduction = $this->calculateLateDeductionWithPermits($employee, $attendance, $permits);
            $pinjamanDeduction = $manualLoanDeduction !== null
                ? (float) $manualLoanDeduction
                : $this->calculateLoanDeduction($employee, $year, $month);
            $potonganMasuk = $this->calculateAbsenceDeduction($employee, $year, $month, $attendance);
            $attendanceBonus = $this->calculateAttendanceBonus($employee, $year, $month, $permits);

            $totalPotonganTerlambat = $lateDeduction;
            $potonganTerlambatMasuk = $lateDeduction;
            $potonganTerlambatIstirahat = 0;
            $bonusLibur = 0;
            $uangJagaMalam = 0;
            $nominalLembur = 0;
            $quota = $employee->getEffectiveLeaveQuota();
            $sisaLibur = 0;
            $hariPotongMasuk = 0;

            $totalDeduction = round($lateDeduction + $pinjamanDeduction + $potonganMasuk, 2);
            $totalIncentive = round($attendanceBonus, 2);
            $grandTotal = round($baseSalary - $totalDeduction + $totalIncentive, 2);
        }

        return [
            'employee_id' => $employee->id,
            'period_year' => $year,
            'period_month' => $month,
            'base_salary' => $baseSalary,
            'late_deduction' => $totalPotonganTerlambat,
            'loan_deduction' => $pinjamanDeduction,
            'absence_deduction' => $potonganMasuk,
            'total_deduction' => $totalDeduction,
            'attendance_bonus' => $attendanceBonus,
            'total_incentive' => $totalIncentive,
            'net_salary' => $grandTotal,
            'total_izin_count' => $totalIzinCount,
            'izin_potong_gaji' => $izinPotongGaji,
            'izin_tambah_gaji' => $izinTambahGaji,
            'potongan_terlambat_masuk' => $potonganTerlambatMasuk,
            'potongan_terlambat_istirahat' => $potonganTerlambatIstirahat,
            'total_potongan_terlambat' => $totalPotonganTerlambat,
            'total_lembur_minutes' => $totalOvertimeMinutes,
            'nominal_lembur' => $nominalLembur,
            'uang_jaga_malam' => $uangJagaMalam,
            'bonus_libur' => $bonusLibur,
            'total_hari_libur' => $totalHariLibur,
            'hari_potong_masuk' => $hariPotongMasuk,
            'potongan_masuk' => $potonganMasuk,
            'pinjaman_deduction' => $pinjamanDeduction,
            'grand_total' => $grandTotal,
            'payment_method' => $employee->payment_method ?? 'transfer',
            'breakdown' => [
                'total_late_minutes' => $totalLateInMinutes,
                'total_late_break_in_minutes' => $totalLateBreakInMinutes,
                'days_present' => $daysPresent,
                'total_work_minutes' => $totalWorkMinutes,
                'leave_quota' => $quota,
                'sisa_libur' => $sisaLibur,
            ],
        ];
    }

    /**
     * Simpan payroll (upsert) untuk satu karyawan. Tidak bisa digenerate ulang jika sudah paid.
     */
    public function generate(Employee $employee, int $year, int $month, ?float $manualLoanDeduction = null): Payroll
    {
        if (Payroll::where('employee_id', $employee->id)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->where('status', 'paid')
            ->exists()) {
            throw new \RuntimeException('Payroll periode ini sudah berstatus paid dan tidak dapat diubah.');
        }

        $data = $this->calculate($employee, $year, $month, $manualLoanDeduction);

        return Payroll::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'period_year' => $year,
                'period_month' => $month,
            ],
            $data
        );
    }

    public function generateAll(int $year, int $month, array $manualLoans = []): array
    {
        $employees = Employee::where('status', 'active')->get();
        $created = [];

        DB::transaction(function () use ($employees, $year, $month, $manualLoans, &$created) {
            foreach ($employees as $employee) {
                $manualLoan = isset($manualLoans[$employee->id]) ? (float) $manualLoans[$employee->id] : null;
                $created[] = $this->generate($employee, $year, $month, $manualLoan);
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