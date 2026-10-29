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
    public function __construct(private AttendanceProcessingService $attendanceService) {}

    /**
     * Hitung rincian gaji satu karyawan untuk periode tertentu sesuai Briefing.
     * Formula:
     * GRAND TOTAL = GAJI POKOK - POTONGAN TERLAMBAT - IZIN POTONG GAJI + IZIN TAMBAH GAJI
     *               - PINJAMAN - POTONGAN MASUK + BONUSAN + UANG JAGA MALAM + NOMINAL LEMBUR
     */
    public function calculate(Employee $employee, int $year, int $month): array
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

                // Aturan izin: hari dengan izin (potong/tidak potong) TIDAK dikenakan
                // potongan terlambat otomatis. Nominal potong izin ditentukan manual
                // lewat menu Manajemen Izin (PermitDeduction) sebelum penggajian.
                if ($d->isLateIgnored()) {
                    if ($d->tipe_nominal_izin === 'potong_gaji') {
                        $izinPotongGaji += (float) $d->nominal_izin; // fallback bila Manajemen Izin belum diisi
                    } elseif ($d->tipe_nominal_izin === 'tambah_gaji') {
                        $izinTambahGaji += (float) $d->nominal_izin;
                    }
                } else {
                    $totalLateInMinutes += (int) $d->late_check_in_minutes;
                    $totalLateBreakInMinutes += (int) $d->late_break_in_minutes;
                }

                if ($d->keterangan === 'libur') {
                    $dateStr = $d->date instanceof \DateTimeInterface ? $d->date->format('Y-m-d') : substr((string) $d->date, 0, 10);
                    $permit = Permit::where('employee_id', $employee->id)
                        ->whereDate('permit_date', $dateStr)
                        ->where('type', Permit::TYPE_LIBUR)
                        ->first();
                    if ($permit && $permit->leave_duration === 'setengah_hari') {
                        $totalHariLibur += 0.5;
                    } else {
                        $totalHariLibur += 1.0;
                    }
                }

                $totalOvertimeMinutes += (int) $d->overtime_minutes;
            }

            // 2. Hitung Potongan Keterlambatan sebulan akumulasi vs Master Potongan Terlambat
            $potonganTerlambatMasuk = $this->calculateLateFine($employee, $totalLateInMinutes, 'masuk_kerja');
            $potonganTerlambatIstirahat = $this->calculateLateFine($employee, $totalLateBreakInMinutes, 'setelah_istirahat');
            $totalPotonganTerlambat = round($potonganTerlambatMasuk + $potonganTerlambatIstirahat, 2);

            // 3. Hitung Jatah Libur & Potongan Masuk (reset tiap tanggal 1 tanpa menghilangkan sisa libur bulan lalu)
            $quota = $employee->getEffectiveLeaveQuotaForMonth($year, $month);
            $hariPotongMasuk = max(0, $totalHariLibur - $quota);
            $sisaLibur = max(0, $quota - $totalHariLibur);

            $dailyDivider = (int) config('payroll_rules.potongan_masuk.divider', 30);
            $dailyRate = $baseSalary > 0 ? ($baseSalary / $dailyDivider) : 0;
            $potonganMasuk = round($dailyRate * $hariPotongMasuk, 2);

            // 4. Hitung Bonus Libur (Pencairan Sisa Libur) - variabel dari Master Bonus Libur
            $bonusLibur = $this->calculateBonusLiburAmount($baseSalary, $dailyRate, $sisaLibur);

            // 5. Uang Jaga Malam (nominal dari Data Karyawan)
            $uangJagaMalam = $this->calculateUangJagaMalam($employee);

            // 6. Nominal Lembur
            $hourlyRate = $baseSalary > 0 ? ($baseSalary / 173) : 0;
            $nominalLembur = round(($totalOvertimeMinutes / 60) * $hourlyRate, 2);

            // 7. Pinjaman Karyawan (dari transaksi Potong Pinjaman)
            $pinjamanDeduction = $this->calculateLoanDeduction($employee, $year, $month);

            // 8. Potongan Izin: nominal dari menu Manajemen Izin (per periode bulan).
            //    Jika belum diinput, fallback ke akumulasi nominal izin harian.
            $manualPotonganIzin = \App\Models\PermitDeduction::amountFor($employee->id, $year, $month);
            if ($manualPotonganIzin !== null) {
                $izinPotongGaji = round($manualPotonganIzin, 2);
            }

            // 9. Rumus Grand Total Gaji (Section 14 briefing):
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
            $pinjamanDeduction = $this->calculateLoanDeduction($employee, $year, $month);

            // Jatah libur & potongan masuk (dihitung dari izin libur approved)
            $totalHariLibur = 0.0;
            foreach ($permits->where('type', Permit::TYPE_LIBUR) as $liburPermit) {
                $totalHariLibur += $liburPermit->leave_duration === 'setengah_hari' ? 0.5 : 1.0;
            }

            $quota = $employee->getEffectiveLeaveQuotaForMonth($year, $month);
            $hariPotongMasuk = max(0, $totalHariLibur - $quota);
            $sisaLibur = max(0, $quota - $totalHariLibur);

            $dailyRate = $baseSalary > 0 ? ($baseSalary / (int) config('payroll_rules.potongan_masuk.divider', 30)) : 0;
            $potonganMasuk = round($dailyRate * $hariPotongMasuk, 2);

            $bonusLibur = $this->calculateBonusLiburAmount($baseSalary, $dailyRate, $sisaLibur);
            $uangJagaMalam = $this->calculateUangJagaMalam($employee);
            $nominalLembur = 0;
            $attendanceBonus = $bonusLibur;

            $totalPotonganTerlambat = $lateDeduction;
            $potonganTerlambatMasuk = $lateDeduction;
            $potonganTerlambatIstirahat = 0;

            // Potongan izin dari menu Manajemen Izin (konsisten dengan jalur DailyAttendance)
            $manualPotonganIzin = \App\Models\PermitDeduction::amountFor($employee->id, $year, $month);
            $izinPotongGaji = round($manualPotonganIzin ?? 0, 2);

            $totalDeduction = round($lateDeduction + $izinPotongGaji + $pinjamanDeduction + $potonganMasuk, 2);
            $totalIncentive = round($bonusLibur + $uangJagaMalam, 2);
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
                // Payroll paid tidak boleh diubah, tapi karyawan lain tetap digenerate (bukan 500).
                $paidExists = Payroll::where('employee_id', $employee->id)
                    ->where('period_year', $year)
                    ->where('period_month', $month)
                    ->where('status', 'paid')
                    ->exists();
                if ($paidExists) {
                    continue;
                }

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

    /**
     * Bonus Libur (Pencairan Sisa Jatah Libur).
     * Variabel A/B/C/D diambil dari Master Bonus Libur.
     */
    private function calculateBonusLiburAmount(float $baseSalary, float $dailyRate, float $sisaLibur): float
    {
        if ($sisaLibur <= 0 || $baseSalary <= 0) {
            return 0;
        }

        $setting = \App\Models\BonusLiburSetting::current();

        if ($baseSalary >= (float) $setting->batas_gaji) {
            return round($dailyRate * (float) $setting->pengali_bonus * $sisaLibur, 2);
        }

        $fullDays = floor($sisaLibur);
        $hasHalfDay = ($sisaLibur - $fullDays) >= 0.5;

        return round(
            ($fullDays * (float) $setting->bonus_satu_hari)
            + ($hasHalfDay ? (float) $setting->bonus_setengah_hari : 0),
            2
        );
    }

    /**
     * Uang Jaga Malam bulanan. Nominal diambil dari Data Karyawan,
     * fallback ke ketentuan config untuk Mandor jaga malam.
     */
    private function calculateUangJagaMalam(Employee $employee): float
    {
        if (! $employee->is_night_guard) {
            return 0;
        }

        if ((float) $employee->uang_jaga_malam > 0) {
            return round((float) $employee->uang_jaga_malam, 2);
        }

        if ($employee->isMandor()) {
            return (float) config('payroll_rules.uang_jaga_malam.mandor_bonus', 600000);
        }

        return (float) config('payroll_rules.uang_jaga_malam.default_bonus', 0);
    }

    private function calculateLoanDeduction(Employee $employee, int $year, int $month): float
    {
        // Potongan pinjaman diambil dari transaksi Potong Pinjaman (loan_payments)
        // pada periode berjalan, bukan diisi manual di baris payroll.
        return (float) \App\Models\LoanPayment::where('employee_id', $employee->id)
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
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

    /**
     * Hitung potongan terlambat dengan aturan izin:
     * - Hari TANPA izin → potongan otomatis sesuai master PotonganTerlambat.
     * - Hari DENGAN izin (terlambat/anomali) → TIDAK dikenakan potongan terlambat
     *   otomatis; potongannya ditentukan manual lewat menu Manajemen Izin.
     */
    private function calculateLateDeductionWithPermits(Employee $employee, array $attendance, $permits): float
    {
        $totalDeduction = 0;

        foreach ($attendance['daily_details'] as $day) {
            if (! $day['present']) {
                continue;
            }

            $dayPermits = $permits->filter(fn ($p) => $p->permit_date->toDateString() === $day['date']);
            if ($dayPermits->isNotEmpty()) {
                continue;
            }

            if ($day['late_minutes'] > 0) {
                $totalDeduction += $this->calculateLateFine($employee, $day['late_minutes'], 'masuk_kerja');
            }

            if (($day['late_break_in_minutes'] ?? 0) > 0) {
                $totalDeduction += $this->calculateLateFine($employee, $day['late_break_in_minutes'], 'setelah_istirahat');
            }
        }

        return round($totalDeduction, 2);
    }

    /**
     * Hitung menit terlambat aktual. Hari yang memiliki izin dikecualikan
     * dari akumulasi keterlambatan (potongan ditentukan via Manajemen Izin).
     */
    private function calculateActualLateMinutes(array $attendance, $permits): array
    {
        $totalLate = 0;
        $daysLate = 0;
        $totalLateBreakIn = 0;
        $daysLateBreakIn = 0;

        foreach ($attendance['daily_details'] as $day) {
            if (! $day['present']) {
                continue;
            }

            $dayPermits = $permits->filter(fn ($p) => $p->permit_date->toDateString() === $day['date']);
            if ($dayPermits->isNotEmpty()) {
                continue;
            }

            if ($day['late_minutes'] > 0) {
                $totalLate += $day['late_minutes'];
                $daysLate++;
            }

            if (($day['late_break_in_minutes'] ?? 0) > 0) {
                $totalLateBreakIn += $day['late_break_in_minutes'];
                $daysLateBreakIn++;
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
