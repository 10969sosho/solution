<?php

namespace Tests\Unit;

use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\SeasonalSchedule;
use App\Models\WorkSetting;
use App\Services\AttendanceProcessingService;
use Carbon\Carbon;
use Tests\TestCase;

class AttendanceProcessingServiceTest extends TestCase
{
    private AttendanceProcessingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AttendanceProcessingService();

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

    private function makeEmployee(string $id = '1001'): Employee
    {
        return Employee::create([
            'employee_id' => $id,
            'name' => 'Test Karyawan',
            'position' => 'Staff',
            'status' => 'active',
        ]);
    }

    private function punch(Employee $employee, string $datetime, string $status = '0'): AttendanceLog
    {
        return AttendanceLog::create([
            'machine_sn' => 'SN001',
            'user_id' => $employee->employee_id,
            'scan_time' => Carbon::parse($datetime),
            'status' => $status,
        ]);
    }

    public function test_normal_day_with_four_check_locks(): void
    {
        $employee = $this->makeEmployee();
        $this->punch($employee, '2026-08-10 07:55:00');
        $this->punch($employee, '2026-08-10 12:00:00');
        $this->punch($employee, '2026-08-10 13:00:00');
        $this->punch($employee, '2026-08-10 17:05:00');

        $result = $this->service->processDay($employee, Carbon::parse('2026-08-10'));

        $this->assertTrue($result['present']);
        $this->assertNotNull($result['check_locks']['check_in']);
        $this->assertNotNull($result['check_locks']['break_out']);
        $this->assertNotNull($result['check_locks']['break_in']);
        $this->assertNotNull($result['check_locks']['check_out']);
        $this->assertSame(0, $result['late_minutes']);
        $this->assertSame(0, $result['early_leave_minutes']);
        // 07:55-12:00 = 245, 13:00-17:05 = 245 -> 490 menit
        $this->assertSame(490, $result['total_work_minutes']);
    }

    public function test_late_check_in_is_detected(): void
    {
        $employee = $this->makeEmployee();
        $this->punch($employee, '2026-08-10 08:30:00');
        $this->punch($employee, '2026-08-10 12:00:00');
        $this->punch($employee, '2026-08-10 13:00:00');
        $this->punch($employee, '2026-08-10 17:00:00');

        $result = $this->service->processDay($employee, Carbon::parse('2026-08-10'));

        // Batas toleransi 08:15 -> telat 15 menit
        $this->assertSame(15, $result['late_minutes']);
    }

    public function test_early_leave_is_detected(): void
    {
        $employee = $this->makeEmployee();
        $this->punch($employee, '2026-08-10 08:00:00');
        $this->punch($employee, '2026-08-10 12:00:00');
        $this->punch($employee, '2026-08-10 13:00:00');
        $this->punch($employee, '2026-08-10 16:30:00');

        $result = $this->service->processDay($employee, Carbon::parse('2026-08-10'));

        $this->assertSame(30, $result['early_leave_minutes']);
    }

    public function test_break_in_before_1245_is_not_counted_anti_fraud(): void
    {
        $employee = $this->makeEmployee();
        $this->punch($employee, '2026-08-10 08:00:00');
        $this->punch($employee, '2026-08-10 12:00:00');
        $this->punch($employee, '2026-08-10 12:30:00'); // terlalu awal -> tidak sah
        $this->punch($employee, '2026-08-10 17:00:00');

        $result = $this->service->processDay($employee, Carbon::parse('2026-08-10'));

        // 12:30 tidak boleh jadi break_in
        $this->assertNotSame('12:30', $result['check_locks']['break_in']['scan_time']->format('H:i'));
        $this->assertNotEmpty($result['ignored_scans']);
        // Work = (08:00-12:00)=240 + (17:00-17:00 tanpapunch)... break_in null sehingga tidak ada segmen 2
        $this->assertSame(240, $result['total_work_minutes']);
    }

    public function test_employee_specific_schedule_is_used(): void
    {
        $employee = $this->makeEmployee();
        EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'name' => 'Penjaga Gerbang',
            'check_in_time' => '08:00:00',
            'break_out_time' => '10:45:00',
            'break_in_time' => '11:45:00',
            'check_out_time' => '17:00:00',
            'is_active' => true,
        ]);

        $this->punch($employee, '2026-08-10 07:58:00');
        $this->punch($employee, '2026-08-10 10:45:00');
        $this->punch($employee, '2026-08-10 11:45:00');
        $this->punch($employee, '2026-08-10 17:02:00');

        $result = $this->service->processDay($employee, Carbon::parse('2026-08-10'));

        $this->assertSame('employee_schedule', $result['schedule']['source']);
        $this->assertSame(0, $result['late_minutes']);
        $this->assertSame(0, $result['early_leave_minutes']);
        // 07:58-10:45 = 167, 11:45-17:02 = 317 -> 484
        $this->assertSame(484, $result['total_work_minutes']);
    }

    public function test_seasonal_schedule_moves_check_out_earlier(): void
    {
        $employee = $this->makeEmployee();
        SeasonalSchedule::create([
            'name' => 'Puasa',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'check_in_delta_minutes' => 0,
            'check_out_delta_minutes' => -30,
            'is_active' => true,
        ]);

        $this->punch($employee, '2026-08-10 08:00:00');
        $this->punch($employee, '2026-08-10 12:00:00');
        $this->punch($employee, '2026-08-10 13:00:00');
        $this->punch($employee, '2026-08-10 16:30:00'); // tepat waktu untuk pulang 16:30

        $result = $this->service->processDay($employee, Carbon::parse('2026-08-10'));

        $this->assertSame('Puasa', $result['schedule']['seasonal'] ?? null);
        $this->assertSame('16:30:00', $result['schedule']['check_out_time']);
        $this->assertSame(0, $result['early_leave_minutes']);
    }

    public function test_absent_day_returns_not_present(): void
    {
        $employee = $this->makeEmployee();

        $result = $this->service->processDay($employee, Carbon::parse('2026-08-10'));

        $this->assertFalse($result['present']);
        $this->assertNull($result['check_locks']['check_in']);
    }
}