<?php

namespace Database\Seeders;

use App\Models\AttendanceCompilation;
use App\Models\DailyAttendance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Absen dummy lanjutan 25-30 September 2026 dengan jam ANOMALI:
 * jam kosong (tanpa masuk/pulang), absen masuk terlambat, lupa checkout, scan istirahat bolong.
 * Idempotent (updateOrCreate) - aman dijalankan berulang.
 */
class AttendanceAnomalySeptemberSeeder extends Seeder
{
    private const YEAR = 2026;

    private const MONTH = 9;

    private const START_DAY = 25;

    private const END_DAY = 30;

    /**
     * [check_in, break_out, break_in, check_out, late_in_minutes, late_break_in_minutes, alasan]
     */
    private const SCENARIOS = [
        ['08:00:00', '12:00:00', '13:00:00', null, 0, 0, 'Lupa checkout / absen pulang kosong'],
        ['08:47:00', '12:00:00', '13:00:00', '17:00:00', 47, 0, 'Absen masuk terlambat'],
        [null, '12:00:00', '13:00:00', '17:00:00', 0, 0, 'Absen masuk kosong'],
        ['08:00:00', null, null, '17:00:00', 0, 0, 'Scan istirahat bolong'],
        ['09:15:00', null, '13:00:00', null, 75, 0, 'Absen masuk terlambat & lupa checkout'],
        [null, null, null, null, 0, 0, 'Seluruh jam absen kosong'],
    ];

    public function run(): void
    {
        $employees = Employee::where('status', 'active')->orderBy('id')->get();

        if ($employees->isEmpty()) {
            $this->command->error('Belum ada karyawan aktif. Jalankan DummyDataSeeder dulu.');

            return;
        }

        foreach ($employees as $index => $employee) {
            for ($day = self::START_DAY; $day <= self::END_DAY; $day++) {
                $dateString = Carbon::create(self::YEAR, self::MONTH, $day)->toDateString();

                AttendanceCompilation::updateOrCreate(
                    ['date' => $dateString],
                    ['status' => 'draft']
                );

                [$checkIn, $breakOut, $breakIn, $checkOut, $lateIn, $lateBreak, $reason] =
                    self::SCENARIOS[($index + $day - self::START_DAY) % count(self::SCENARIOS)];

                DailyAttendance::updateOrCreate(
                    ['date' => $dateString, 'employee_id' => $employee->id],
                    [
                        'check_in' => $checkIn,
                        'break_out' => $breakOut,
                        'break_in' => $breakIn,
                        'check_out' => $checkOut,
                        'late_check_in_minutes' => $lateIn,
                        'late_break_in_minutes' => $lateBreak,
                        'overtime_minutes' => 0,
                        'is_anomaly' => true,
                        'anomaly_reason' => $reason,
                        'keterangan' => ($checkIn || $checkOut) ? 'hadir' : 'alpha',
                        'nominal_izin' => 0,
                        'tipe_nominal_izin' => null,
                        'is_fixed' => false,
                        'edited_fields' => null,
                    ]
                );
            }
        }

        // Payroll September (belum paid) digenerate ulang agar ikut memakai data absen terbaru.
        app(PayrollService::class)->generateAll(self::YEAR, self::MONTH);

        $this->command->info(sprintf(
            'AttendanceAnomalySeptemberSeeder selesai: %d karyawan x %d-%d %s 2026 (anomali), payroll non-paid September digenerate ulang (%d baris).',
            $employees->count(),
            self::START_DAY,
            self::END_DAY,
            'September',
            Payroll::where('period_year', self::YEAR)->where('period_month', self::MONTH)->count()
        ));
    }
}
