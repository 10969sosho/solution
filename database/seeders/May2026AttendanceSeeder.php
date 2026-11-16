<?php

namespace Database\Seeders;

use App\Models\AttendanceCompilation;
use App\Models\AttendanceLog;
use App\Models\DailyAttendance;
use App\Models\Device;
use App\Models\Employee;
use App\Models\Permit;
use App\Services\AttendanceProcessingService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class May2026AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $year = 2026;
        $month = 5;
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $totalDays = $end->day; // 31 hari

        $employees = Employee::where('status', 'active')->orderBy('id')->get();

        if ($employees->isEmpty()) {
            $this->command->error('Tidak ada karyawan aktif yang ditemukan.');
            return;
        }

        $activeDevice = Device::where('is_active', true)->first();
        $machineSn = $activeDevice?->serial_number ?: 'NYU7250300070';

        $attendanceService = app(AttendanceProcessingService::class);

        $this->command->info("Memulai seeding absensi Mei 2026 untuk {$employees->count()} karyawan aktif (1 - 31 Mei 2026)...");

        // 1. Pastikan setiap karyawan aktif memiliki gaji dasar realistis jika masih 0
        // agar pengujian kalkulasi penggajian (Payroll), potongan izin, dan slip gaji valid.
        foreach ($employees as $index => $emp) {
            if ((float) $emp->salary <= 0) {
                $defaultSalary = 3200000 + (($index % 8) * 250000); // 3.200.000 s/d 4.950.000
                $emp->update(['salary' => $defaultSalary]);
            }
        }

        // 2. Bersihkan log dan permit seeder lama Mei 2026 jika ada (agar idempotent)
        AttendanceLog::where('user_agent', 'May2026AttendanceSeeder')
            ->whereBetween('scan_time', ["{$year}-05-01 00:00:00", "{$year}-05-31 23:59:59"])
            ->delete();

        Permit::whereBetween('permit_date', ["{$year}-05-01", "{$year}-05-31"])
            ->where('reason', 'like', '%SEED-MAY-2026%')
            ->delete();

        // 3. Set status kompilasi harian menjadi 'fix' untuk seluruh 31 hari di bulan Mei 2026
        // sehingga siap ditarik ke Penggajian tanpa ada blocker
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            AttendanceCompilation::updateOrCreate(
                ['date' => $date->toDateString()],
                [
                    'status' => 'fix',
                    'locked_at' => Carbon::now(),
                    'fixed_at' => Carbon::now(),
                    'notes' => 'Status FIX diset otomatis untuk testing Payroll Mei 2026',
                ]
            );
        }

        $dailyCount = 0;
        $logCount = 0;
        $permitCount = 0;

        DB::beginTransaction();
        try {
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $dateStr = $date->toDateString();
                $isSunday = $date->isSunday();

                foreach ($employees as $empIndex => $employee) {
                    $baseSalary = (float) $employee->salary;

                    if ($isSunday) {
                        // Hari Minggu: Libur rutin mingguan
                        DailyAttendance::updateOrCreate(
                            ['date' => $dateStr, 'employee_id' => $employee->id],
                            [
                                'check_in' => null,
                                'break_out' => null,
                                'break_in' => null,
                                'check_out' => null,
                                'late_check_in_minutes' => 0,
                                'late_break_in_minutes' => 0,
                                'overtime_minutes' => 0,
                                'is_anomaly' => false,
                                'anomaly_reason' => null,
                                'keterangan' => 'libur',
                                'nominal_izin' => 0,
                                'tipe_nominal_izin' => null,
                                'is_fixed' => true,
                                'edited_fields' => null,
                            ]
                        );
                        $dailyCount++;
                        continue;
                    }

                    // Hari Kerja (Senin s/d Sabtu)
                    $schedule = $attendanceService->resolveSchedule($employee, $date);
                    $stdCheckIn = $schedule['check_in_time'] ?: '07:00:00';
                    $stdBreakOut = $schedule['break_out_time'] ?: '12:00:00';
                    $stdBreakIn = $schedule['break_in_time'] ?: '13:00:00';
                    $stdCheckOut = $schedule['check_out_time'] ?: '16:00:00';

                    // Deterministic variation pattern based on employee ID and date
                    $pattern = abs(crc32("EMP-{$employee->id}-{$dateStr}")) % 100;

                    if ($pattern < 74) {
                        // 1. HADIR TEPAT WAKTU (74% kasuistis normal)
                        $earlyInMinutes = 3 + ($pattern % 8); // Masuk 3-10 menit sebelum jam kerja
                        $checkInDt = Carbon::parse("{$dateStr} {$stdCheckIn}")->subMinutes($earlyInMinutes);
                        $breakOutDt = Carbon::parse("{$dateStr} {$stdBreakOut}")->addMinutes($pattern % 2);
                        $breakInDt = Carbon::parse("{$dateStr} {$stdBreakIn}")->subMinutes($pattern % 3);
                        $checkOutDt = Carbon::parse("{$dateStr} {$stdCheckOut}")->addMinutes(2 + ($pattern % 5));

                        $checkInStr = $checkInDt->format('H:i:s');
                        $breakOutStr = $breakOutDt->format('H:i:s');
                        $breakInStr = $breakInDt->format('H:i:s');
                        $checkOutStr = $checkOutDt->format('H:i:s');

                        DailyAttendance::updateOrCreate(
                            ['date' => $dateStr, 'employee_id' => $employee->id],
                            [
                                'check_in' => $checkInStr,
                                'break_out' => $breakOutStr,
                                'break_in' => $breakInStr,
                                'check_out' => $checkOutStr,
                                'late_check_in_minutes' => 0,
                                'late_break_in_minutes' => 0,
                                'overtime_minutes' => 0,
                                'is_anomaly' => false,
                                'anomaly_reason' => null,
                                'keterangan' => 'hadir',
                                'nominal_izin' => 0,
                                'tipe_nominal_izin' => null,
                                'is_fixed' => true,
                                'edited_fields' => null,
                            ]
                        );
                        $dailyCount++;

                        $this->createLogs($employee, $dateStr, $machineSn, [$checkInStr, $breakOutStr, $breakInStr, $checkOutStr]);
                        $logCount += 4;

                    } elseif ($pattern < 84) {
                        // 2. TERLAMBAT MASUK / ISTIRAHAT (10% kasuistis)
                        $lateInMin = 15 + (($pattern % 5) * 5); // 15, 20, 25, 30, 35 menit
                        $checkInDt = Carbon::parse("{$dateStr} {$stdCheckIn}")->addMinutes($lateInMin);
                        $breakOutDt = Carbon::parse("{$dateStr} {$stdBreakOut}");

                        $lateBreakMin = ($pattern % 2 === 0) ? 15 : 0;
                        $breakInDt = Carbon::parse("{$dateStr} {$stdBreakIn}")->addMinutes($lateBreakMin);
                        $checkOutDt = Carbon::parse("{$dateStr} {$stdCheckOut}")->addMinutes(3);

                        $checkInStr = $checkInDt->format('H:i:s');
                        $breakOutStr = $breakOutDt->format('H:i:s');
                        $breakInStr = $breakInDt->format('H:i:s');
                        $checkOutStr = $checkOutDt->format('H:i:s');

                        DailyAttendance::updateOrCreate(
                            ['date' => $dateStr, 'employee_id' => $employee->id],
                            [
                                'check_in' => $checkInStr,
                                'break_out' => $breakOutStr,
                                'break_in' => $breakInStr,
                                'check_out' => $checkOutStr,
                                'late_check_in_minutes' => $lateInMin,
                                'late_break_in_minutes' => $lateBreakMin,
                                'overtime_minutes' => 0,
                                'is_anomaly' => false,
                                'anomaly_reason' => null,
                                'keterangan' => 'hadir',
                                'nominal_izin' => 0,
                                'tipe_nominal_izin' => null,
                                'is_fixed' => true,
                                'edited_fields' => null,
                            ]
                        );
                        $dailyCount++;

                        $this->createLogs($employee, $dateStr, $machineSn, [$checkInStr, $breakOutStr, $breakInStr, $checkOutStr]);
                        $logCount += 4;

                    } elseif ($pattern < 92) {
                        // 3. LEMBUR (8% kasuistis)
                        $overtimeMin = 60 + (($pattern % 4) * 30); // 60, 90, 120, 150 menit
                        $checkInDt = Carbon::parse("{$dateStr} {$stdCheckIn}")->subMinutes(5);
                        $breakOutDt = Carbon::parse("{$dateStr} {$stdBreakOut}");
                        $breakInDt = Carbon::parse("{$dateStr} {$stdBreakIn}");
                        $checkOutDt = Carbon::parse("{$dateStr} {$stdCheckOut}")->addMinutes($overtimeMin);

                        $checkInStr = $checkInDt->format('H:i:s');
                        $breakOutStr = $breakOutDt->format('H:i:s');
                        $breakInStr = $breakInDt->format('H:i:s');
                        $checkOutStr = $checkOutDt->format('H:i:s');

                        DailyAttendance::updateOrCreate(
                            ['date' => $dateStr, 'employee_id' => $employee->id],
                            [
                                'check_in' => $checkInStr,
                                'break_out' => $breakOutStr,
                                'break_in' => $breakInStr,
                                'check_out' => $checkOutStr,
                                'late_check_in_minutes' => 0,
                                'late_break_in_minutes' => 0,
                                'overtime_minutes' => $overtimeMin,
                                'is_anomaly' => false,
                                'anomaly_reason' => null,
                                'keterangan' => 'hadir',
                                'nominal_izin' => 0,
                                'tipe_nominal_izin' => null,
                                'is_fixed' => true,
                                'edited_fields' => null,
                            ]
                        );
                        $dailyCount++;

                        $this->createLogs($employee, $dateStr, $machineSn, [$checkInStr, $breakOutStr, $breakInStr, $checkOutStr]);
                        $logCount += 4;

                    } elseif ($pattern < 96) {
                        // 4. IZIN POTONG GAJI PER MENIT (4% kasuistis)
                        $izinMinutes = 60 * (($pattern % 4) + 1); // 60, 120, 180, 240 menit
                        $dailyRate = $baseSalary / 30;
                        $potonganNominal = round($dailyRate * ($izinMinutes / 480), 2);

                        Permit::create([
                            'employee_id' => $employee->id,
                            'permit_date' => $dateStr,
                            'type' => Permit::TYPE_SALARY_DEDUCTION,
                            'category' => 'pulang_awal',
                            'location' => $employee->location,
                            'position' => $employee->position,
                            'start_time' => '13:00:00',
                            'end_time' => Carbon::parse('13:00:00')->addMinutes($izinMinutes)->format('H:i:s'),
                            'duration_minutes' => $izinMinutes,
                            'deduction_minutes' => $izinMinutes,
                            'nominal' => $potonganNominal,
                            'reason' => "SEED-MAY-2026 Izin keperluan pribadi ({$izinMinutes} menit)",
                            'status' => 'approved',
                        ]);
                        $permitCount++;

                        $checkInStr = Carbon::parse("{$dateStr} {$stdCheckIn}")->format('H:i:s');
                        $checkOutDt = Carbon::parse("{$dateStr} {$stdCheckOut}")->subMinutes($izinMinutes);
                        $checkOutStr = $checkOutDt->format('H:i:s');

                        DailyAttendance::updateOrCreate(
                            ['date' => $dateStr, 'employee_id' => $employee->id],
                            [
                                'check_in' => $checkInStr,
                                'break_out' => '12:00:00',
                                'break_in' => '13:00:00',
                                'check_out' => $checkOutStr,
                                'late_check_in_minutes' => 0,
                                'late_break_in_minutes' => 0,
                                'overtime_minutes' => 0,
                                'is_anomaly' => false,
                                'anomaly_reason' => null,
                                'keterangan' => 'izin',
                                'nominal_izin' => $potonganNominal,
                                'tipe_nominal_izin' => 'potong_gaji',
                                'is_fixed' => true,
                                'edited_fields' => null,
                            ]
                        );
                        $dailyCount++;

                        $this->createLogs($employee, $dateStr, $machineSn, [$checkInStr, '12:00:00', '13:00:00', $checkOutStr]);
                        $logCount += 4;

                    } elseif ($pattern < 98) {
                        // 5. IZIN TANPA POTONG GAJI (2% kasuistis)
                        Permit::create([
                            'employee_id' => $employee->id,
                            'permit_date' => $dateStr,
                            'type' => Permit::TYPE_NO_DEDUCTION,
                            'category' => 'tidak_masuk',
                            'location' => $employee->location,
                            'position' => $employee->position,
                            'start_time' => $stdCheckIn,
                            'end_time' => $stdCheckOut,
                            'duration_minutes' => 480,
                            'deduction_minutes' => 0,
                            'nominal' => 0,
                            'reason' => 'SEED-MAY-2026 Izin dinas tugas luar',
                            'status' => 'approved',
                        ]);
                        $permitCount++;

                        DailyAttendance::updateOrCreate(
                            ['date' => $dateStr, 'employee_id' => $employee->id],
                            [
                                'check_in' => null,
                                'break_out' => null,
                                'break_in' => null,
                                'check_out' => null,
                                'late_check_in_minutes' => 0,
                                'late_break_in_minutes' => 0,
                                'overtime_minutes' => 0,
                                'is_anomaly' => false,
                                'anomaly_reason' => null,
                                'keterangan' => 'izin',
                                'nominal_izin' => 0,
                                'tipe_nominal_izin' => null,
                                'is_fixed' => true,
                                'edited_fields' => null,
                            ]
                        );
                        $dailyCount++;

                    } else {
                        // 6. CUTI / LIBUR PRIBADI (2% kasuistis)
                        Permit::create([
                            'employee_id' => $employee->id,
                            'permit_date' => $dateStr,
                            'type' => Permit::TYPE_LIBUR,
                            'category' => 'libur',
                            'location' => $employee->location,
                            'position' => $employee->position,
                            'leave_duration' => 'satu_hari',
                            'start_time' => $stdCheckIn,
                            'end_time' => $stdCheckOut,
                            'duration_minutes' => 480,
                            'deduction_minutes' => 0,
                            'nominal' => 0,
                            'reason' => 'SEED-MAY-2026 Cuti tahunan karyawan',
                            'status' => 'approved',
                        ]);
                        $permitCount++;

                        DailyAttendance::updateOrCreate(
                            ['date' => $dateStr, 'employee_id' => $employee->id],
                            [
                                'check_in' => null,
                                'break_out' => null,
                                'break_in' => null,
                                'check_out' => null,
                                'late_check_in_minutes' => 0,
                                'late_break_in_minutes' => 0,
                                'overtime_minutes' => 0,
                                'is_anomaly' => false,
                                'anomaly_reason' => null,
                                'keterangan' => 'libur',
                                'nominal_izin' => 0,
                                'tipe_nominal_izin' => null,
                                'is_fixed' => true,
                                'edited_fields' => null,
                            ]
                        );
                        $dailyCount++;
                    }
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->command->error("Gagal melakukan seeding: {$e->getMessage()}");
            throw $e;
        }

        $this->command->info("May2026AttendanceSeeder sukses dieksekusi!");
        $this->command->info("- Karyawan aktif: {$employees->count()}");
        $this->command->info("- Total record DailyAttendance: {$dailyCount}");
        $this->command->info("- Total AttendanceLog mesin ('{$machineSn}'): {$logCount}");
        $this->command->info("- Total Permit dibuat: {$permitCount}");
        $this->command->info("- Seluruh 31 hari Mei 2026 berstatus FIX.");
    }

    private function createLogs(Employee $employee, string $dateStr, string $machineSn, array $scanTimes): void
    {
        $userId = (string) $employee->employee_id;
        $now = now();

        $rows = [];
        foreach ($scanTimes as $time) {
            if (! $time) {
                continue;
            }
            $rows[] = [
                'machine_sn' => $machineSn,
                'user_id' => $userId,
                'scan_time' => "{$dateStr} {$time}",
                'status' => 0,
                'user_agent' => 'May2026AttendanceSeeder',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($rows)) {
            AttendanceLog::insert($rows);
        }
    }
}
