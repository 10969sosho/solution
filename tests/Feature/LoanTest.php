<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Loan;
use App\Models\LoanPayment;
use Tests\TestCase;

class LoanTest extends TestCase
{
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsSuperAdmin();

        $this->employee = Employee::create([
            'employee_id' => '1001',
            'name' => 'Budi',
            'position' => 'Gudang',
            'status' => 'active',
        ]);
    }

    public function test_loan_remaining_balance_is_auto_calculated(): void
    {
        $loan = Loan::create([
            'employee_id' => $this->employee->id,
            'loan_date' => '2026-08-01',
            'principal' => 1000000,
            'status' => 'active',
        ]);

        $this->assertSame(1000000.0, (float) $loan->remaining_balance);

        LoanPayment::create([
            'loan_id' => $loan->id,
            'employee_id' => $this->employee->id,
            'payment_date' => '2026-08-10',
            'amount' => 400000,
        ]);

        $this->assertSame(600000.0, (float) $loan->fresh()->remaining_balance);
    }

    public function test_loan_becomes_paid_when_fully_paid(): void
    {
        $loan = Loan::create([
            'employee_id' => $this->employee->id,
            'loan_date' => '2026-08-01',
            'principal' => 500000,
            'status' => 'active',
        ]);

        $response = $this->post(route('loans.payments', $loan), [
            'payment_date' => '2026-08-10',
            'amount' => 500000,
        ]);

        $response->assertRedirect();
        $this->assertSame('paid', $loan->fresh()->status);
    }

    public function test_loan_index_page_loads(): void
    {
        Loan::create([
            'employee_id' => $this->employee->id,
            'loan_date' => '2026-08-01',
            'principal' => 1000000,
            'status' => 'active',
        ]);

        $response = $this->get(route('loans.index'));

        $response->assertStatus(200);
        $response->assertSee('Budi');
    }
}