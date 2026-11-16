<?php

namespace Tests\Feature;

use App\Models\AttendanceCompilation;
use App\Models\AttendanceLog;
use App\Models\DailyAttendance;
use App\Models\Device;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Golongan;
use App\Models\Jabatan;
use App\Models\Permit;
use App\Models\SeasonalSchedule;
use App\Models\User;
use App\Models\WorkSetting;
use Carbon\Carbon;
use Tests\TestCase;

class RevisiSistemPayrollTest extends TestCase
{
    private User $superAdmin;
    private User $admin;
    private Employee $staff;
    private Employee $mandor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'email' => 'owner@test.com',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin_operasional',
            'email' => 'admin@test.com',
        ]);

        $golStaff = Golongan::create(['name' => 'Golongan Staff', 'type' => 'gudang_kandang']);
        $golMandor = Golongan::create(['name' => 'Golongan Mandor', 'type' => 'mandor_admin']);

        $jabatanStaff = Jabatan::create(['name' => 'Staff Gudang']);
        $jabatanMandor = Jabatan::create(['name' => 'Mandor Lapangan']);

        $this->staff = Employee::create([
            'employee_id' => 'EMP00100',
            'name' => 'Staff Biasa',
            'position' => 'Gudang',
            'status' => 'active',
            'salary' => 3000000,
            'golongan_id' => $golStaff->id,
            'jabatan_id' => $jabatanStaff->id,
            'uang_jaga_malam' => 250000,
        ]);

        $this->mandor = Employee::create([
            'employee_id' => 'EMP00200',
            'name' => 'Mandor Hebat',
            'position' => 'Mandor Lapangan',
            'status' => 'active',
            'salary' => 6000000,
            'golongan_id' => $golMandor->id,
            'jabatan_id' => $jabatanMandor->id,
            'uang_jaga_malam' => 600000,
        ]);
    }

    /**
     * 1. Sinkronisasi Data Mesin ke Absen Daily
     */
    public function test_machine_scan_logs_sync_automatically_to_daily_attendance(): void
    {
        $this->actingAs($this->superAdmin);

        $date = '2026-11-16';
        WorkSetting::create([
            'golongan_id' => $this->staff->golongan_id,
            'effective_from' => '2026-01-01',
            'check_in_time' => '08:00:00',
            'break_out_time' => '12:00:00',
            'break_in_time' => '13:00:00',
            'check_out_time' => '17:00:00',
            'late_tolerance_minutes' => 15,
        ]);

        // Kirim scan ADMS cdata untuk employee staff
        $cdataPayload = "EMP00100\t2026-11-16 07:55:00\t0\t1\t0\n"
                      . "EMP00100\t2026-11-16 12:05:00\t2\t1\t0\n"
                      . "EMP00100\t2026-11-16 12:55:00\t3\t1\t0\n"
                      . "EMP00100\t2026-11-16 17:05:00\t1\t1\t0\n";

        $response = $this->call(
            'POST',
            '/iclock/cdata?SN=TEST_SN_1&table=ATTLOG',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain'],
            $cdataPayload
        );

        $response->assertOk();

        // Verifikasi DailyAttendance terbuat dan tersinkronisasi otomatis
        $daily = DailyAttendance::where('employee_id', $this->staff->id)
            ->where('date', $date)
            ->first();

        $this->assertNotNull($daily);
        $this->assertSame('07:55:00', $daily->check_in);
        $this->assertSame('12:05:00', $daily->break_out);
        $this->assertSame('12:55:00', $daily->break_in);
        $this->assertSame('17:05:00', $daily->check_out);
        $this->assertSame('hadir', $daily->keterangan);

        // Kunjungi halaman absen daily dan pastikan record tampil dengan benar
        $dailyPage = $this->get(route('attendance.daily', ['date' => $date]));
        $dailyPage->assertOk();
        $dailyPage->assertSee('Staff Biasa');
        $dailyPage->assertSee('07:55');
        $dailyPage->assertSee('17:05');
    }

    /**
     * 2. Format Waktu 24 Jam murni (tanpa AM/PM) pada Absen Daily & Kelola Jam
     */
    public function test_24_hour_format_in_daily_attendance_and_kelola_jam_modal(): void
    {
        $this->actingAs($this->superAdmin);

        DailyAttendance::create([
            'employee_id' => $this->staff->id,
            'date' => '2026-11-16',
            'check_in' => '14:30:00',
            'check_out' => '21:00:00',
            'keterangan' => 'hadir',
            'is_fixed' => false,
        ]);

        $response = $this->get(route('attendance.daily', ['date' => '2026-11-16']));
        $response->assertOk();

        // Di tabel tampil format 24 jam murni
        $response->assertSee('14:30');
        $response->assertSee('21:00');

        // Modal kelola jam menggunakan format 24 jam dengan input text class time-24h
        $response->assertSee('time-24h', false);
        $response->assertSee('Format 24 Jam', false);
    }

    /**
     * 3. Kalkulasi Potongan Gaji Izin Presisi Per Menit
     */
    public function test_permit_salary_deduction_calculated_precisely_per_minute(): void
    {
        $this->actingAs($this->superAdmin);

        // Staff salary = 3.000.000
        // Daily divider = 30 -> Gaji Harian = 100.000
        // Work minutes = 480 menit
        // Izin 120 menit -> 100.000 * 120 / 480 = 25.000
        $response = $this->post(route('permits.store'), [
            'source' => 'attendance_daily',
            'employee_id' => $this->staff->id,
            'permit_date' => '2026-11-16',
            'permits' => [
                [
                    'category' => 'terlambat',
                    'type' => 'salary_deduction',
                    'start_time' => '08:00',
                    'end_time' => '10:00',
                    'permit_minutes' => 120,
                    'reason' => 'Urusan keluarga',
                ],
            ],
        ]);

        $response->assertRedirect();

        $permit = Permit::where('employee_id', $this->staff->id)->first();
        $this->assertNotNull($permit);
        $this->assertEquals(25000.0, (float) $permit->nominal);

        $daily = DailyAttendance::where('employee_id', $this->staff->id)
            ->where('date', '2026-11-16')
            ->first();
        $this->assertNotNull($daily);
        $this->assertEquals(25000.0, (float) $daily->nominal_izin);
        $this->assertSame('potong_gaji', $daily->tipe_nominal_izin);
    }

    /**
     * 4. Restrukturisasi Sidebar Dropdown: Absen Daily & Absen Jaga Malam terpisah, Rekap Bulanan di HR
     */
    public function test_sidebar_dropdown_restructure(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('dashboard'));
        $response->assertOk();

        // Ada 2 dropdown utama:
        $response->assertSee('Dropdown 1: Absen Daily', false);
        $response->assertSee('Dropdown 2: Absen Jaga Malam', false);

        // Rekap Bulanan muncul di grup HR & Keuangan
        $response->assertSee(route('attendance.monthly-recap'));
    }

    /**
     * 5. Testing Jam Kerja Musiman & Khusus
     */
    public function test_seasonal_schedule_and_custom_schedule_attendance_classification(): void
    {
        $this->actingAs($this->superAdmin);

        // Setting default: 08:00 - 17:00
        WorkSetting::create([
            'golongan_id' => $this->staff->golongan_id,
            'effective_from' => '2026-01-01',
            'check_in_time' => '08:00:00',
            'break_out_time' => '12:00:00',
            'break_in_time' => '13:00:00',
            'check_out_time' => '17:00:00',
            'late_tolerance_minutes' => 15,
        ]);

        // Jadwal Musiman (Ramadan): Jam masuk 07:00, jam pulang 15:00
        SeasonalSchedule::create([
            'name' => 'Jadwal Musiman Puasa',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'force_check_in_time' => '07:00:00',
            'check_out_delta_minutes' => -120, // 17:00 - 2 jam = 15:00
            'is_active' => true,
        ]);

        $testDate = Carbon::parse('2026-03-10');

        // Tambah log scan masuk 06:55 dan pulang 15:05
        AttendanceLog::create([
            'user_id' => $this->staff->employee_id,
            'machine_sn' => 'TEST_SN_1',
            'scan_time' => '2026-03-10 06:55:00',
            'status' => '0',
        ]);
        AttendanceLog::create([
            'user_id' => $this->staff->employee_id,
            'machine_sn' => 'TEST_SN_1',
            'scan_time' => '2026-03-10 12:00:00',
            'status' => '2',
        ]);
        AttendanceLog::create([
            'user_id' => $this->staff->employee_id,
            'machine_sn' => 'TEST_SN_1',
            'scan_time' => '2026-03-10 13:00:00',
            'status' => '3',
        ]);
        AttendanceLog::create([
            'user_id' => $this->staff->employee_id,
            'machine_sn' => 'TEST_SN_1',
            'scan_time' => '2026-03-10 15:05:00',
            'status' => '1',
        ]);

        $service = app(\App\Services\AttendanceProcessingService::class);
        $result = $service->processDay($this->staff, $testDate);

        $this->assertTrue($result['present']);
        $this->assertFalse($result['is_anomaly']);
        $this->assertEquals(0, $result['late_minutes']);
        $this->assertNotNull($result['check_locks']['check_in']);
        $this->assertNotNull($result['check_locks']['check_out']);
        $this->assertSame('15:05:00', $result['check_locks']['check_out']['scan_time']->format('H:i:s'));
    }

    /**
     * 6. Implementasi Role & Hak Akses Admin
     */
    public function test_admin_role_restrictions_across_modules(): void
    {
        // 6.1 Rekap Bulanan: Admin tidak bisa melihat sesama Admin dan Mandor
        AttendanceCompilation::create(['date' => '2026-11-01', 'status' => 'fix']);

        $this->actingAs($this->admin);
        $recapResponse = $this->get(route('attendance.monthly-recap', ['year' => 2026, 'month' => 11]));
        $recapResponse->assertOk();
        $recapResponse->assertSee('Staff Biasa');
        $recapResponse->assertDontSee('Mandor Hebat'); // Mandor tidak muncul di Rekap Bulanan Admin

        // 6.2 Master Bonus Libur ditutup total (403 Forbidden) untuk Admin
        $this->get(route('bonus-libur.index'))->assertForbidden();
        $this->put(route('bonus-libur.update'), [
            'batas_gaji' => 2000000,
            'bonus_satu_hari' => 50000,
            'bonus_setengah_hari' => 25000,
            'pengali_bonus' => 1.5,
        ])->assertForbidden();

        // 6.3 Master Karyawan: Admin tidak melihat input nominal uang jaga malam
        $createEmpResponse = $this->get(route('employees.create'));
        $createEmpResponse->assertOk();
        $createEmpResponse->assertDontSee('Uang Jaga Malam (Rp / bulan)');

        $editEmpResponse = $this->get(route('employees.edit', $this->staff->id));
        $editEmpResponse->assertOk();
        $editEmpResponse->assertDontSee('Uang Jaga Malam (Rp / bulan)');

        // Admin mencoba submit uang_jaga_malam diabaikan
        $this->put(route('employees.update', $this->staff->id), [
            'name' => 'Staff Biasa Updated',
            'position' => 'Gudang',
            'status' => 'active',
            'uang_jaga_malam' => 999999,
        ]);

        $this->staff->refresh();
        $this->assertEquals(250000.0, (float) $this->staff->uang_jaga_malam); // Tidak berubah menjadi 999999
    }
}
