<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Payroll;
use App\Models\Permit;
use App\Services\PayrollService;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsSuperAdmin();

        $this->employee = Employee::create([
            'employee_id' => '2001',
            'name' => 'Andi',
            'position' => 'Kandang',
            'status' => 'active',
            'salary' => 3000000,
            'salary_tier' => 'B',
            'join_date' => '2020-01-15',
        ]);
    }

    public function test_late_fine_is_tiered_by_minutes(): void
    {
        $service = app(PayrollService::class);
        $this->employee->salary = 3000000;
        $this->employee->save();

        $data = $service->calculate($this->employee, 2026, 8);
        $this->assertArrayHasKey('late_deduction', $data);
    }

    public function test_loan_payment_deducted_from_payroll(): void
    {
        $loan = Loan::create([
            'employee_id' => $this->employee->id,
            'loan_date' => '2026-07-01',
            'principal' => 1000000,
            'status' => 'active',
        ]);

        LoanPayment::create([
            'loan_id' => $loan->id,
            'employee_id' => $this->employee->id,
            'payment_date' => '2026-08-10',
            'amount' => 250000,
        ]);

        $payroll = app(PayrollService::class)->generate($this->employee, 2026, 8);

        $this->assertSame(250000.0, (float) $payroll->loan_deduction);
    }

    public function test_attendance_bonus_given_when_no_approved_permit(): void
    {
        $payroll = app(PayrollService::class)->generate($this->employee, 2026, 8);

        $this->assertSame(200000.0, (float) $payroll->attendance_bonus);
    }

    public function test_attendance_bonus_skipped_when_approved_permit_exists(): void
    {
        Permit::create([
            'employee_id' => $this->employee->id,
            'permit_date' => '2026-08-12',
            'type' => 'no_deduction',
            'reason' => 'Acara keluarga',
            'status' => 'approved',
            'duration_minutes' => 120,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        $payroll = app(PayrollService::class)->generate($this->employee, 2026, 8);

        $this->assertSame(0.0, (float) $payroll->attendance_bonus);
    }

    public function test_paid_payroll_cannot_be_regenerated(): void
    {
        $payroll = app(PayrollService::class)->generate($this->employee, 2026, 8);
        $payroll->update(['status' => 'paid']);

        $this->expectException(\RuntimeException::class);
        app(PayrollService::class)->generate($this->employee, 2026, 8);
    }

    public function test_thr_full_for_long_service(): void
    {
        $thr = app(PayrollService::class)->calculateThr($this->employee, 2026);

        $this->assertTrue($thr['long_service']);
        $this->assertSame(3000000.0, (float) $thr['thr']);
    }

    public function test_thr_prorated_for_short_service(): void
    {
        $this->employee->join_date = '2026-05-01';
        $this->employee->save();

        $thr = app(PayrollService::class)->calculateThr($this->employee, 2026);

        $this->assertFalse($thr['long_service']);
        $this->assertLessThan(3000000.0, (float) $thr['thr']);
    }

    public function test_payroll_index_page_loads(): void
    {
        app(PayrollService::class)->generate($this->employee, 2026, 8);

        $response = $this->get(route('payrolls.index', ['year' => 2026, 'month' => 8]));

        $response->assertStatus(200);
        $response->assertSee('Andi');
    }

    public function test_thr_report_page_loads(): void
    {
        $response = $this->get(route('payrolls.thr', ['year' => 2026]));

        $response->assertStatus(200);
        $response->assertSee('Andi');
    }
}