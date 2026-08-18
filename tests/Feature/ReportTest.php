<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\WorkSetting;
use Carbon\Carbon;
use Tests\TestCase;

class ReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        WorkSetting::create([
            'name' => 'Default',
            'check_in_time' => '08:00:00',
            'check_out_time' => '17:00:00',
            'break_out_time' => '12:00:00',
            'break_in_time' => '13:00:00',
            'late_tolerance_minutes' => 15,
            'is_active' => true,
        ]);
    }

    public function test_monthly_report_page_loads(): void
    {
        $employee = Employee::create([
            'employee_id' => '1001',
            'name' => 'Budi',
            'position' => 'Gudang',
            'location' => 'Surabaya',
            'status' => 'active',
        ]);

        AttendanceLog::create([
            'machine_sn' => 'SN001',
            'user_id' => '1001',
            'scan_time' => Carbon::parse('2026-08-10 08:00:00'),
            'status' => '0',
        ]);

        $response = $this->get(route('reports.monthly', ['year' => 2026, 'month' => 8, 'employee_id' => '1001']));

        $response->assertStatus(200);
        $response->assertSee('Rincian Absensi');
        $response->assertSee('Budi');
    }

    public function test_summary_report_page_loads_and_filters_by_location(): void
    {
        Employee::create([
            'employee_id' => '1001',
            'name' => 'Budi',
            'position' => 'Gudang',
            'location' => 'Surabaya',
            'status' => 'active',
        ]);

        Employee::create([
            'employee_id' => '1002',
            'name' => 'Andi',
            'position' => 'Kandang',
            'location' => 'Gresik',
            'status' => 'active',
        ]);

        $response = $this->get(route('reports.summary', ['year' => 2026, 'month' => 8, 'location' => 'Surabaya']));

        $response->assertStatus(200);
        $response->assertSee('Budi');
        $response->assertDontSee('Andi');
    }
}