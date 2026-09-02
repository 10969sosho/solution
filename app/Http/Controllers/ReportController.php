<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\WorkSetting;
use App\Models\Permit;
use App\Services\AttendanceProcessingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private AttendanceProcessingService $attendanceService)
    {
    }

    public function monthly(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $employeeId = $request->input('employee_id');
        $location = $request->input('location');
        $position = $request->input('position');

        $employees = Employee::query()
            ->where('status', 'active')
            ->when(! auth()->user()->isSuperAdmin(), fn ($q) => $q->whereIn('position', config('hrms.operational_positions', [])))
            ->when($location, fn ($q) => $q->where('location', $location))
            ->when($position, fn ($q) => $q->where('position', $position))
            ->orderBy('name')
            ->get();

        $selectedEmployee = null;
        $report = null;

        if ($employeeId) {
            $selectedEmployee = $employees->firstWhere('employee_id', $employeeId)
                ?? Employee::where('employee_id', $employeeId)->first();
            if ($selectedEmployee && ! auth()->user()->isSuperAdmin()
                && ! in_array($selectedEmployee->position, config('hrms.operational_positions', []), true)) {
                abort(403, 'Anda tidak memiliki akses ke laporan karyawan non-operasional.');
            }
            if ($selectedEmployee) {
                $report = $this->attendanceService->processMonth($selectedEmployee, $year, $month);
            }
        }

        $workSetting = WorkSetting::getActive();

        return view('reports.monthly', compact(
            'employees',
            'selectedEmployee',
            'report',
            'year',
            'month',
            'location',
            'position',
            'workSetting'
        ));
    }

    public function summary(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $location = $request->input('location');
        $position = $request->input('position');

        $employees = Employee::query()
            ->where('status', 'active')
            ->when(! auth()->user()->isSuperAdmin(), fn ($q) => $q->whereIn('position', config('hrms.operational_positions', [])))
            ->when($location, fn ($q) => $q->where('location', $location))
            ->when($position, fn ($q) => $q->where('position', $position))
            ->orderBy('name')
            ->get();

        $workSetting = WorkSetting::getActive();

        $summary = [];

        foreach ($employees as $employee) {
            $report = $this->attendanceService->processMonth($employee, $year, $month);

            $summary[] = [
                'employee_id' => $employee->employee_id,
                'name' => $employee->name,
                'department' => $employee->department,
                'location' => $employee->location,
                'position' => $employee->position,
                'days_present' => $report['days_present'],
                'total_work_minutes' => $report['total_work_minutes'],
                'total_late_minutes' => $report['total_late_minutes'],
                'days_late' => $report['days_late'],
                'total_early_leave_minutes' => $report['total_early_leave_minutes'],
            ];
        }

        return view('reports.summary', compact('summary', 'year', 'month', 'location', 'position', 'workSetting'));
    }

    public function attendanceDetail(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $location = $request->input('location');
        $position = $request->input('position');
        $employeeId = $request->input('employee_id');

        $query = Employee::query()
            ->where('status', 'active')
            ->when(! auth()->user()->isSuperAdmin(), fn ($q) => $q->whereIn('position', config('hrms.operational_positions', [])))
            ->when($location, fn ($q) => $q->where('location', $location))
            ->when($position, fn ($q) => $q->where('position', $position))
            ->when($employeeId, fn ($q) => $q->where('id', $employeeId))
            ->orderBy('name');

        $employees = $query->get();

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $reportData = [];

        foreach ($employees as $employee) {
            $dailyDetails = [];
            $totalWorkMinutes = 0;
            $totalLateMinutes = 0;
            $totalEarlyLeaveMinutes = 0;

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $day = $this->attendanceService->processDay($employee, $date->copy());

                $permits = Permit::where('employee_id', $employee->id)
                    ->where('permit_date', $date->toDateString())
                    ->get();

                $izinNoDeduction = $permits->where('type', 'no_deduction')->sum('duration_minutes');
                $izinSalaryDeduction = $permits->where('type', 'salary_deduction')->sum('duration_minutes');
                $izinTerlambat = $permits->where('category', 'terlambat')->sum('late_minutes');
                $izinPulangAwal = $permits->where('category', 'pulang_awal')->sum('duration_minutes');

                $dailyDetails[] = [
                    'date' => $date->toDateString(),
                    'check_in' => $day['check_locks']['check_in']
                        ? $day['check_locks']['check_in']['scan_time']->format('H:i')
                        : '-',
                    'check_out' => $day['check_locks']['check_out']
                        ? $day['check_locks']['check_out']['scan_time']->format('H:i')
                        : '-',
                    'break_out' => $day['check_locks']['break_out']
                        ? $day['check_locks']['break_out']['scan_time']->format('H:i')
                        : '-',
                    'break_in' => $day['check_locks']['break_in']
                        ? $day['check_locks']['break_in']['scan_time']->format('H:i')
                        : '-',
                    'work_minutes' => $day['total_work_minutes'],
                    'late_minutes' => $day['late_minutes'],
                    'early_leave_minutes' => $day['early_leave_minutes'],
                    'izin_no_deduction' => $izinNoDeduction,
                    'izin_salary_deduction' => $izinSalaryDeduction,
                    'izin_terlambat' => $izinTerlambat,
                    'izin_pulang_awal' => $izinPulangAwal,
                ];

                $totalWorkMinutes += $day['total_work_minutes'];
                $totalLateMinutes += $day['late_minutes'];
                $totalEarlyLeaveMinutes += $day['early_leave_minutes'];
            }

            $reportData[] = [
                'employee' => $employee,
                'daily_details' => $dailyDetails,
                'total_work_minutes' => $totalWorkMinutes,
                'total_late_minutes' => $totalLateMinutes,
                'total_early_leave_minutes' => $totalEarlyLeaveMinutes,
            ];
        }

        $locations = Employee::where('status', 'active')->distinct()->pluck('location')->filter()->values();
        $positions = Employee::where('status', 'active')->distinct()->pluck('position')->filter()->values();

        return view('reports.attendance-detail', compact(
            'reportData',
            'startDate',
            'endDate',
            'location',
            'position',
            'employeeId',
            'locations',
            'positions',
            'employees'
        ));
    }

    public function attendanceSummary(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $location = $request->input('location');
        $position = $request->input('position');
        $employeeId = $request->input('employee_id');

        $query = Employee::query()
            ->where('status', 'active')
            ->when(! auth()->user()->isSuperAdmin(), fn ($q) => $q->whereIn('position', config('hrms.operational_positions', [])))
            ->when($location, fn ($q) => $q->where('location', $location))
            ->when($position, fn ($q) => $q->where('position', $position))
            ->when($employeeId, fn ($q) => $q->where('id', $employeeId))
            ->orderBy('name');

        $employees = $query->get();

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $summary = [];

        foreach ($employees as $employee) {
            $totalIzinNoDeduction = 0;
            $totalIzinSalaryDeduction = 0;
            $totalIzinTerlambat = 0;
            $totalIzinPulangAwal = 0;
            $totalLateMinutes = 0;

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $day = $this->attendanceService->processDay($employee, $date->copy());

                $permits = Permit::where('employee_id', $employee->id)
                    ->where('permit_date', $date->toDateString())
                    ->get();

                $totalIzinNoDeduction += $permits->where('type', 'no_deduction')->sum('duration_minutes');
                $totalIzinSalaryDeduction += $permits->where('type', 'salary_deduction')->sum('duration_minutes');
                $totalIzinTerlambat += $permits->where('category', 'terlambat')->sum('late_minutes');
                $totalIzinPulangAwal += $permits->where('category', 'pulang_awal')->sum('duration_minutes');
                $totalLateMinutes += $day['late_minutes'];
            }

            $summary[] = [
                'employee' => $employee,
                'total_izin_no_deduction' => $totalIzinNoDeduction,
                'total_izin_salary_deduction' => $totalIzinSalaryDeduction,
                'total_izin_terlambat' => $totalIzinTerlambat,
                'total_izin_pulang_awal' => $totalIzinPulangAwal,
                'total_late_minutes' => $totalLateMinutes,
                'total_terlambat_dengan_izin' => $totalIzinTerlambat,
                'total_terlambat_potong_gaji' => $totalLateMinutes,
            ];
        }

        $locations = Employee::where('status', 'active')->distinct()->pluck('location')->filter()->values();
        $positions = Employee::where('status', 'active')->distinct()->pluck('position')->filter()->values();

        return view('reports.attendance-summary', compact(
            'summary',
            'startDate',
            'endDate',
            'location',
            'position',
            'employeeId',
            'locations',
            'positions',
            'employees'
        ));
    }
}