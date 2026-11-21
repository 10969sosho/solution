<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\DailyAttendance;
use App\Models\Device;
use App\Models\Employee;
use App\Models\WorkSetting;
use Tests\TestCase;

class QAAbsensiDailyAdminVerificationTest extends TestCase
{
    private Employee $anakGudang;
    private Employee $mandor;
    private Employee $gudangOperasional;
    private Device $device;
    private string $testDate = '2026-11-20';

    protected function setUp(): void
    {
        parent::setUp();

        WorkSetting::create([
            'effective_from' => '2026-01-01',
            'check_in_time' => '08:00:00',
            'break_out_time' => '12:00:00',
            'break_in_time' => '13:00:00',
            'check_out_time' => '17:00:00',
            'late_tolerance_minutes' => 15,
        ]);

        $this->device = Device::create([
            'name' => 'Mesin Fingerprint Gudang',
            'serial_number' => 'SN_GUDANG_001',
            'ip_address' => '192.168.1.100',
            'port' => 4370,
            'is_active' => true,
        ]);

        // Employee 1: Non-operasional 'Anak Gudang'
        $this->anakGudang = Employee::create([
            'employee_id' => 'AG001',
            'name' => 'Budi Santoso Anak Gudang',
            'position' => 'Anak Gudang',
            'department' => 'Gudang Logistik',
            'status' => 'active',
            'salary' => 3500000,
        ]);

        // Employee 2: Non-operasional 'Mandor'
        $this->mandor = Employee::create([
            'employee_id' => 'MND001',
            'name' => 'Pak Joko Mandor',
            'position' => 'Mandor',
            'department' => 'Operasional Lapangan',
            'status' => 'active',
            'salary' => 5000000,
        ]);

        // Employee 3: Operasional 'Gudang' (standar config hrms)
        $this->gudangOperasional = Employee::create([
            'employee_id' => 'GDG001',
            'name' => 'Siti Gudang Standard',
            'position' => 'Gudang',
            'department' => 'Gudang Utama',
            'status' => 'active',
            'salary' => 3800000,
        ]);
    }

