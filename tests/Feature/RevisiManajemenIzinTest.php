<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\DailyAttendance;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Golongan;
use App\Models\Permit;
use App\Models\PermitDeduction;
use App\Models\PotonganTerlambat;
use App\Models\SeasonalSchedule;
use App\Models\WorkSetting;
use App\Services\AttendanceProcessingService;
use App\Services\PayrollService;
use Carbon\Carbon;
use Tests\TestCase;

class RevisiManajemenIzinTest extends TestCase
{
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsSuperAdmin();

        $this->employee = Employee::create([
            'employee_id' => 'RMI001',
            'name' => 'Karyawan Revisi',
            'position' => 'Kandang',
            'status' => 'active',
            'salary' => 3000000,
        ]);
    }

    private function makeGolonganWithWorkSetting(): Golongan
    {
        $golongan = Golongan::create(['name' => 'Gudang', 'type' => 'gudang_kandang']);

        WorkSetting::create([
            'name' => 'Golongan Gudang',
            'golongan_id' => $golongan->id,
            'check_in_time' => '08:00:00',
            'check_out_time' => '17:00:00',
            'break_out_time' => '12:00:00',
            'break_in_time' => '13:00:00',
            'late_tolerance_minutes' => 15,
            'is_active' => true,
        ]);

        return $golongan;
    }

    /**
     * 1. Hirarki jam kerja: Musiman > Khusus (EmployeeSchedule) > Setting per Golongan.
     */
    public function test_work_hour_hierarchy_seasonal_beats_employee_schedule_beats_golongan(): void
    {
        $golongan = $this->makeGolonganWithWorkSetting();
        $this->employee->update(['golongan_id' => $golongan->id]);

        $service = app(AttendanceProcessingService::class);
        $date = Carbon::parse('2026-08-10');

        // (c) Setting per golongan
        $schedule = $service->resolveSchedule($this->employee, $date);
        $this->assertSame('08:00:00', $schedule['check_in_time']);
        $this->assertSame('global', $schedule['source']);

        // (b) Jam kerja khusus mengalahkan setting golongan
        EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'name' => 'Khusus Gerbang',
            'check_in_time' => '07:00:00',
            'break_out_time' => '11:00:00',
            'break_in_time' => '12:00:00',
            'check_out_time' => '16:00:00',
            'is_active' => true,
        ]);

        $schedule = $service->resolveSchedule($this->employee, $date);
        $this->assertSame('07:00:00', $schedule['check_in_time']);
        $this->assertSame('16:00:00', $schedule['check_out_time']);
        $this->assertSame('employee_schedule', $schedule['source']);

        // (a) Jam kerja musiman mengalahkan jadwal khusus & golongan
        SeasonalSchedule::create([
            'name' => 'Ramadan',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'check_in_delta_minutes' => 0,
            'check_out_delta_minutes' => -60,
            'force_check_in_time' => '06:30:00',
            'is_active' => true,
        ]);

        $schedule = $service->resolveSchedule($this->employee, $date);
        $this->assertSame('06:30:00', $schedule['check_in_time']);
        $this->assertSame('15:00:00', $schedule['check_out_time']); // 16:00 - 60 menit
        $this->assertSame('Ramadan', $schedule['seasonal']);

        // Keterlambatan dihitung dari hirarki: masuk 08:00 dianggap telat 90 menit
        // (toleransi 15 => batas 06:45).
        AttendanceLog::create([
            'machine_sn' => 'SN001',
            'user_id' => $this->employee->employee_id,
            'scan_time' => Carbon::parse('2026-08-10 08:00:00'),
            'status' => '0',
        ]);
        AttendanceLog::create([
            'machine_sn' => 'SN001',
            'user_id' => $this->employee->employee_id,
            'scan_time' => Carbon::parse('2026-08-10 15:00:00'),
            'status' => '0',
        ]);

        $result = $service->processDay($this->employee, $date);
        $this->assertSame(75, $result['late_minutes']); // 06:45 -> 08:00
    }

    /**
     * 2a. Terlambat TANPA izin → potongan otomatis sesuai master PotonganTerlambat.
     */
    public function test_late_without_permit_gets_auto_potongan_terlambat(): void
    {
        $golongan = $this->makeGolonganWithWorkSetting();
        $this->employee->update(['golongan_id' => $golongan->id]);

        PotonganTerlambat::create([
            'golongan_id' => $golongan->id,
            'type' => 'masuk_kerja',
            'min_minutes' => 1,
            'max_minutes' => null,
            'amount' => 50000,
        ]);

        DailyAttendance::create([
            'employee_id' => $this->employee->id,
            'date' => '2026-08-10',
            'check_in' => '08:30:00',
            'check_out' => '17:00:00',
            'late_check_in_minutes' => 15,
            'keterangan' => 'hadir',
            'is_fixed' => true,
        ]);

        $payroll = app(PayrollService::class)->generate($this->employee, 2026, 8);

        $this->assertSame(50000.0, (float) $payroll->late_deduction);
        $this->assertSame(50000.0, (float) $payroll->total_potongan_terlambat);
        $this->assertSame(0.0, (float) $payroll->izin_potong_gaji);
    }

    /**
     * 2b. Terlambat/anomali TETAPI ADA IZIN → TANPA potongan terlambat otomatis;
     *     potongan ditentukan manual via Manajemen Izin (PermitDeduction).
     */
    public function test_late_with_permit_has_no_auto_fine_and_uses_manajemen_izin(): void
    {
        $golongan = $this->makeGolonganWithWorkSetting();
        $this->employee->update(['golongan_id' => $golongan->id]);

        PotonganTerlambat::create([
            'golongan_id' => $golongan->id,
            'type' => 'masuk_kerja',
            'min_minutes' => 1,
            'max_minutes' => null,
            'amount' => 50000,
        ]);

        // Izin terlambat (potong gaji, tanpa nominal di daily)
        Permit::create([
            'employee_id' => $this->employee->id,
            'category' => 'terlambat',
            'permit_date' => '2026-08-10',
            'type' => Permit::TYPE_SALARY_DEDUCTION,
            'start_time' => '08:00',
            'end_time' => '08:15',
            'duration_minutes' => 15,
            'nominal' => 0,
            'reason' => 'Telat macet',
            'status' => 'approved',
        ]);

        DailyAttendance::create([
            'employee_id' => $this->employee->id,
            'date' => '2026-08-10',
            'check_in' => '08:30:00',
            'check_out' => '17:00:00',
            'late_check_in_minutes' => 15,
            'is_anomaly' => true,
            'keterangan' => 'izin',
            'tipe_nominal_izin' => 'potong_gaji',
            'nominal_izin' => 0,
            'is_fixed' => true,
        ]);

        // Nominal potongan izin diinput lewat Manajemen Izin
        PermitDeduction::create([
            'employee_id' => $this->employee->id,
            'period_year' => 2026,
            'period_month' => 8,
            'amount' => 75000,
        ]);

        $payroll = app(PayrollService::class)->generate($this->employee, 2026, 8);

        // Tidak ada potongan terlambat otomatis; potongan izin 75.000 dari Manajemen Izin
        $this->assertSame(0.0, (float) $payroll->late_deduction);
        $this->assertSame(0.0, (float) $payroll->total_potongan_terlambat);
        $this->assertSame(75000.0, (float) $payroll->izin_potong_gaji);
    }

    /**
     * 3. Menu Manajemen Izin: halaman, simpan nominal, dan rekap hitungan izin.
     */
    public function test_manajemen_izin_page_and_store_nominal(): void
    {
        Permit::create([
            'employee_id' => $this->employee->id,
            'category' => 'terlambat',
            'permit_date' => '2026-10-05',
            'type' => Permit::TYPE_SALARY_DEDUCTION,
            'start_time' => '08:00',
            'end_time' => '08:30',
            'duration_minutes' => 30,
            'nominal' => 0,
            'reason' => 'Potong 1',
            'status' => 'approved',
        ]);
        Permit::create([
            'employee_id' => $this->employee->id,
            'category' => 'terlambat',
            'permit_date' => '2026-10-06',
            'type' => Permit::TYPE_SALARY_DEDUCTION,
            'start_time' => '08:00',
            'end_time' => '08:30',
            'duration_minutes' => 30,
            'nominal' => 0,
            'reason' => 'Potong 2',
            'status' => 'approved',
        ]);
        Permit::create([
            'employee_id' => $this->employee->id,
            'category' => 'terlambat',
            'permit_date' => '2026-10-07',
            'type' => Permit::TYPE_NO_DEDUCTION,
            'start_time' => '08:00',
            'end_time' => '08:10',
            'duration_minutes' => 10,
            'nominal' => 0,
            'reason' => 'Tidak potong 1',
            'status' => 'approved',
        ]);

        $page = $this->get(route('permits.management', ['year' => 2026, 'month' => 10]));
        $page->assertOk();
        $page->assertSee('Manajemen Izin');
        $page->assertSee('Karyawan Revisi');
        $page->assertSee('2 hari'); // izin potong gaji
        $page->assertSee('1 hari'); // izin tidak potong

        $this->post(route('permits.management.store'), [
            'employee_id' => $this->employee->id,
            'year' => 2026,
            'month' => 10,
            'amount' => 120000,
        ])->assertRedirect(route('permits.management', ['year' => 2026, 'month' => 10]));

        $this->assertDatabaseHas('permit_deductions', [
            'employee_id' => $this->employee->id,
            'period_year' => 2026,
            'period_month' => 10,
            'amount' => 120000,
        ]);

        // Upsert: simpan lagi mengubah nominal yang sama
        $this->post(route('permits.management.store'), [
            'employee_id' => $this->employee->id,
            'year' => 2026,
            'month' => 10,
            'amount' => 90000,
        ]);

        $this->assertSame(1, PermitDeduction::where('employee_id', $this->employee->id)->count());
        $this->assertSame(90000.0, (float) PermitDeduction::amountFor($this->employee->id, 2026, 10));
    }

    /**
     * 4. Rekap Bulanan menarik nominal potongan izin dari Manajemen Izin.
     */
    public function test_monthly_recap_shows_potongan_izin_from_manajemen(): void
    {
        DailyAttendance::create([
            'employee_id' => $this->employee->id,
            'date' => '2026-10-05',
            'keterangan' => 'izin',
            'tipe_nominal_izin' => 'potong_gaji',
            'nominal_izin' => 0,
            'is_fixed' => true,
        ]);

        PermitDeduction::create([
            'employee_id' => $this->employee->id,
            'period_year' => 2026,
            'period_month' => 10,
            'amount' => 80000,
        ]);

        $response = $this->get(route('attendance.monthly-recap', ['year' => 2026, 'month' => 10]));
        $response->assertOk();
        $response->assertSee('Karyawan Revisi');
        $response->assertSee('-Rp 80.000');
    }

    /**
     * 5. Payroll memakai nominal Manajemen Izin bila ada (fallback nominal harian bila tidak).
     */
    public function test_payroll_prefers_manajemen_izin_over_daily_nominal(): void
    {
        DailyAttendance::create([
            'employee_id' => $this->employee->id,
            'date' => '2026-08-10',
            'keterangan' => 'izin',
            'tipe_nominal_izin' => 'potong_gaji',
            'nominal_izin' => 1000,
            'is_fixed' => true,
        ]);

        // Belum ada Manajemen Izin -> fallback nominal harian
        $payroll = app(PayrollService::class)->generate($this->employee, 2026, 8);
        $this->assertSame(1000.0, (float) $payroll->izin_potong_gaji);

        // Setelah diinput di Manajemen Izin -> nominal Manajemen Izin menang
        PermitDeduction::create([
            'employee_id' => $this->employee->id,
            'period_year' => 2026,
            'period_month' => 8,
            'amount' => 80000,
        ]);

        $payroll = app(PayrollService::class)->generate($this->employee, 2026, 8);
        $this->assertSame(80000.0, (float) $payroll->izin_potong_gaji);
    }

    /**
     * 6. List Kompilasi Jaga Malam: halaman tampil & merangkum kehadiran petugas.
     */
    public function test_night_guard_compilation_page_renders(): void
    {
        $this->employee->update(['is_night_guard' => true, 'uang_jaga_malam' => 350000]);

        AttendanceLog::create([
            'machine_sn' => 'SN001',
            'user_id' => $this->employee->employee_id,
            'scan_time' => Carbon::parse('2026-10-05 17:05:00'),
            'status' => '0',
        ]);
        AttendanceLog::create([
            'machine_sn' => 'SN001',
            'user_id' => $this->employee->employee_id,
            'scan_time' => Carbon::parse('2026-10-06 06:00:00'),
            'status' => '0',
        ]);

        $response = $this->get(route('attendance.night-guard-compilation', ['year' => 2026, 'month' => 10]));

        $response->assertOk();
        $response->assertSee('List Kompilasi Jaga Malam');
        $response->assertSee('Karyawan Revisi');
        $response->assertSee('Buka Jaga Malam');
        // Tanggal 5 Oktober hadir 1/1
        $response->assertSee('1 / 1');
    }

    /**
     * 7. Struktur menu navigasi sidebar sesuai spesifikasi revisi.
     */
    public function test_sidebar_menu_structure_updated(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Absensi dan Kompilasi');
        $response->assertSee('List Kompilasi Jaga Malam');
        $response->assertSee('Jam Kerja</span>', false);
        $response->assertSee('Jam Kerja Khusus');
        $response->assertSee('Jam Kerja Musiman');
        $response->assertSee('Setting Jam Kerja');
        $response->assertSee('Manajemen Izin');
        $response->assertSee('Pinjaman / Kasbon');
        $response->assertSee('Payroll &amp; Slip Gaji', false);
    }
}
