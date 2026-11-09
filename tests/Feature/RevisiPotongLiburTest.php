<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Jabatan;
use App\Models\Lokasi;
use App\Models\Payroll;
use Tests\TestCase;

/**
 * Revisi:
 * - Rekap Bulanan: HARI POT. MASUK -> POT. HARI LIBUR, NOMINAL POT. MASUK -> NOMINAL POT. LIBUR
 * - Payroll & Slip Gaji: POT. MASUK -> POT. LIBUR
 * - Payroll & Slip Gaji: kolom Jabatan, Lokasi, No. Rekening (tanpa rekening -> TUNAI)
 * - Pinjaman: pilihan Potong Gaji Bulan "1 Januari ... 1 Desember"
 */
class RevisiPotongLiburTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsSuperAdmin();
    }

    public function test_monthly_recap_uses_pot_libur_labels(): void
    {
        $response = $this->get(route('attendance.monthly-recap', ['year' => 2026, 'month' => 10]));

        $response->assertOk();
        $response->assertSee('Pot. Hari Libur');
        $response->assertSee('Nominal Pot. Libur');
        $response->assertDontSee('Hari Pot. Masuk');
        $response->assertDontSee('Nominal Pot. Masuk');
    }

    private function makeEmployeeWithMaster(array $attributes = []): Employee
    {
        $jabatan = Jabatan::create(['name' => 'Mandor Produksi']);
        $lokasi = Lokasi::create(['name' => 'Kandang Timur']);

        return Employee::create(array_merge([
            'employee_id' => 'RPL001',
            'name' => 'Karyawan Revisi Payroll',
            'position' => 'Kandang',
            'status' => 'active',
            'salary' => 3000000,
            'jabatan_id' => $jabatan->id,
            'lokasi_id' => $lokasi->id,
        ], $attributes));
    }

    private function makePayroll(Employee $employee, array $attributes = []): Payroll
    {
        return Payroll::create(array_merge([
            'employee_id' => $employee->id,
            'period_year' => 2026,
            'period_month' => 10,
            'base_salary' => (float) $employee->salary,
            'grand_total' => (float) $employee->salary,
            'net_salary' => (float) $employee->salary,
        ], $attributes));
    }

    public function test_payroll_index_shows_jabatan_lokasi_rekening_and_tunai_without_account(): void
    {
        // Tanpa nomor rekening: meski pengaturan transfer, metode tetap TUNAI
        $employee = $this->makeEmployeeWithMaster(['account_number' => null, 'payment_method' => 'transfer']);
        $this->makePayroll($employee);

        $response = $this->get(route('payrolls.index', ['year' => 2026, 'month' => 10]));

        $response->assertOk();
        $response->assertSee('Jabatan');
        $response->assertSee('Lokasi');
        $response->assertSee('No. Rekening');
        $response->assertSee('Mandor Produksi');
        $response->assertSee('Kandang Timur');
        $response->assertSee('TUNAI');
        $response->assertDontSee('TRANSFER');
        $response->assertSee('Pot. Libur');
        $response->assertDontSee('Pot. Masuk');

        $this->assertSame('cash', $employee->effectivePaymentMethod());
    }

    public function test_payroll_index_shows_transfer_when_employee_has_account(): void
    {
        $employee = $this->makeEmployeeWithMaster([
            'employee_id' => 'RPL002',
            'account_number' => '1234567890',
            'bank_name' => 'BRI',
            'payment_method' => 'transfer',
        ]);
        $this->makePayroll($employee);

        $response = $this->get(route('payrolls.index', ['year' => 2026, 'month' => 10]));

        $response->assertOk();
        $response->assertSee('1234567890');
        $response->assertSee('TRANSFER');
        $response->assertDontSee('>TUNAI<', false);

        $this->assertSame('transfer', $employee->effectivePaymentMethod());
    }

    public function test_slip_gaji_shows_pot_libur_lokasi_rekening_and_tunai(): void
    {
        $employee = $this->makeEmployeeWithMaster(['account_number' => null]);
        $payroll = $this->makePayroll($employee, ['potongan_masuk' => 100000, 'hari_potong_masuk' => 1]);

        $response = $this->get(route('payrolls.show', $payroll->id));

        $response->assertOk();
        $response->assertSee('Lokasi:');
        $response->assertSee('Kandang Timur');
        $response->assertSee('Nomor Rekening:');
        $response->assertSee('Mandor Produksi');
        $response->assertSee('TUNAI');
        $response->assertSee('Potongan Libur');
        $response->assertDontSee('Potongan Masuk');
        $response->assertDontSee('Pot. Masuk');
    }

    public function test_potong_pinjaman_form_uses_first_of_month_options(): void
    {
        \App\Models\Loan::create([
            'employee_id' => $this->makeEmployeeWithMaster()->id,
            'loan_date' => '2026-10-01',
            'principal' => 500000,
            'status' => 'active',
        ]);

        $response = $this->get(route('loans.potong'));

        $response->assertOk();
        $response->assertSee('Potong Gaji Bulan', false);
        $response->assertSee('name="payment_month"', false);
        $response->assertSee('1 Januari '.now()->year, false);
        $response->assertSee('1 Februari '.now()->year, false);
        $response->assertSee('1 Desember '.(now()->year + 1), false);
        $response->assertDontSee('type="month"', false);
    }
}