    /**
     * Test 1: Verifikasi log mesin masuk dan auto-sync di Absen Daily untuk admin
     */
    public function test_admin_can_see_non_operational_employee_and_sync_machine_attendance_in_daily(): void
    {
        // 1. Kirim log absensi dari mesin fingerprint via ADMS ATTLOG untuk Budi Anak Gudang & Siti Gudang
        $cdataPayload = "AG001\t2026-11-20 07:50:00\t0\t1\t0\n"
                      . "AG001\t2026-11-20 12:02:00\t2\t1\t0\n"
                      . "AG001\t2026-11-20 12:58:00\t3\t1\t0\n"
                      . "AG001\t2026-11-20 17:05:00\t1\t1\t0\n"
                      . "GDG001\t2026-11-20 07:55:00\t0\t1\t0\n"
                      . "GDG001\t2026-11-20 17:01:00\t1\t1\t0\n";

        $admsResponse = $this->call(
            'POST',
            '/iclock/cdata?SN=SN_GUDANG_001&table=ATTLOG',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain'],
            $cdataPayload
        );
        $admsResponse->assertOk();
        $this->assertStringContainsString('OK', $admsResponse->getContent());

        // Verifikasi log tersimpan di database attendance_logs
        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => 'AG001',
            'machine_sn' => 'SN_GUDANG_001',
            'scan_time' => '2026-11-20 07:50:00',
        ]);

        // 2. Login sebagai Admin Operasional (admin@adms.test)
        $this->loginAsAdminOperasional();

        // 3. Buka halaman Absen Daily (/attendance/daily?date=2026-11-20)
        $response = $this->get(route('attendance.daily', ['date' => $this->testDate]));

        $response->assertOk();
        // Cek bahwa karyawan non-operasional (Budi Anak Gudang & Mandor) terlihat
        $response->assertSee('Budi Santoso Anak Gudang');
        $response->assertSee('Pak Joko Mandor');
        $response->assertSee('Siti Gudang Standard');

        // Cek jam masuk dan pulang hasil auto-sync tampil di UI Daily
        $response->assertSee('07:50');
        $response->assertSee('17:05');
        $response->assertSee('Mesin Fingerprint Gudang');

        // 4. Verifikasi bahwa DailyAttendance record otomatis terbentuk di database
        $dailyAnakGudang = DailyAttendance::where('employee_id', $this->anakGudang->id)
            ->where('date', $this->testDate)
            ->first();

        $this->assertNotNull($dailyAnakGudang, 'DailyAttendance untuk Anak Gudang harus otomatis terbuat!');
        $this->assertSame('07:50:00', $dailyAnakGudang->check_in);
        $this->assertSame('12:02:00', $dailyAnakGudang->break_out);
        $this->assertSame('12:58:00', $dailyAnakGudang->break_in);
        $this->assertSame('17:05:00', $dailyAnakGudang->check_out);
        $this->assertSame('hadir', $dailyAnakGudang->keterangan);
        $this->assertSame(0, $dailyAnakGudang->late_check_in_minutes);
    }

    /**
     * Test 2: Verifikasi akses seluruh modul Report untuk Admin (bebas dari 403)
     */
    public function test_admin_has_full_access_to_all_report_modules(): void
    {
        $this->loginAsAdminOperasional();

        // 1. Report Monthly
        $resMonthly = $this->get(route('reports.monthly', [
            'year' => 2026,
            'month' => 11,
            'employee_id' => $this->anakGudang->employee_id,
        ]));
        $resMonthly->assertOk();
        $resMonthly->assertSee('Budi Santoso Anak Gudang');

        // 2. Report Summary
        $resSummary = $this->get(route('reports.summary', [
            'year' => 2026,
            'month' => 11,
        ]));
        $resSummary->assertOk();
        $resSummary->assertSee('Budi Santoso Anak Gudang');
        $resSummary->assertSee('Pak Joko Mandor');

        // 3. Report Attendance Detail
        $resDetail = $this->get(route('reports.attendanceDetail', [
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-20',
            'employee_id' => $this->anakGudang->id,
        ]));
        $resDetail->assertOk();
        $resDetail->assertSee('Budi Santoso Anak Gudang');

        // 4. Report Attendance Summary
        $resAttSummary = $this->get(route('reports.attendanceSummary', [
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-20',
        ]));
        $resAttSummary->assertOk();
        $resAttSummary->assertSee('Budi Santoso Anak Gudang');
        $resAttSummary->assertSee('Pak Joko Mandor');

        // 5. Report Yearly
        $resYearly = $this->get(route('reports.yearly', [
            'year' => 2026,
        ]));
        $resYearly->assertOk();
        $resYearly->assertSee('Budi Santoso Anak Gudang');
        $resYearly->assertSee('Pak Joko Mandor');
    }

    /**
     * Test 3: Verifikasi filter mesin dan filter anomali di Absen Daily untuk admin
     */
    public function test_admin_daily_filters_work_properly(): void
    {
        $this->loginAsAdminOperasional();

        // Buat log hanya untuk Anak Gudang di device SN_GUDANG_001
        AttendanceLog::create([
            'user_id' => 'AG001',
            'machine_sn' => 'SN_GUDANG_001',
            'scan_time' => '2026-11-20 08:30:00', // Terlambat (anomali)
            'scan_type' => 0,
            'status' => '0',
        ]);

        // Filter: machine = SN_GUDANG_001
        $resMachine = $this->get(route('attendance.daily', [
            'date' => $this->testDate,
            'machine' => 'SN_GUDANG_001',
        ]));
        $resMachine->assertOk();
        $resMachine->assertSee('Budi Santoso Anak Gudang');
        // Karyawan lain yang tidak scan di mesin ini tidak boleh muncul saat filter mesin spesifik
        $resMachine->assertDontSee('Pak Joko Mandor');

        // Filter: filter = anomaly
        $resAnomaly = $this->get(route('attendance.daily', [
            'date' => $this->testDate,
            'filter' => 'anomaly',
        ]));
        $resAnomaly->assertOk();
        $resAnomaly->assertSee('Budi Santoso Anak Gudang');
    }

    /**
     * Test 4: Verifikasi perlindungan data fixed (is_fixed = true) agar tidak tertimpa auto-sync
     */
    public function test_fixed_daily_record_not_overwritten_by_auto_sync(): void
    {
        $this->loginAsAdminOperasional();

        // Siapkan record manual yang sudah di-FIX
        $fixedRecord = DailyAttendance::create([
            'employee_id' => $this->anakGudang->id,
            'date' => $this->testDate,
            'check_in' => '07:30:00',
            'check_out' => '16:30:00',
            'keterangan' => 'hadir',
            'is_fixed' => true,
        ]);

        // Ada log baru di mesin
        AttendanceLog::create([
            'user_id' => 'AG001',
            'machine_sn' => 'SN_GUDANG_001',
            'scan_time' => '2026-11-20 09:00:00',
            'scan_type' => 0,
            'status' => '0',
        ]);

        // Admin buka daily page
        $res = $this->get(route('attendance.daily', ['date' => $this->testDate]));
        $res->assertOk();

        // Record tetap 07:30 dan tidak ditimpa 09:00 karena statusnya is_fixed = true
        $fixedRecord->refresh();
        $this->assertSame('07:30:00', $fixedRecord->check_in);
    }

    /**
     * Test 5: Verifikasi Super Admin tetap memiliki akses 100% (No Regression)
     */
    public function test_super_admin_full_access_no_regression(): void
    {
        $this->loginAsSuperAdmin();

        $resDaily = $this->get(route('attendance.daily', ['date' => $this->testDate]));
        $resDaily->assertOk();
        $resDaily->assertSee('Budi Santoso Anak Gudang');
        $resDaily->assertSee('Pak Joko Mandor');

        $resSummary = $this->get(route('reports.summary', ['year' => 2026, 'month' => 11]));
        $resSummary->assertOk();
        $resSummary->assertSee('Budi Santoso Anak Gudang');
    }
}
