<?php

namespace Tests\Feature;

use App\Models\AttendanceCompilation;
use App\Models\DailyAttendance;
use App\Models\Employee;
use App\Models\Golongan;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Services\AttendanceProcessingService;
use App\Services\PayrollService;
use Tests\TestCase;

class BriefingRequirementsTest extends TestCase
{
    /**
     * Module 2: Checklock 4 Ranges and Overtime >= 60 min threshold
     */
    public function test_checklock_classification_4_ranges_and_overtime(): void
    {
        $service = app(AttendanceProcessingService::class);

        $checkLocks = [
            '07:15:00', // Masuk (06:00 - 09:00)
            '11:45:00', // Istirahat (11:00 - 12:29)
            '12:45:00', // Masuk Istirahat (12:30 - 13:30)
            '17:30:00', // Pulang (15:30 - 20:00)
        ];

        $classified = $service->classifyTimeRanges($checkLocks);

        $this->assertSame('07:15:00', $classified['check_in']);
        $this->assertSame('11:45:00', $classified['break_out']);
        $this->assertSame('12:45:00', $classified['break_in']);
        $this->assertSame('17:30:00', $classified['check_out']);

        // Overtime >= 60 mins: schedule pulang 16:00, actual pulang 17:30 -> 90 mins overtime
        $overtimeMinutes = $service->calculateOvertimeMinutes('17:30:00', '16:00:00');
        $this->assertSame(90, $overtimeMinutes);

        // Overtime < 60 mins: schedule pulang 16:00, actual pulang 16:45 -> 0 mins overtime
        $noOvertimeMinutes = $service->calculateOvertimeMinutes('16:45:00', '16:00:00');
        $this->assertSame(0, $noOvertimeMinutes);
    }

    /**
     * Module 4: Absen Daily anomaly detection, save row, & status lock/fix flow
     */
    public function test_daily_attendance_anomaly_highlight_and_fix_flow(): void
    {
        $this->loginAsSuperAdmin();

        $employee = Employee::create([
            'employee_id' => 'EMP001',
            'name' => 'Budi Santoso',
            'position' => 'Kandang',
            'status' => 'active',
            'salary' => 2500000,
        ]);

        $compilation = AttendanceCompilation::create([
            'date' => '2026-08-10',
            'status' => 'draft',
        ]);

        $daily = DailyAttendance::create([
            'employee_id' => $employee->id,
            'date' => '2026-08-10',
            'check_in' => '08:30:00',
            'late_check_in_minutes' => 30,
            'is_anomaly' => true,
            'is_fixed' => false,
        ]);

        // Access daily page
        $response = $this->get(route('attendance.daily', ['date' => '2026-08-10']));
        $response->assertOk();
        $response->assertSee('Budi Santoso');

        // Cannot update compilation status to 'fix' while unresolved anomalies exist
        $statusResponse = $this->post(route('attendance.daily.status'), [
            'date' => '2026-08-10',
            'status' => 'fix',
        ]);
        $statusResponse->assertSessionHasErrors('status');

        // Save row with HRD Izin with potong_gaji (ignoring late minutes fine)
        $saveRowResponse = $this->post(route('attendance.daily.save', ['daily' => $daily->id]), [
            'keterangan' => 'izin',
            'tipe_nominal_izin' => 'potong_gaji',
            'nominal_izin' => 25000,
            'is_fixed' => 1,
        ]);
        $saveRowResponse->assertRedirect();

        $daily->refresh();
        $this->assertSame('izin', $daily->keterangan);
        $this->assertSame('potong_gaji', $daily->tipe_nominal_izin);
        $this->assertSame(25000.0, (float) $daily->nominal_izin);
        $this->assertFalse((bool) $daily->is_anomaly);
        $this->assertTrue((bool) $daily->is_fixed);
        $this->assertTrue($daily->isLateIgnored());

        // Now compilation can be marked as 'fix'
        $statusResponseOk = $this->post(route('attendance.daily.status'), [
            'date' => '2026-08-10',
            'status' => 'fix',
        ]);
        $statusResponseOk->assertRedirect();
        $compilation->refresh();
        $this->assertSame('fix', $compilation->status);
    }

