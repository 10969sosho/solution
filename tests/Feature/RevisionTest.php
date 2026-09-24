<?php

namespace Tests\Feature;

use App\Models\DailyAttendance;
use App\Models\Employee;
use App\Models\Golongan;
use App\Models\Payroll;
use App\Models\Permit;
use App\Models\WorkSetting;
use Tests\TestCase;

class RevisionTest extends TestCase
{
    public function test_settings_edit_renders_golongan_checkboxes_without_raw_code(): void
    {
        $this->loginAsSuperAdmin();
        Golongan::create(['name' => 'Mandor', 'type' => 'mandor_admin']);
        $setting = WorkSetting::create([
            'name' => 'Default',
            'check_in_time' => '08:00:00',
            'check_out_time' => '17:00:00',
            'break_out_time' => '12:00:00',
            'break_in_time' => '13:00:00',
            'late_tolerance_minutes' => 10,
            'overtime_threshold_minutes' => 60,
            'is_active' => true,
        ]);

        $response = $this->get(route('settings.edit', $setting));
        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringNotContainsString("pluck('id')", $content, 'RAW pluck text found in edit page');
        $this->assertStringContainsString('name="golongan_ids[]"', $content);
    }

    public function test_payroll_generate_skips_paid_instead_of_500(): void
    {
        $this->loginAsSuperAdmin();

        $employee = Employee::create([
            'employee_id' => 'EMPG001',
            'name' => 'Generate Test',
            'position' => 'Kandang',
            'status' => 'active',
            'salary' => 3000000,
        ]);

        $paid = Payroll::create([
            'employee_id' => $employee->id,
            'period_year' => 2026,
            'period_month' => 8,
            'base_salary' => 3000000,
            'grand_total' => 3000000,
            'net_salary' => 3000000,
            'status' => 'paid',
        ]);

        $response = $this->post(route('payrolls.generate'), [
            'year' => 2026,
            'month' => 8,
        ]);

        $response->assertRedirect(route('payrolls.index', ['year' => 2026, 'month' => 8]));
        $paid->refresh();
        $this->assertSame('paid', $paid->status);
        $this->assertSame(3000000.0, (float) $paid->grand_total);
        $this->assertSame(1, Payroll::where('period_year', 2026)->where('period_month', 8)->count());
    }

    public function test_payroll_generate_creates_fresh_period(): void
    {
        $this->loginAsSuperAdmin();

        Employee::create([
            'employee_id' => 'EMPG002',
            'name' => 'Fresh Generate',
            'position' => 'Kandang',
            'status' => 'active',
            'salary' => 3000000,
        ]);

        $response = $this->post(route('payrolls.generate'), [
            'year' => 2026,
            'month' => 9,
        ]);

        $response->assertRedirect(route('payrolls.index', ['year' => 2026, 'month' => 9]));
        $this->assertSame(1, Payroll::where('period_year', 2026)->where('period_month', 9)->count());
    }

    public function test_daily_page_action_only_kelola_izin(): void
    {
        $this->loginAsSuperAdmin();

        Employee::create([
            'employee_id' => 'EMPD001',
            'name' => 'Daily Revision',
            'position' => 'Kandang',
            'status' => 'active',
            'salary' => 2500000,
        ]);

        $response = $this->get(route('attendance.daily', ['date' => '2026-08-10']));
        $response->assertOk();
        $response->assertSee('Kelola Izin');
        $response->assertSee('Daily Revision');
        $response->assertDontSee('>Nominal Izin<', false);
        $response->assertDontSee('>Keterangan</th>', false);
        $response->assertDontSee('name="nominal_izin"', false);
        $response->assertDontSee('name="tipe_nominal_izin"', false);
        $response->assertDontSee('name="keterangan"', false);
        $response->assertSee('permit-rows-permit-modal-', false);
    }

