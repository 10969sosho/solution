<?php

namespace Database\Seeders;

use App\Models\AttendanceCompilation;
use App\Models\AttendanceLog;
use App\Models\DailyAttendance;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Payroll;
use App\Models\Permit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AugustTestingSeeder extends Seeder
{
    public function run(): void
    {
        $year = 2026;
        $month = 8;
        $employees = Employee::where('status', 'active')->orderBy('id')->get()->values();

        if ($employees->isEmpty()) {
            $this->command->error('Tidak ada karyawan aktif. Seeder dibatalkan.');
            return;
        }

        $logs = $daily = $permits = $loans = $payments = $payrolls = 0;
        $start = Carbon::create($year, $month, 1);

        for ($date = $start->copy(); $date->month === $month; $date->addDay()) {
            $dateString = $date->toDateString();
            AttendanceCompilation::firstOrCreate(['date' => $dateString], ['status' => 'draft']);

            foreach ($employees as $index => $employee) {
                $weekend = $date->isWeekend();
                $condition = $index % 6;
                $row = $this->attendanceRow($date, $condition, $weekend);
                $scanTimes = $row['scan_times'];
                unset($row['scan_times']);

                $record = DailyAttendance::firstOrCreate(
                    ['date' => $dateString, 'employee_id' => $employee->id],
                    $row
                );
                $daily += $record->wasRecentlyCreated ? 1 : 0;

                foreach ($scanTimes as $scanTime) {
                    $scanDateTime = $date->copy()->setTimeFromTimeString($scanTime)->format('Y-m-d H:i:s');
                    $log = AttendanceLog::firstOrCreate([
                        'machine_sn' => 'SEED-AUG-2026',
                        'user_id' => $employee->employee_id,
                        'scan_time' => $scanDateTime,
                    ], [
                        'status' => 0,
                        'user_agent' => 'AugustTestingSeeder',
                    ]);
                    $logs += $log->wasRecentlyCreated ? 1 : 0;
                }

                if ($condition === 5 && ! $weekend && $date->day === 6) {
                    $permit = Permit::firstOrCreate([
                        'employee_id' => $employee->id,
                        'permit_date' => $dateString,
                        'reason' => 'SEED-AUG-2026 izin tanpa potong',
                    ], [
                        'type' => Permit::TYPE_NO_DEDUCTION,
                        'start_time' => '08:00:00',
                        'end_time' => '17:00:00',
                        'duration_minutes' => 480,
                        'status' => 'approved',
                    ]);
                    $permits += $permit->wasRecentlyCreated ? 1 : 0;
                }
            }
        }

        foreach ($employees->take(4) as $index => $employee) {
            $previous = Loan::firstOrCreate([
                'employee_id' => $employee->id,
                'loan_date' => '2026-07-15',
                'description' => 'SEED-AUG-2026 sisa bulan sebelumnya',
            ], [
                'principal' => 1500000 + ($index * 250000),
                'status' => 'active',
            ]);
            $loans += $previous->wasRecentlyCreated ? 1 : 0;

            $current = Loan::firstOrCreate([
                'employee_id' => $employee->id,
                'loan_date' => '2026-08-10',
                'description' => 'SEED-AUG-2026 bon bulan ini',
            ], [
                'principal' => 500000 + ($index * 100000),
                'status' => 'active',
            ]);
            $loans += $current->wasRecentlyCreated ? 1 : 0;

            $payment = LoanPayment::firstOrCreate([
                'loan_id' => $previous->id,
                'employee_id' => $employee->id,
                'payment_date' => '2026-07-25',
                'notes' => 'SEED-AUG-2026 pembayaran sebelumnya',
            ], ['amount' => 250000]);
            $payments += $payment->wasRecentlyCreated ? 1 : 0;

            $loanDeduction = 300000 + ($index * 50000);
            $payroll = Payroll::firstOrCreate([
                'employee_id' => $employee->id,
                'period_year' => $year,
                'period_month' => $month,
            ], $this->payrollRow($employee, $loanDeduction, $index));
            $payrolls += $payroll->wasRecentlyCreated ? 1 : 0;
        }

        $this->command->info("AugustTestingSeeder selesai: employees={$employees->count()}, daily={$daily}, logs={$logs}, permits={$permits}, loans={$loans}, payments={$payments}, payrolls={$payrolls}");
        $this->command->info('Kondisi: tepat waktu, telat, kurang scan, urutan scan salah, alpha, izin, libur, pinjaman, pembayaran, payroll.');
    }

    private function attendanceRow(Carbon $date, int $condition, bool $weekend): array
    {
        if ($weekend) {
            return $this->row(null, null, null, null, 'libur', false, null, []);
        }

        return match ($condition) {
            0 => $this->row('08:00:00', '12:00:00', '13:00:00', '17:00:00', 'hadir', false, null, ['08:00:00', '12:00:00', '13:00:00', '17:00:00']),
            1 => $this->row('08:25:00', '12:00:00', '13:20:00', '17:30:00', 'hadir', true, 'Telat masuk dan scan di luar batas normal', ['08:25:00', '12:00:00', '13:20:00', '17:30:00'], 25, 20, 30),
            2 => $this->row('08:00:00', '12:00:00', null, '17:00:00', 'hadir', true, 'Jumlah scan kurang dari 4 kali', ['08:00:00', '12:00:00', '17:00:00']),
            3 => $this->row('08:00:00', '12:00:00', '13:00:00', '17:00:00', 'hadir', false, null, ['08:00:00', '12:00:00', '13:00:00', '17:00:00']),
            4 => $this->row(null, null, null, null, 'alpha', true, 'Tidak ada scan absensi', []),
            default => $this->row(null, null, null, null, 'izin', false, null, []),
        };
    }

    private function row(?string $checkIn, ?string $breakOut, ?string $breakIn, ?string $checkOut, string $keterangan, bool $anomaly, ?string $reason, array $scanTimes, int $lateIn = 0, int $lateBreakIn = 0, int $overtime = 0): array
    {
        return [
            'check_in' => $checkIn,
            'break_out' => $breakOut,
            'break_in' => $breakIn,
            'check_out' => $checkOut,
            'late_check_in_minutes' => $lateIn,
            'late_break_in_minutes' => $lateBreakIn,
            'overtime_minutes' => $overtime,
            'is_anomaly' => $anomaly,
            'anomaly_reason' => $reason,
            'keterangan' => $keterangan,
            'nominal_izin' => $keterangan === 'izin' ? 100000 : 0,
            'tipe_nominal_izin' => $keterangan === 'izin' ? 'potong_gaji' : null,
            'is_fixed' => false,
            'scan_times' => array_map(fn ($time) => $time, $scanTimes),
        ];
    }

    private function payrollRow(Employee $employee, int $loanDeduction, int $index): array
    {
        $base = (float) ($employee->salary ?: 3000000);
        $late = 100000 + ($index * 25000);
        $absence = 50000;
        $bonus = 75000;
        $total = $late + $loanDeduction + $absence;

        return [
            'base_salary' => $base,
            'late_deduction' => $late,
            'loan_deduction' => $loanDeduction,
            'pinjaman_deduction' => $loanDeduction,
            'absence_deduction' => $absence,
            'total_deduction' => $total,
            'attendance_bonus' => $bonus,
            'total_incentive' => $bonus,
            'net_salary' => $base - $total + $bonus,
            'grand_total' => $base - $total + $bonus,
            'payment_method' => $employee->payment_method ?: 'transfer',
            'breakdown' => ['source' => 'AugustTestingSeeder'],
            'status' => 'draft',
        ];
    }
}