    /**
     * Module 5: Calendar compilation workflow and recap lock
     */
    public function test_monthly_recap_requires_all_compilations_fix(): void
    {
        $this->loginAsSuperAdmin();

        $employee = Employee::create([
            'employee_id' => 'EMP002',
            'name' => 'Siti Nurhaliza',
            'position' => 'Admin Gudang',
            'status' => 'active',
            'salary' => 3000000,
        ]);

        // 1 compilation draft
        AttendanceCompilation::create([
            'date' => '2026-08-01',
            'status' => 'draft',
        ]);

        // Monthly recap should warn that not all compilations are FIX
        $response = $this->get(route('attendance.monthly-recap', ['year' => 2026, 'month' => 8]));
        $response->assertOk();
        $response->assertSee('Semua tanggal harus berstatus FIX');
    }

    /**
     * Module 7: Yearly report for Semester 1 & Semester 2
     */
    public function test_yearly_leave_report_semester_display(): void
    {
        $this->loginAsSuperAdmin();

        Employee::create([
            'employee_id' => 'EMP003',
            'name' => 'Joko Widodo',
            'position' => 'Mandor Lapangan',
            'status' => 'active',
            'salary' => 4000000,
            'leave_quota' => 2,
        ]);

        $response = $this->get(route('reports.yearly', ['year' => 2026]));
        $response->assertOk();
        $response->assertSee('Semester 1');
        $response->assertSee('Semester 2');
        $response->assertSee('Joko Widodo');
    }

    /**
     * Module 8: Confidential salary masked for non-super_admin
     */
    public function test_confidential_salary_masked_for_non_super_admin(): void
    {
        $confidentialGolongan = Golongan::create([
            'name' => 'Golongan Direksi',
            'type' => 'mandor_admin',
            'is_confidential' => true,
        ]);

        $emp = Employee::create([
            'employee_id' => 'DIR001',
            'name' => 'Pak Direktur',
            'position' => 'Mandor',
            'golongan_id' => $confidentialGolongan->id,
            'status' => 'active',
            'salary' => 15000000,
        ]);

        // Admin operasional cannot view or edit confidential employee salary
        $this->loginAsAdminOperasional();
        $response = $this->get(route('gajis.edit', $emp));
        $response->assertForbidden();

        $indexResponse = $this->get(route('gajis.index'));
        $indexResponse->assertOk();
        $indexResponse->assertDontSee('Pak Direktur');

        // Super admin sees confidential employee and can view/edit salary
        $this->loginAsSuperAdmin();
        $responseAdmin = $this->get(route('gajis.index'));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee('Pak Direktur');
        $responseAdmin->assertSee('15.000.000');

        $editAdmin = $this->get(route('gajis.edit', $emp));
        $editAdmin->assertOk();
    }

    /**
     * Module 9: Employee bank account and payment method
     */
    public function test_employee_banking_and_payment_method(): void
    {
        $this->loginAsSuperAdmin();

        $employee = Employee::create([
            'employee_id' => 'EMP004',
            'name' => 'Rahmat Hidayat',
            'position' => 'Kandang',
            'status' => 'active',
            'salary' => 3500000,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'Rahmat Hidayat',
            'payment_method' => 'transfer',
        ]);

        $this->assertSame('transfer', $employee->payment_method);
        $this->assertSame('BCA', $employee->bank_name);
        $this->assertSame('1234567890', $employee->account_number);

        $payroll = app(PayrollService::class)->generate($employee, 2026, 8);
        $this->assertSame('transfer', $payroll->payment_method);
    }