    public function test_store_multiple_permits_syncs_daily_attendance(): void
    {
        $this->loginAsSuperAdmin();

        $employee = Employee::create([
            'employee_id' => 'EMPI001',
            'name' => 'Izin Multiple',
            'position' => 'Kandang',
            'status' => 'active',
            'salary' => 2500000,
        ]);

        $record = DailyAttendance::create([
            'employee_id' => $employee->id,
            'date' => '2026-08-10',
            'check_in' => '08:30:00',
            'late_check_in_minutes' => 30,
            'is_anomaly' => true,
            'is_fixed' => false,
        ]);

        $response = $this->post(route('permits.store'), [
            'source' => 'attendance_daily',
            'employee_id' => $employee->id,
            'permit_date' => '2026-08-10',
            'permits' => [
                [
                    'category' => 'terlambat',
                    'type' => 'salary_deduction',
                    'start_time' => '08:00',
                    'end_time' => '08:15',
                    'nominal' => 50000,
                    'reason' => 'Telat macet',
                ],
                [
                    'category' => 'pulang_awal',
                    'type' => 'addition',
                    'start_time' => '08:00',
                    'end_time' => '08:15',
                    'nominal' => 20000,
                    'reason' => 'Bantuan tambahan',
                ],
            ],
        ]);

        $response->assertRedirect(route('attendance.daily', ['date' => '2026-08-10']));
        $this->assertSame(2, Permit::where('employee_id', $employee->id)->whereDate('permit_date', '2026-08-10')->count());

        $record->refresh();
        $this->assertSame('izin', $record->keterangan);
        $this->assertSame('potong_gaji', $record->tipe_nominal_izin);
        $this->assertSame(30000.0, (float) $record->nominal_izin);
        $this->assertTrue($record->isLateIgnored());
    }

    public function test_store_multiple_requires_nominal_for_money_and_minutes_for_no_deduction(): void
    {
        $this->loginAsSuperAdmin();

        $employee = Employee::create([
            'employee_id' => 'EMPI002',
            'name' => 'Validasi Izin',
            'position' => 'Kandang',
            'status' => 'active',
            'salary' => 2500000,
        ]);

        $this->post(route('permits.store'), [
            'employee_id' => $employee->id,
            'permit_date' => '2026-08-10',
            'permits' => [
                [
                    'category' => 'terlambat',
                    'type' => 'salary_deduction',
                    'start_time' => '08:00',
                    'end_time' => '08:15',
                    'reason' => 'Tanpa nominal',
                ],
            ],
        ])->assertSessionHasErrors('permits.0.nominal');

        $this->post(route('permits.store'), [
            'employee_id' => $employee->id,
            'permit_date' => '2026-08-10',
            'permits' => [
                [
                    'category' => 'terlambat',
                    'type' => 'no_deduction',
                    'start_time' => '08:00',
                    'end_time' => '08:15',
                    'nominal' => 1000,
                    'reason' => 'Tanpa menit',
                ],
            ],
        ])->assertSessionHasErrors('permits.0.permit_minutes');

        $this->assertSame(0, Permit::count());
    }

    public function test_destroy_permit_resyncs_daily_attendance(): void
    {
        $this->loginAsSuperAdmin();

        $employee = Employee::create([
            'employee_id' => 'EMPI003',
            'name' => 'Hapus Izin',
            'position' => 'Kandang',
            'status' => 'active',
            'salary' => 2500000,
        ]);

        DailyAttendance::create([
            'employee_id' => $employee->id,
            'date' => '2026-08-10',
            'keterangan' => 'izin',
            'tipe_nominal_izin' => 'potong_gaji',
            'nominal_izin' => 50000,
            'is_fixed' => false,
        ]);

        $permit = Permit::create([
            'employee_id' => $employee->id,
            'category' => 'terlambat',
            'permit_date' => '2026-08-10',
            'type' => 'salary_deduction',
            'start_time' => '08:00',
            'end_time' => '08:15',
            'duration_minutes' => 15,
            'nominal' => 50000,
            'reason' => 'Sudah dihapus',
            'status' => 'approved',
        ]);

        $this->delete(route('permits.destroy', $permit))->assertRedirect(route('permits.index'));

        $record = DailyAttendance::where('employee_id', $employee->id)->where('date', '2026-08-10')->first();
        $this->assertNull($record->tipe_nominal_izin);
        $this->assertSame(0.0, (float) $record->nominal_izin);
    }
}
