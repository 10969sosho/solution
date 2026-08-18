<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payroll;
use App\Services\PayrollService;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct(private PayrollService $payrollService)
    {
    }

    public function index(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $payrolls = Payroll::with('employee')
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->orderBy('employee_id')
            ->get();

        return view('payrolls.index', compact('payrolls', 'year', 'month'));
    }

    public function show(Payroll $payroll)
    {
        $payroll->load('employee');
        return view('payrolls.show', compact('payroll'));
    }

    public function generate(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $this->payrollService->generateAll($year, $month);

        return redirect()
            ->route('payrolls.index', ['year' => $year, 'month' => $month])
            ->with('success', 'Payroll berhasil digenerate');
    }

    public function markPaid(Request $request, Payroll $payroll)
    {
        $this->payrollService->markPaid($payroll);

        return redirect()
            ->route('payrolls.index', [
                'year' => $payroll->period_year,
                'month' => $payroll->period_month,
            ])
            ->with('success', "Payroll {$payroll->employee?->name} ditandai sudah dibayar");
    }

    public function thr(Request $request)
    {
        $year = (int) $request->input('year', now()->year);

        $employees = Employee::where('status', 'active')->orderBy('name')->get();

        $rows = $employees->map(function ($employee) use ($year) {
            $thr = $this->payrollService->calculateThr($employee, $year);

            return [
                'employee_id' => $employee->employee_id,
                'name' => $employee->name,
                'position' => $employee->position,
                'join_date' => $employee->join_date?->format('d M Y'),
                'tenure_months' => $thr['tenure_months'],
                'long_service' => $thr['long_service'],
                'salary' => (float) $employee->salary,
                'thr' => $thr['thr'],
            ];
        })->sortByDesc('thr');

        return view('payrolls.thr', compact('rows', 'year'));
    }
}