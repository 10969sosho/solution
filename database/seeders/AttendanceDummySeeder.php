<?php

namespace Database\Seeders;

use App\Models\AttendanceCompilation;
use App\Models\AttendanceLog;
use App\Models\DailyAttendance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceDummySeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::where('status', 'active')->orderBy('id')->get();

        foreach ([7, 8, 9] as $month) {
            $fixed = $month === 7;
            $start = Carbon::create(2026, $month, 1);

            for ($date = $start->copy(); $date->month === $month; $date->addDay()) {
                $dateString = $date->toDateString();
                AttendanceCompilation::updateOrCreate(
                    ['date' => $dateString],
                    ['status' => $fixed ? 'fix' : 'draft']
                );

                foreach ($employees as $employee) {
                    foreach (['08:00:00', '12:00:00', '13:00:00', '17:00:00'] as $scanTime) {
                        AttendanceLog::firstOrCreate([
                            'machine_sn' => "SEED-ATT-2026-{$month}",
                            'user_id' => $employee->employee_id,
                            'scan_time' => $date->copy()->setTimeFromTimeString($scanTime),
                        ], ['status' => 0, 'user_agent' => 'AttendanceDummySeeder']);
                    }

                    DailyAttendance::updateOrCreate(
                        ['date' => $dateString, 'employee_id' => $employee->id],
                        [
                            'check_in' => '08:00:00',
                            'break_out' => '12:00:00',
                            'break_in' => '13:00:00',
                            'check_out' => '17:00:00',
                            'late_check_in_minutes' => 0,
                            'late_break_in_minutes' => 0,
                            'overtime_minutes' => 0,
                            'is_anomaly' => false,
                            'anomaly_reason' => null,
                            'keterangan' => 'hadir',
                            'nominal_izin' => 0,
                            'tipe_nominal_izin' => null,
                            'is_fixed' => $fixed,
                        ]
                    );
                }
            }
        }

        $payrollService = app(PayrollService::class);
        foreach ($employees as $employee) {
            $existing = Payroll::where('employee_id', $employee->id)
                ->where('period_year', 2026)
                ->where('period_month', 7)
                ->first();

            if (! $existing || $existing->status !== 'paid') {
                $payroll = $payrollService->generate($employee, 2026, 7);
                $payroll->update(['status' => 'paid']);
            }
        }

        $this->command->info("AttendanceDummySeeder selesai: {$employees->count()} karyawan, Juli FIX/paid, Agustus-September 4 checklock.");
    }
}
