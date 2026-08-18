<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\WorkSetting;
use App\Services\AttendanceProcessingService;
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
            ->when($location, fn ($q) => $q->where('location', $location))
            ->when($position, fn ($q) => $q->where('position', $position))
            ->orderBy('name')
            ->get();

        $selectedEmployee = null;
        $report = null;

        if ($employeeId) {
            $selectedEmployee = $employees->firstWhere('employee_id', $employeeId)
                ?? Employee::where('employee_id', $employeeId)->first();
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
}