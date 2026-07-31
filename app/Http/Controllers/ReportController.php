<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\AttendanceLog;
use App\Models\WorkSetting;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function monthly(Request $request)
    {
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $employeeId = $request->input('employee_id');

        $employees = Employee::where('status', 'active')->orderBy('name')->get();
        
        if ($employeeId) {
            $selectedEmployee = Employee::where('employee_id', $employeeId)->first();
            $report = $selectedEmployee ? $selectedEmployee->getMonthlyReport($year, $month) : null;
        } else {
            $selectedEmployee = null;
            $report = null;
        }

        $workSetting = WorkSetting::where('is_active', true)->first();

        return view('reports.monthly', compact(
            'employees',
            'selectedEmployee',
            'report',
            'year',
            'month',
            'workSetting'
        ));
    }

    public function summary(Request $request)
    {
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $employees = Employee::where('status', 'active')->orderBy('name')->get();
        $workSetting = WorkSetting::where('is_active', true)->first();

        $summary = [];

        foreach ($employees as $employee) {
            $report = $employee->getMonthlyReport($year, $month);
            
            $summary[] = [
                'employee_id' => $employee->employee_id,
                'name' => $employee->name,
                'department' => $employee->department,
                'total_days' => $report['total_days'],
                'total_hours' => $report['total_hours'],
                'total_late_minutes' => $report['total_late_minutes'],
                'total_overtime_minutes' => $report['total_overtime_minutes'],
                'days_late' => $report['days_late'],
            ];
        }

        return view('reports.summary', compact('summary', 'year', 'month', 'workSetting'));
    }
}