    /**
     * Module 10 & 11: Grand Total Formula & Manual Loan Deduction
     * GRAND TOTAL = GAJI POKOK - POTONGAN TERLAMBAT - IZIN POTONG GAJI + IZIN TAMBAH GAJI
     *               - PINJAMAN - POTONGAN MASUK + BONUSAN + UANG JAGA MALAM + NOMINAL LEMBUR
     */
    public function test_payroll_grand_total_briefing_formula_with_daily_compilation(): void
    {
        $this->loginAsSuperAdmin();

        $employee = Employee::create([
            'employee_id' => 'EMP005',
            'name' => 'Mandor Agus',
            'position' => 'Mandor',
            'status' => 'active',
            'salary' => 3000000, // Daily rate = 3,000,000 / 30 = 100,000. Multiplier 1.5 -> 150,000 per sisa libur.
            'leave_quota' => 2,
            'is_night_guard' => true, // Mandor gets 600,000 jaga malam
        ]);

        // Active loan with monthly payment
        $loan = Loan::create([
            'employee_id' => $employee->id,
            'loan_date' => '2026-08-01',
            'principal' => 1000000,
            'status' => 'active',
        ]);
        LoanPayment::create([
            'loan_id' => $loan->id,
            'employee_id' => $employee->id,
            'payment_date' => '2026-08-15',
            'amount' => 200000,
        ]);

        // Setup daily compilation & attendance
        $compilation = AttendanceCompilation::create([
            'date' => '2026-08-10',
            'status' => 'fix',
        ]);

        // Day 1: Izin with potong gaji 50,000
        DailyAttendance::create([
            'employee_id' => $employee->id,
            'date' => '2026-08-10',
            'keterangan' => 'izin',
            'tipe_nominal_izin' => 'potong_gaji',
            'nominal_izin' => 50000,
            'overtime_minutes' => 60, // 1 hour overtime
            'is_fixed' => true,
        ]);

        // Day 2: Libur taken (1 day libur taken, quota is 2, so sisa libur = 1)
        DailyAttendance::create([
            'employee_id' => $employee->id,
            'date' => '2026-08-11',
            'keterangan' => 'libur',
            'is_fixed' => true,
        ]);

        $service = app(PayrollService::class);
        $payroll = $service->generate($employee, 2026, 8);

        // Verification of components:
        // Gaji Pokok: 3,000,000
        // Potongan Terlambat: 0 (since Day 1 is ignored via izin potong gaji)
        // Izin Potong Gaji: 50,000
        // Izin Tambah Gaji: 0
        // Pinjaman: 200,000
        // Potongan Masuk: 0 (totalHariLibur = 1, quota = 2)
        // Bonus Libur: sisaLibur (1) * (3,000,000 / 30) * 1.5 = 150,000
        // Uang Jaga Malam: 600,000 (Mandor)
        // Lembur: 60 mins -> 1 hour * (3,000,000 / 173) = 17,341.04
        // Expected Grand Total = 3,000,000 - 0 - 50,000 + 0 - 200,000 - 0 + 150,000 + 600,000 + 17341.04 = 3,517,341.04

        $this->assertSame(3000000.0, (float) $payroll->base_salary);
        $this->assertSame(50000.0, (float) $payroll->izin_potong_gaji);
        $this->assertSame(200000.0, (float) $payroll->loan_deduction);
        $this->assertSame(150000.0, (float) $payroll->bonus_libur);
        $this->assertSame(600000.0, (float) $payroll->uang_jaga_malam);
        $this->assertSame(17341.04, (float) $payroll->nominal_lembur);
        $this->assertSame(3517341.04, (float) $payroll->grand_total);

        // Test manual loan deduction override
        $payrollManualLoan = $service->generate($employee, 2026, 8, 100000);
        $this->assertSame(100000.0, (float) $payrollManualLoan->loan_deduction);
        // Recalculated Grand Total with 100,000 loan deduction instead of 200,000 (+100,000) = 3,617,341.04
        $this->assertSame(3617341.04, (float) $payrollManualLoan->grand_total);
    }
}
