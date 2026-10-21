<?php

namespace Tests\Feature;

use App\Models\BonusLiburSetting;
use App\Models\DailyAttendance;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Services\PayrollService;
use Tests\TestCase;

/**
 * Pengujian untuk 7 item CATATAN REVISI PROGRAM PAYROLL.
 */
class CatatanRevisiTest extends TestCase
{
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsSuperAdmin();

        $this->employee = Employee::create([
            'employee_id' => 'REV001',
            'name' => 'Reka Revisi',
            'position' => 'Kandang',
            'status' => 'active',
            'salary' => 3000000,
        ]);
    }

    /**
     * Item 6: Master Bonus Libur - menu untuk mengubah variabel A/B/C/D.
     */
    public function test_master_bonus_libur_menu_loads_and_saves_variables(): void
    {
        $response = $this->get(route('bonus-libur.index'));
        $response->assertOk();
        $response->assertSee('Master Bonus Libur');
        $response->assertSee('Batas Gaji');

        $this->put(route('bonus-libur.update'), [
            'batas_gaji' => 2000000,
            'bonus_satu_hari' => 80000,
            'bonus_setengah_hari' => 40000,
            'pengali_bonus' => 2,
        ])->assertRedirect(route('bonus-libur.index'));

        $setting = BonusLiburSetting::current();
        $this->assertSame(2000000.0, (float) $setting->batas_gaji);
        $this->assertSame(80000.0, (float) $setting->bonus_satu_hari);
        $this->assertSame(40000.0, (float) $setting->bonus_setengah_hari);
        $this->assertSame(2.0, (float) $setting->pengali_bonus);
    }

    /**
     * Item 6: variabel Master Bonus Libur dipakai pada perhitungan payroll.
     */
    public function test_bonus_libur_uses_master_settings(): void
    {
        BonusLiburSetting::current()->update([
            'batas_gaji' => 2250000,
            'bonus_satu_hari' => 75000,
            'bonus_setengah_hari' => 50000,
            'pengali_bonus' => 1.5,
        ]);

        $this->employee->leave_quota = 2;
        $this->employee->save();

        $payroll = app(PayrollService::class)->generate($this->employee, 2026, 10);

        // Gaji 3.000.000 >= batas 2.250.000 => (3.000.000 / 30) * 1,5 * 2 hari = 300.000
        $this->assertSame(300000.0, (float) $payroll->bonus_libur);
    }

    /**
     * Item 7: Create Potong Pinjaman ada di menu Pinjaman/Kasbon.
     */
    public function test_potong_pinjaman_page_available(): void
    {
        $loan = Loan::create([
            'employee_id' => $this->employee->id,
            'loan_date' => '2026-10-01',
            'principal' => 1000000,
            'status' => 'active',
        ]);

        $response = $this->get(route('loans.potong'));
        $response->assertOk();
        $response->assertSee('Potong Pinjaman');
        $response->assertSee('Reka Revisi');

        $response = $this->get(route('loans.index'));
        $response->assertSee('Potong Pinjaman');
        $response->assertSee('Tambah Pinjaman');

        $this->assertSame(1000000.0, (float) $loan->remaining_balance);
    }

    /**
     * Item 7: Payroll memakai transaksi Potong Pinjaman, tanpa input manual per baris.
     */
    public function test_payroll_loan_deduction_comes_from_potong_pinjaman(): void
    {
        $loan = Loan::create([
            'employee_id' => $this->employee->id,
            'loan_date' => '2026-10-01',
            'principal' => 1000000,
            'status' => 'active',
        ]);

        LoanPayment::create([
            'loan_id' => $loan->id,
            'employee_id' => $this->employee->id,
            'payment_date' => '2026-10-15',
            'amount' => 250000,
        ]);

        $payroll = app(PayrollService::class)->generate($this->employee, 2026, 10);
        $this->assertSame(250000.0, (float) $payroll->loan_deduction);

        // Tidak ada lagi input manual di baris payroll / slip gaji.
        $this->get(route('payrolls.index', ['year' => 2026, 'month' => 10]))
            ->assertOk()
            ->assertDontSee('name="pinjaman_deduction"', false)
            ->assertDontSee('Pot. Pinjaman (Manual)', false);

        $this->get(route('payrolls.show', $payroll->id))
            ->assertOk()
            ->assertDontSee('name="pinjaman_deduction"', false);
    }

    /**
     * Item 3 & 5: Kelola Jam menandai jam yang diedit, dan komponen lama dihapus.
     */
    public function test_kelola_jam_marks_edited_time_and_simplifies_menu(): void
    {
        $record = DailyAttendance::create([
            'employee_id' => $this->employee->id,
            'date' => '2026-10-10',
            'check_in' => '08:00:00',
            'break_out' => '12:00:00',
            'break_in' => '13:00:00',
            'check_out' => '17:00:00',
            'is_fixed' => false,
        ]);

        $this->post(route('attendance.daily.save', $record->id), [
            'check_in' => '09:00',
            'break_out' => '12:00',
            'break_in' => '13:00',
            'check_out' => '17:00',
        ])->assertRedirect();

        $record->refresh();
        $this->assertSame('09:00:00', $record->check_in);
        $this->assertArrayHasKey('check_in', $record->edited_fields);
        $this->assertArrayNotHasKey('check_out', $record->edited_fields);

        $response = $this->get(route('attendance.daily', ['date' => '2026-10-10']));
        $response->assertOk();
        $response->assertSee('Kelola Jam');
        $response->assertDontSee('Kelola Jam &amp; Ket', false);
        // Komponen yang dihapus dari modal Kelola Jam
        $response->assertDontSee('Status Kehadiran', false);
        $response->assertDontSee('Lembur (Menit)', false);
        $response->assertDontSee('Catatan / Keterangan Notes', false);
        // Component yang dihapus dari tabel Absen Daily
        $response->assertDontSee('>Lembur</th>', false);
        $response->assertDontSee('Keterangan (Notes)', false);
    }

    /**
     * Item 4: baris dengan absen kosong/bolong diberi stabilo merah.
     */
    public function test_empty_attendance_row_is_highlighted_red(): void
    {
        DailyAttendance::create([
            'employee_id' => $this->employee->id,
            'date' => '2026-10-11',
            'check_in' => null,
            'check_out' => null,
            'keterangan' => 'alpha',
            'is_fixed' => false,
        ]);

        $response = $this->get(route('attendance.daily', ['date' => '2026-10-11']));
        $response->assertOk();
        $response->assertSee('bg-red-100', false);
        $response->assertSee('Absen Kosong');
    }

    /**
     * Item 4: absen bolong (scan istirahat tidak lengkap) juga stabilo merah.
     */
    public function test_bolong_attendance_row_is_highlighted_red(): void
    {
        DailyAttendance::create([
            'employee_id' => $this->employee->id,
            'date' => '2026-10-12',
            'check_in' => '08:00:00',
            'break_out' => null,
            'break_in' => null,
            'check_out' => '17:00:00',
            'keterangan' => 'hadir',
            'is_fixed' => false,
        ]);

        $response = $this->get(route('attendance.daily', ['date' => '2026-10-12']));
        $response->assertOk();
        $response->assertSee('Absen Bolong');
        $response->assertSee('bg-red-100', false);
    }

    /**
     * Item 1: Jaga Malam - petugas & nominal diatur di Data Karyawan, tampil ala Absen Daily.
     */
    public function test_jaga_malam_shows_assigned_guards_with_nominal(): void
    {
        $guard = Employee::create([
            'employee_id' => 'REV002',
            'name' => 'Joko Jaga',
            'position' => 'Kandang',
            'status' => 'active',
            'salary' => 2500000,
            'is_night_guard' => true,
            'uang_jaga_malam' => 350000,
        ]);

        $response = $this->get(route('attendance.night-guard', ['date' => '2026-10-11']));
        $response->assertOk();
        $response->assertSee('Jaga Malam');
        $response->assertSee('Joko Jaga');
        $response->assertSee('350.000');

        // Uang jaga malam masuk ke komponen bonus payroll.
        $payroll = app(PayrollService::class)->generate($guard, 2026, 10);
        $this->assertSame(350000.0, (float) $payroll->uang_jaga_malam);
    }

    /**
     * Item 1: nominal uang jaga malam bisa diedit melalui Data Karyawan.
     */
    public function test_uang_jaga_malam_editable_from_data_karyawan(): void
    {
        $this->post(route('employees.store'), [
            'employee_id' => 'REV003',
            'name' => 'Wawan Malam',
            'position' => 'Kandang',
            'status' => 'active',
            'is_night_guard' => '1',
            'uang_jaga_malam' => 425000,
        ])->assertRedirect(route('employees.index'));

        $employee = Employee::where('employee_id', 'REV003')->first();
        $this->assertTrue((bool) $employee->is_night_guard);
        $this->assertSame(425000.0, (float) $employee->uang_jaga_malam);
    }
}
