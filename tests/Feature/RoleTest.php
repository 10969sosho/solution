<?php

namespace Tests\Feature;

use App\Models\Employee;
use Tests\TestCase;

class RoleTest extends TestCase
{
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/employees');

        $response->assertRedirect(route('login'));
    }

    public function test_login_with_valid_credentials(): void
    {
        $user = $this->loginAsSuperAdmin();

        $this->post(route('logout'));

        $response = $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_operasional_cannot_access_payroll(): void
    {
        $this->loginAsAdminOperasional();

        $this->get(route('payrolls.index'))->assertForbidden();
    }

    public function test_super_admin_can_access_payroll(): void
    {
        $this->loginAsSuperAdmin();

        $this->get(route('payrolls.index'))->assertOk();
    }

    public function test_admin_operasional_only_sees_operational_employees(): void
    {
        $this->loginAsAdminOperasional();

        Employee::create([
            'employee_id' => 'G001',
            'name' => 'Karyawan Gudang',
            'position' => 'Gudang',
            'status' => 'active',
        ]);

        Employee::create([
            'employee_id' => 'M001',
            'name' => 'Karyawan Mandor',
            'position' => 'Mandor',
            'status' => 'active',
        ]);

        $response = $this->get(route('employees.index'));

        $response->assertOk();
        $response->assertSee('Karyawan Gudang');
        $response->assertDontSee('Karyawan Mandor');
    }

    public function test_admin_operasional_cannot_edit_non_operational_employee(): void
    {
        $this->loginAsAdminOperasional();

        $employee = Employee::create([
            'employee_id' => 'M001',
            'name' => 'Karyawan Mandor',
            'position' => 'Mandor',
            'status' => 'active',
        ]);

        $this->get(route('employees.edit', $employee))->assertForbidden();
    }

    public function test_admin_operasional_cannot_create_non_operational_employee(): void
    {
        $this->loginAsAdminOperasional();

        $this->post(route('employees.store'), [
            'employee_id' => 'A001',
            'name' => 'Karyawan Admin',
            'position' => 'Admin',
            'status' => 'active',
        ])->assertForbidden();
    }

    public function test_admin_operasional_cannot_set_salary(): void
    {
        $this->loginAsAdminOperasional();

        $this->post(route('employees.store'), [
            'employee_id' => 'G002',
            'name' => 'Karyawan Gudang Baru',
            'position' => 'Gudang',
            'status' => 'active',
            'salary' => 5000000,
            'salary_tier' => 'A',
        ])->assertRedirect(route('employees.index'));

        $employee = Employee::where('employee_id', 'G002')->first();
        $this->assertNotEquals(5000000, (float) $employee->salary);
        $this->assertNull($employee->salary_tier);
    }

    public function test_admin_operasional_reports_are_operational_only(): void
    {
        $this->loginAsAdminOperasional();

        Employee::create([
            'employee_id' => 'G001',
            'name' => 'Karyawan Gudang',
            'position' => 'Gudang',
            'status' => 'active',
        ]);

        Employee::create([
            'employee_id' => 'M001',
            'name' => 'Karyawan Mandor',
            'position' => 'Mandor',
            'status' => 'active',
        ]);

        $response = $this->get(route('reports.summary', ['year' => 2026, 'month' => 8]));

        $response->assertOk();
        $response->assertSee('Karyawan Gudang');
        $response->assertDontSee('Karyawan Mandor');
    }
}