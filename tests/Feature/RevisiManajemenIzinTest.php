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
     *     gaji harian dipotong sesuai MENIT izin potong gaji (popup Absen Daily).
     */
    public function test_late_with_permit_has_no_auto_fine_and_uses_permit_minutes(): void
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
            'is_anomaly' => true,
            'is_fixed' => true,
        ]);

        // Kelola Izin dari Absen Daily: potong gaji 120 menit (2 jam)
        $this->post(route('permits.store'), [
            'source' => 'attendance_daily',
            'employee_id' => $this->employee->id,
            'permit_date' => '2026-08-10',
            'permits' => [
                [
                    'category' => 'terlambat',
                    'type' => Permit::TYPE_SALARY_DEDUCTION,
                    'permit_minutes' => 120,
                    'start_time' => '08:00',
                    'end_time' => '08:15',
                    'reason' => 'Telat macet',
                ],
            ],
        ]);

        $record = DailyAttendance::where('employee_id', $this->employee->id)
            ->where('date', '2026-08-10')
            ->first();
        $this->assertSame('izin', $record->keterangan);
        $this->assertTrue($record->isLateIgnored());
        // Gaji 3.000.000 / 30 = 100.000/hari; 120 menit dari 480 menit kerja -> Rp 25.000
        $this->assertEqualsWithDelta(25000.0, (float) $record->nominal_izin, 0.01);

        $payroll = app(PayrollService::class)->generate($this->employee, 2026, 8);

        // Tidak ada potongan terlambat otomatis; potongan izin dari menit di atas
        $this->assertSame(0.0, (float) $payroll->late_deduction);
        $this->assertSame(0.0, (float) $payroll->total_potongan_terlambat);
        $this->assertEqualsWithDelta(25000.0, (float) $payroll->izin_potong_gaji, 0.01);
    }

    /**
     * 3. Menu Manajemen Izin dihapus total: route, halaman, dan item sidebar.
     */
    public function test_menu_manajemen_izin_is_removed(): void
    {
        // Route & namanya sudah dihapus
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('permits.management'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('permits.management.store'));

        // URI lama jatuh ke rute DELETE permits/{permit} -> 405, halaman tidak ada
        $this->get('/permits/management')->assertStatus(405);
        $this->post('/permits/management', [
            'employee_id' => $this->employee->id,
            'year' => 2026,
            'month' => 10,
            'amount' => 120000,
        ])->assertStatus(405);

        $this->assertSame(0, PermitDeduction::count());

        $sidebar = $this->get(route('dashboard'));
        $sidebar->assertOk();
        $sidebar->assertDontSee('Manajemen Izin');
    }

    /**
     * 4. Rekap Bulanan menarik nominal potongan izin dari Absen Daily
     *    (data lama di tabel permit_deductions tidak dipakai lagi).
     */
    public function test_monthly_recap_shows_potongan_izin_from_daily(): void
    {
        DailyAttendance::create([
            'employee_id' => $this->employee->id,
            'date' => '2026-10-05',
            'keterangan' => 'izin',
            'tipe_nominal_izin' => 'potong_gaji',
            'nominal_izin' => 80000,
            'is_fixed' => true,
        ]);

        PermitDeduction::create([
            'employee_id' => $this->employee->id,
            'period_year' => 2026,
            'period_month' => 10,
            'amount' => 999999,
        ]);

        $response = $this->get(route('attendance.monthly-recap', ['year' => 2026, 'month' => 10]));
        $response->assertOk();
        $response->assertSee('Karyawan Revisi');
        $response->assertSee('-Rp 80.000');
        $response->assertDontSee('999.999');
    }

    /**
     * 5. Payroll memakai nominal izin harian; data Manajemen Izin lama diabaikan.
     */
    public function test_payroll_uses_daily_izin_nominal_only(): void
    {
        DailyAttendance::create([
            'employee_id' => $this->employee->id,
            'date' => '2026-08-10',
            'keterangan' => 'izin',
            'tipe_nominal_izin' => 'potong_gaji',
            'nominal_izin' => 1000,
            'is_fixed' => true,
        ]);

        PermitDeduction::create([
            'employee_id' => $this->employee->id,
            'period_year' => 2026,
            'period_month' => 8,
            'amount' => 80000,
        ]);

        $payroll = app(PayrollService::class)->generate($this->employee, 2026, 8);

        $this->assertSame(1000.0, (float) $payroll->izin_potong_gaji);
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
        $response->assertDontSee('Manajemen Izin');
        $response->assertSee('Pinjaman / Kasbon');
        $response->assertSee('Payroll &amp; Slip Gaji', false);
    }
}
