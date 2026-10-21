<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Golongan;
use App\Models\Loan;
use App\Models\NightGuard;
use App\Models\Payroll;
use Tests\TestCase;

class NewRequirementsTest extends TestCase
{
    public function test_permit_libur_options_and_leave_duration(): void
    {
        $this->loginAsSuperAdmin();

        $employee = Employee::create([
            'employee_id' => 'EMP-LIBUR-01',
            'name' => 'Budi Santoso',
            'position' => 'Staff',
            'status' => 'active',
            'salary' => 3000000,
        ]);

        $response = $this->post(route('permits.store'), [
            'employee_id' => $employee->id,
            'permit_date' => '2026-10-05',
            'permits' => [
                [
                    'category' => 'libur',
                    'type' => 'libur',
                    'leave_duration' => 'setengah_hari',
                    'reason' => 'Izin setengah hari',
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('permits', [
            'employee_id' => $employee->id,
            'type' => 'libur',
            'leave_duration' => 'setengah_hari',
        ]);
    }

    public function test_leave_quota_resets_on_1st_without_losing_previous_month_balance(): void
    {
        $employee = Employee::create([
            'employee_id' => 'EMP-LIBUR-02',
            'name' => 'Siti Rahma',
            'position' => 'Mandor',
            'status' => 'active',
            'salary' => 4000000,
            'leave_quota' => 2, // default quota 2 per bulan
        ]);

        // Bulan lalu (September): kuota 2, dipakai 1 hari libur
        \App\Models\DailyAttendance::create([
            'employee_id' => $employee->id,
            'date' => '2026-09-10',
            'keterangan' => 'libur',
        ]);

        // Sisa September = 2 - 1 = 1 hari
        $sisaSept = $employee->calculateRemainingLeaveForMonth(2026, 9);
        $this->assertEquals(1.0, $sisaSept);

        // Kuota Oktober (reset tgl 1 dapat default 2 + sisa Sept 1 = 3 hari)
        $quotaOkt = $employee->getEffectiveLeaveQuotaForMonth(2026, 10);
        $this->assertEquals(3.0, $quotaOkt);
    }

    public function test_night_guard_page_renders_and_confirms(): void
    {
        $this->loginAsSuperAdmin();

        $employee = Employee::create([
            'employee_id' => 'EMP-GUARD-01',
            'name' => 'Agus Jaga',
            'position' => 'Security',
            'status' => 'active',
            'is_night_guard' => true,
            'uang_jaga_malam' => 350000,
        ]);

        $response = $this->get(route('attendance.night-guard', ['year' => 2026, 'month' => 10]));
        $response->assertOk();
        $response->assertSee('Jaga Malam');
        $response->assertSee('Agus Jaga');

        // Simpan konfirmasi jaga malam
        $postResponse = $this->post(route('attendance.night-guard.update'), [
            'date' => '2026-10-08',
            'employee_id' => $employee->id,
            'action' => 'confirm',
        ]);

        $postResponse->assertRedirect();
        $this->assertNotNull(NightGuard::whereDate('date', '2026-10-08')->where('employee_id', $employee->id)->where('status', 'confirmed')->first());
    }

    public function test_loan_report_header_shows_potong_gaji_next_month(): void
    {
        $this->loginAsSuperAdmin();

        $employee = Employee::create([
            'employee_id' => 'EMP-LOAN-01',
            'name' => 'Doni Pinjam',
            'position' => 'Staff',
            'status' => 'active',
        ]);

        Loan::create([
            'employee_id' => $employee->id,
            'loan_date' => '2026-10-01',
            'principal' => 500000,
            'status' => 'active',
        ]);

        $response = $this->get(route('loans.laporan', ['year' => 2026, 'month' => 10]));
        $response->assertOk();
        $response->assertSee('Potong Gaji 1 November');
    }

    public function test_payroll_index_and_slip_shows_sisa_pinjaman(): void
    {
        $this->loginAsSuperAdmin();

        $employee = Employee::create([
            'employee_id' => 'EMP-LOAN-02',
            'name' => 'Rina Staff',
            'position' => 'Staff',
            'status' => 'active',
            'salary' => 3500000,
        ]);

        $loan = Loan::create([
            'employee_id' => $employee->id,
            'loan_date' => '2026-10-01',
            'principal' => 1000000,
            'status' => 'active',
        ]);

        $payroll = Payroll::create([
            'employee_id' => $employee->id,
            'period_year' => 2026,
            'period_month' => 10,
            'base_salary' => 3500000,
            'pinjaman_deduction' => 200000,
            'grand_total' => 3300000,
        ]);

        // Index page contains Sisa Pinjaman header and value
        $indexResponse = $this->get(route('payrolls.index', ['year' => 2026, 'month' => 10]));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Sisa Pinjaman');
        $indexResponse->assertSee('1.000.000');

        // Slip page contains Sisa Pinjaman
        $slipResponse = $this->get(route('payrolls.show', $payroll->id));
        $slipResponse->assertOk();
        $slipResponse->assertSee('Sisa Pinjaman:');
        $slipResponse->assertSee('1.000.000');
    }

    public function test_role_admin_masking_for_mandor_and_admin(): void
    {
        $golMandorAdmin = Golongan::create([
            'name' => 'Golongan Mandor & Admin',
            'type' => 'mandor_admin',
            'is_confidential' => true,
        ]);

        $mandor = Employee::create([
            'employee_id' => 'MDR-01',
            'name' => 'Pak Mandor Hebat',
            'position' => 'Mandor',
            'golongan_id' => $golMandorAdmin->id,
            'salary' => 6000000,
            'status' => 'active',
        ]);

        $staff = Employee::create([
            'employee_id' => 'STF-01',
            'name' => 'Bambang Gudang',
            'position' => 'Gudang',
            'salary' => 2000000,
            'status' => 'active',
        ]);

        $payrollMandor = Payroll::create([
            'employee_id' => $mandor->id,
            'period_year' => 2026,
            'period_month' => 10,
            'base_salary' => 6000000,
            'grand_total' => 6000000,
        ]);

        $payrollStaff = Payroll::create([
            'employee_id' => $staff->id,
            'period_year' => 2026,
            'period_month' => 10,
            'base_salary' => 2000000,
            'grand_total' => 2000000,
        ]);

        // Login as Admin Operasional
        $this->loginAsAdminOperasional();

        // 1. Master Gaji
        $gajiResponse = $this->get(route('gajis.index'));
        $gajiResponse->assertOk();
        $gajiResponse->assertSee('Pak Mandor Hebat');
        $gajiResponse->assertDontSee('6.000.000'); // gaji mandor tidak bisa dilihat
        $gajiResponse->assertSee('2.000.000');     // gaji staff gudang bisa dilihat

        // Edit gaji mandor forbidden for Admin
        $this->get(route('gajis.edit', $mandor->id))->assertForbidden();

        // 2. Payroll Index
        $payrollResponse = $this->get(route('payrolls.index', ['year' => 2026, 'month' => 10]));
        $payrollResponse->assertOk();
        $payrollResponse->assertSee('Pak Mandor Hebat');
        $payrollResponse->assertDontSee('6.000.000'); // nominal rupiah mandor tidak bisa dilihat

        // Cetak slip mandor forbidden for Admin
        $this->get(route('payrolls.show', $payrollMandor->id))->assertForbidden();

        // Cetak slip staff gudang boleh
        $this->get(route('payrolls.show', $payrollStaff->id))->assertOk();

        // 3. Super Admin can see all and edit
        $this->loginAsSuperAdmin();
        $gajiAdminResponse = $this->get(route('gajis.index'));
        $gajiAdminResponse->assertSee('6.000.000');

        $this->get(route('gajis.edit', $mandor->id))->assertOk();
        $this->get(route('payrolls.show', $payrollMandor->id))->assertOk();
    }
}
