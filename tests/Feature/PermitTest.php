<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Permit;
use Tests\TestCase;

class PermitTest extends TestCase
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

    public function test_short_permit_is_no_deduction(): void
    {
        $response = $this->post(route('permits.store'), [
            'employee_id' => $this->employee->id,
            'permit_date' => '2026-08-10',
            'start_time' => '08:05',
            'end_time' => '08:15',
            'reason' => 'Ban gembos',
        ]);

        $response->assertRedirect();
        $permit = Permit::first();

        $this->assertSame(10, $permit->duration_minutes);
        $this->assertSame(Permit::TYPE_NO_DEDUCTION, $permit->type);
    }

    public function test_long_permit_is_salary_deduction(): void
    {
        $response = $this->post(route('permits.store'), [
            'employee_id' => $this->employee->id,
            'permit_date' => '2026-08-10',
            'start_time' => '10:00',
            'end_time' => '13:00',
            'reason' => 'Urusan keluarga',
        ]);

        $response->assertRedirect();
        $permit = Permit::first();

        $this->assertSame(180, $permit->duration_minutes);
        $this->assertSame(Permit::TYPE_SALARY_DEDUCTION, $permit->type);
    }

    public function test_permit_index_page_loads(): void
    {
        Permit::create([
            'employee_id' => $this->employee->id,
            'permit_date' => '2026-08-10',
            'start_time' => '08:05',
            'end_time' => '08:15',
            'duration_minutes' => 10,
            'type' => Permit::TYPE_NO_DEDUCTION,
            'status' => 'approved',
        ]);

        $response = $this->get(route('permits.index'));

        $response->assertStatus(200);
        $response->assertSee('Budi');
    }
}