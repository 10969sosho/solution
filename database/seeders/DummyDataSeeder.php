<?php

namespace Database\Seeders;

use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Golongan;
use App\Models\Jabatan;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Lokasi;
use App\Models\Payroll;
use App\Models\Permit;
use App\Models\SeasonalSchedule;
use App\Models\User;
use App\Models\WorkSetting;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('DummyDataSeeder: Menjalankan seeder data dummy...');

        // Disable FK checks for SQLite
        DB::statement('PRAGMA foreign_keys = OFF');

        $tables = [
            'payrolls', 'loan_payments', 'loans', 'permits',
            'attendance_logs', 'employee_schedules', 'seasonal_schedules',
            'work_settings', 'employees', 'golongans', 'jabatans', 'lokasis',
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
                $this->command->info("  Truncated: {$table}");
            }
        }

        DB::statement('PRAGMA foreign_keys = ON');

        // 1. Super admin user
        User::firstOrCreate(
            ['email' => 'admin@adms.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
            ]
        );
        $this->command->info('  Created: User super admin');

        // 2. Golongan (use DB::table to handle legacy 'code' column on SQLite)
        $golonganNames = [
            'Golongan I', 'Golongan II', 'Golongan III',
            'Golongan IV', 'Golongan V', 'Golongan VI',
        ];
        $golongans = [];
        foreach ($golonganNames as $i => $name) {
            $id = DB::table('golongans')->insertGetId([
                'name' => $name,
                'code' => 'G' . ($i + 1), // legacy column, may be ignored on MySQL
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $golongans[] = (object) ['id' => $id, 'name' => $name];
        }
        $this->command->info('  Created: ' . count($golongans) . ' golongan');

        // 3. Jabatan
        $jabatanNames = [
            'Staff', 'Senior Staff', 'Supervisor',
            'Manager', 'Senior Manager', 'Admin',
            'Operator', 'Technician',
        ];
        $jabatans = [];
        foreach ($jabatanNames as $i => $name) {
            $id = DB::table('jabatans')->insertGetId([
                'name' => $name,
                'code' => 'J' . ($i + 1),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $jabatans[] = (object) ['id' => $id, 'name' => $name];
        }
        $this->command->info('  Created: ' . count($jabatans) . ' jabatan');

        // 4. Lokasi
        $lokasiNames = [
            'Kantor Pusat Jakarta', 'Cabang Bandung', 'Cabang Surabaya',
            'Cabang Semarang', 'Cabang Medan', 'Cabang Makassar',
        ];
        $lokasis = [];
        foreach ($lokasiNames as $i => $name) {
            $id = DB::table('lokasis')->insertGetId([
                'name' => $name,
                'code' => 'L' . ($i + 1),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $lokasis[] = (object) ['id' => $id, 'name' => $name];
        }
        $this->command->info('  Created: ' . count($lokasis) . ' lokasi');

        // 5. Work Settings (per hari + per golongan)
        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        // Global default (semua hari kerja)
        WorkSetting::create([
            'name' => 'Standard Kerja',
            'day' => 'Senin,Selasa,Rabu,Kamis,Jumat',
            'golongan_id' => null,
            'check_in_time' => '08:00:00',
            'check_out_time' => '17:00:00',
            'break_out_time' => '12:00:00',
            'break_in_time' => '13:00:00',
            'late_tolerance_minutes' => 15,
            'overtime_threshold_minutes' => 30,
            'is_active' => true,
            'description' => 'Jam kerja standar Senin-Jumat',
        ]);

        // Per golongan override (contoh: Golongan IV-VI punya toleransi lebih)
        WorkSetting::create([
            'name' => 'Golongan Senior',
            'day' => 'Senin,Selasa,Rabu,Kamis,Jumat',
            'golongan_id' => $golongans[3]->id, // Golongan IV
            'check_in_time' => '07:30:00',
            'check_out_time' => '16:30:00',
            'break_out_time' => '12:00:00',
            'break_in_time' => '13:00:00',
            'late_tolerance_minutes' => 10,
            'overtime_threshold_minutes' => 60,
            'is_active' => true,
            'description' => 'Jam kerja untuk Golongan IV ke atas',
        ]);

        $this->command->info('  Created: 2 work settings');

        // 6. Employees (12 karyawan)
        $employeeData = [
            ['employee_id' => 'EMP00001', 'name' => 'Budi Santoso',     'position' => 'Manager',       'department' => 'IT',        'location' => 'Jakarta',  'salary' => 7000000, 'status' => 'active'],
            ['employee_id' => 'EMP00002', 'name' => 'Siti Rahayu',       'position' => 'Staff',        'department' => 'HR',        'location' => 'Jakarta',  'salary' => 4500000, 'status' => 'active'],
            ['employee_id' => 'EMP00003', 'name' => 'Ahmad Fauzi',       'position' => 'Supervisor',   'department' => 'Logistik',  'location' => 'Bandung',  'salary' => 5500000, 'status' => 'active'],
            ['employee_id' => 'EMP00004', 'name' => 'Dewi Lestari',      'position' => 'Staff',        'department' => 'Finance',   'location' => 'Jakarta',  'salary' => 4000000, 'status' => 'active'],
            ['employee_id' => 'EMP00005', 'name' => 'Rizky Pratama',     'position' => 'Technician',   'department' => 'IT',        'location' => 'Surabaya', 'salary' => 3500000, 'status' => 'active'],
            ['employee_id' => 'EMP00006', 'name' => 'Anisa Putri',       'position' => 'Admin',        'department' => 'HR',        'location' => 'Jakarta',  'salary' => 3800000, 'status' => 'active'],
            ['employee_id' => 'EMP00007', 'name' => 'Hendra Wijaya',     'position' => 'Operator',     'department' => 'Produksi',  'location' => 'Semarang', 'salary' => 3200000, 'status' => 'active'],
            ['employee_id' => 'EMP00008', 'name' => 'Maya Anggraeni',    'position' => 'Staff',        'department' => 'Marketing', 'location' => 'Jakarta',  'salary' => 4200000, 'status' => 'active'],
            ['employee_id' => 'EMP00009', 'name' => 'Dimas Kurniawan',   'position' => 'Senior Staff', 'department' => 'IT',        'location' => 'Bandung',  'salary' => 5800000, 'status' => 'active'],
            ['employee_id' => 'EMP00010', 'name' => 'Rina Sari',         'position' => 'Staff',        'department' => 'Finance',   'location' => 'Surabaya', 'salary' => 4100000, 'status' => 'inactive'],
            ['employee_id' => 'EMP00011', 'name' => 'Fajar Nugroho',     'position' => 'Operator',     'department' => 'Produksi',  'location' => 'Semarang', 'salary' => 3100000, 'status' => 'resigned'],
            ['employee_id' => 'EMP00012', 'name' => 'Lia Marlina',       'position' => 'Staff',        'department' => 'Marketing', 'location' => 'Jakarta',  'salary' => 4300000, 'status' => 'active'],
        ];

        $employees = [];
        foreach ($employeeData as $data) {
            $employees[] = Employee::create(array_merge($data, [
                'golongan_id' => $golongans[array_rand($golongans)]->id,
                'jabatan_id' => $jabatans[array_rand($jabatans)]->id,
                'lokasi_id' => $lokasis[array_rand($lokasis)]->id,
                'tanggal_keluar' => $data['status'] === 'resigned' ? Carbon::now()->subMonths(1)->toDateString() : null,
                'jam_masuk_normal' => '08:00:00',
                'phone' => '08' . mt_rand(1000000000, 9999999999),
                'email' => strtolower(str_replace(' ', '.', $data['name'])) . '@3putraperkasa.com',
                'join_date' => Carbon::now()->subMonths(mt_rand(6, 24))->toDateString(),
                'salary_tier' => ['A', 'B', 'C', 'D'][array_rand(['A', 'B', 'C', 'D'])],
                'address' => fake()->address(),
            ]));
        }
        $this->command->info('  Created: ' . count($employees) . ' employees');

        // 7. Employee Schedules (1 per employee, active)
        foreach ($employees as $emp) {
            EmployeeSchedule::create([
                'employee_id' => $emp->id,
                'name' => 'Jam Kerja Standard',
                'check_in_time' => '08:00:00',
                'break_out_time' => '12:00:00',
                'break_in_time' => '13:00:00',
                'check_out_time' => '17:00:00',
                'late_tolerance_minutes' => 15,
                'effective_from' => $emp->join_date,
                'is_active' => true,
                'description' => 'Jam kerja standar untuk ' . $emp->name,
            ]);
        }
        $this->command->info('  Created: ' . count($employees) . ' employee schedules');

        // 8. Seasonal Schedules
        $seasonalData = [
            ['name' => 'Puasa Ramadan 2026',  'start_date' => '2026-03-01', 'end_date' => '2026-03-30', 'check_in_delta_minutes' => -30, 'check_out_delta_minutes' => -30, 'force_check_in_time' => '05:30:00', 'is_active' => false],
            ['name' => 'Lebaran 2026',         'start_date' => '2026-03-31', 'end_date' => '2026-04-07', 'check_in_delta_minutes' => 0,    'check_out_delta_minutes' => 0,    'force_check_in_time' => null,       'is_active' => false],
            ['name' => 'Natal 2025',           'start_date' => '2025-12-20', 'end_date' => '2026-01-05', 'check_in_delta_minutes' => -60,  'check_out_delta_minutes' => -60,  'force_check_in_time' => '06:00:00', 'is_active' => false],
            ['name' => 'Tahun Baru 2026',      'start_date' => '2026-01-01', 'end_date' => '2026-01-03', 'check_in_delta_minutes' => 0,    'check_out_delta_minutes' => 0,    'force_check_in_time' => null,       'is_active' => false],
        ];

        foreach ($seasonalData as $data) {
            SeasonalSchedule::create($data);
        }
        $this->command->info('  Created: ' . count($seasonalData) . ' seasonal schedules');

        // 9. Attendance Logs (2 bulan terakhir: Agustus & September 2026)
        $this->command->info('  Generating attendance logs for 2 months...');
        $months = [
            ['year' => 2026, 'month' => 8],  // Agustus
            ['year' => 2026, 'month' => 9],  // September
        ];

        $totalLogs = 0;
        $activeEmployees = array_filter($employees, fn($e) => $e->status === 'active');

        foreach ($months as $m) {
            $startOfMonth = Carbon::create($m['year'], $m['month'], 1);
            $endOfMonth = (clone $startOfMonth)->endOfMonth();

            for ($date = (clone $startOfMonth); $date->lte($endOfMonth); $date->addDay()) {
                // Skip weekends
                if ($date->isSaturday() || $date->isSunday()) {
                    continue;
                }

                foreach ($activeEmployees as $emp) {
                    $rand = mt_rand(1, 100);

                    if ($rand <= 5) {
                        // 5% alpha (no log)
                        continue;
                    }

                    // Check-in time (default 08:00, some late 5-30 min)
                    $checkIn = $date->copy()->setTime(8, 0, 0);
                    if ($rand <= 20) {
                        // 15% telat (chance 6-20)
                        $lateMinutes = mt_rand(5, 30);
                        $checkIn->addMinutes($lateMinutes);
                    }

                    // Check-out time (17:00, some earlier)
                    $checkOut = $date->copy()->setTime(17, 0, 0);
                    if (mt_rand(1, 100) <= 10) {
                        // 10% pulang awal
                        $checkOut->subMinutes(mt_rand(10, 60));
                    }

                    // Scan time BETWEEN 08:00 and 17:30
                    $scanTimeCheckIn = $checkIn->format('Y-m-d H:i:s');
                    $scanTimeCheckOut = $checkOut->format('Y-m-d H:i:s');

                    // Status: 0 = normal, 1 = late
                    $isLate = $checkIn->hour === 8 && $checkIn->minute <= 0 ? 0 : 1;

                    // Check-in log
                    AttendanceLog::create([
                        'machine_sn' => (string) mt_rand(1, 3),
                        'user_id' => $emp->employee_id,
                        'scan_time' => $scanTimeCheckIn,
                        'status' => $isLate,
                        'raw_data' => null,
                        'ip_address' => fake()->ipv4(),
                        'user_agent' => 'iClock ZKTeco',
                    ]);
                    $totalLogs++;

                    // Check-out log
                    AttendanceLog::create([
                        'machine_sn' => (string) mt_rand(1, 3),
                        'user_id' => $emp->employee_id,
                        'scan_time' => $scanTimeCheckOut,
                        'status' => 0,
                        'raw_data' => null,
                        'ip_address' => fake()->ipv4(),
                        'user_agent' => 'iClock ZKTeco',
                    ]);
                    $totalLogs++;
                }
            }
        }
        $this->command->info("  Created: {$totalLogs} attendance logs");

        // 10. Permits (mix izin/cuti/sakit/dinas)
        $permitReasons = [
            'Izin Sakit', 'Cuti Tahunan', 'Dinas Luar Kota',
            'Izin Pribadi', 'Sakit Flu', 'Cuti Menikah',
            'Dinas Meeting', 'Izin Keluarga Sakit',
        ];
        $permitCount = 0;
        foreach ($employees as $i => $emp) {
            // 2-4 permits per employee
            $count = mt_rand(2, 4);
            for ($p = 0; $p < $count; $p++) {
                $type = fake()->randomElement([
                    Permit::TYPE_NO_DEDUCTION,
                    Permit::TYPE_SALARY_DEDUCTION,
                ]);
                $permitCount++;
                Permit::create([
                    'employee_id' => $emp->id,
                    'location' => $emp->location,
                    'position' => $emp->position,
                    'permit_date' => Carbon::now()->subDays(mt_rand(1, 60))->toDateString(),
                    'type' => $type,
                    'start_time' => '08:00:00',
                    'end_time' => '17:00:00',
                    'duration_minutes' => mt_rand(60, 480),
                    'reason' => $permitReasons[array_rand($permitReasons)],
                    'status' => fake()->randomElement(['pending', 'approved', 'approved', 'rejected']),
                    'deduction_type' => $type === Permit::TYPE_SALARY_DEDUCTION ? 'daily' : null,
                    'deduction_hours' => $type === Permit::TYPE_SALARY_DEDUCTION ? 8 : 0,
                    'deduction_minutes' => 0,
                ]);
            }
        }
        $this->command->info("  Created: {$permitCount} permits");

        // 11. Loans + Loan Payments
        $loanCount = 0;
        $paymentCount = 0;

        foreach ($employees as $i => $emp) {
            // 3-5 karyawan punya pinjaman
            if ($i >= 7) {
                continue;
            }

            $principal = mt_rand(500000, 5000000);
            $status = $i < 3 ? 'paid' : 'active';

            $loan = Loan::create([
                'employee_id' => $emp->id,
                'loan_date' => Carbon::now()->subMonths(mt_rand(1, 6))->toDateString(),
                'principal' => $principal,
                'description' => fake()->randomElement([
                    'Pinjaman Karyawan', 'Emergency Loan', 'Medical Loan',
                ]),
                'status' => $status,
                'previous_loans_total' => mt_rand(0, 2000000),
                'all_loans_total' => $principal + mt_rand(0, 2000000),
            ]);
            $loanCount++;

            // Create 2-4 payments for this loan
            $paymentCountLoan = mt_rand(2, 4);
            $totalPaid = 0;
            $perPayment = (int) ceil($principal / $paymentCountLoan);

            for ($pp = 0; $pp < $paymentCountLoan; $pp++) {
                $amount = ($pp === $paymentCountLoan - 1)
                    ? max(100000, $principal - $totalPaid)
                    : $perPayment;

                $amount = min($amount, 1000000);

                LoanPayment::create([
                    'loan_id' => $loan->id,
                    'employee_id' => $emp->id,
                    'payment_date' => Carbon::now()->subMonths($paymentCountLoan - $pp - 1)->toDateString(),
                    'amount' => $amount,
                    'notes' => 'Cicilan ke-' . ($pp + 1),
                ]);
                $totalPaid += $amount;
                $paymentCount++;
            }
        }
        $this->command->info("  Created: {$loanCount} loans, {$paymentCount} loan payments");

        // 12. Payrolls (2 bulan: Agustus & September 2026)
        $payrollCount = 0;

        foreach ($months as $m) {
            foreach ($employees as $emp) {
                $baseSalary = $emp->salary;

                // Random deductions
                $lateDeduction = mt_rand(0, 200000);
                $loanDeduction = 0;
                $absenceDeduction = mt_rand(0, 300000);

                // Check if this employee has an active loan
                $activeLoan = Loan::where('employee_id', $emp->id)
                    ->where('status', 'active')
                    ->first();
                if ($activeLoan) {
                    $loanDeduction = (int) ceil($activeLoan->principal / 6);
                }

                $totalDeduction = $lateDeduction + $loanDeduction + $absenceDeduction;
                $attendanceBonus = (mt_rand(0, 100) > 20) ? mt_rand(50000, 200000) : 0;
                $totalIncentive = $attendanceBonus;
                $netSalary = $baseSalary - $totalDeduction + $totalIncentive;

                Payroll::create([
                    'employee_id' => $emp->id,
                    'period_year' => $m['year'],
                    'period_month' => $m['month'],
                    'base_salary' => $baseSalary,
                    'late_deduction' => $lateDeduction,
                    'loan_deduction' => $loanDeduction,
                    'absence_deduction' => $absenceDeduction,
                    'total_deduction' => $totalDeduction,
                    'attendance_bonus' => $attendanceBonus,
                    'total_incentive' => $totalIncentive,
                    'net_salary' => $netSalary,
                    'breakdown' => [
                        'base_salary' => $baseSalary,
                        'late_deduction' => $lateDeduction,
                        'loan_deduction' => $loanDeduction,
                        'absence_deduction' => $absenceDeduction,
                        'attendance_bonus' => $attendanceBonus,
                        'total_incentive' => $totalIncentive,
                        'net_salary' => $netSalary,
                    ],
                    'status' => $m['month'] === 8 ? 'paid' : 'draft',
                ]);
                $payrollCount++;
            }
        }
        $this->command->info("  Created: {$payrollCount} payrolls (2 bulan)");

        // Summary
        $this->command->info('');
        $this->command->info('DummyDataSeeder: Selesai! Summary:');
        $this->command->info('  Golongan:         ' . Golongan::count());
        $this->command->info('  Jabatan:          ' . Jabatan::count());
        $this->command->info('  Lokasi:           ' . Lokasi::count());
        $this->command->info('  Work Settings:    ' . WorkSetting::count());
        $this->command->info('  Employees:        ' . Employee::count());
        $this->command->info('  Schedules:        ' . EmployeeSchedule::count());
        $this->command->info('  Seasonal:         ' . SeasonalSchedule::count());
        $this->command->info('  Attendance Logs:  ' . AttendanceLog::count());
        $this->command->info('  Permits:          ' . Permit::count());
        $this->command->info('  Loans:            ' . Loan::count());
        $this->command->info('  Loan Payments:    ' . LoanPayment::count());
        $this->command->info('  Payrolls:         ' . Payroll::count());
        $this->command->info('  Users:            ' . User::count());
    }
}
