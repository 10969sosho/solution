<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Payroll;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $query = Loan::with('employee')->orderBy('loan_date', 'desc');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $loans = $query->paginate(25);
        $employees = Employee::where('status', 'active')->orderBy('name')->get();

        return view('loans.index', compact('loans', 'employees'));
    }

    public function create()
    {
        $employees = Employee::where('status', 'active')->orderBy('name')->get();

        // Calculate totals for each employee
        $employeesWithTotals = $employees->map(function ($employee) {
            $previousLoansTotal = $employee->loans()->where('status', '!=', 'paid')
                ->sum('principal');
            $allLoansTotal = $employee->loans()->sum('principal');

            return (object) [
                'employee' => $employee,
                'previous_loans_total' => $previousLoansTotal,
                'all_loans_total' => $allLoansTotal,
            ];
        });

        return view('loans.create', compact('employeesWithTotals'));
    }

    public function paymentCreate(Loan $loan)
    {
        return view('loan-payments.create', compact('loan'));
    }

    public function paymentStore(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'previous_balance' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'remaining_after' => 'nullable|numeric|min:0',
        ]);

        LoanPayment::create($validated);

        // Update loan remaining balance
        $newRemaining = max(0, (float) $loan->principal - ((float) $loan->total_paid + (float) $validated['amount']));
        $loan->update(['principal' => (float) $loan->principal]); // keep original principal

        // Check if fully paid
        if ($newRemaining <= 0) {
            $loan->update(['status' => 'paid']);
        }

        return redirect()->route('loans.show', $loan)->with('success', 'Pembayaran berhasil dicatat');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'loan_date' => 'required|date',
            'principal' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'previous_loans_total' => 'nullable|numeric|min:0',
            'all_loans_total' => 'nullable|numeric|min:0',
        ]);

        $validated['status'] = 'active';

        Loan::create($validated);

        return redirect()->route('loans.index')->with('success', 'Pinjaman berhasil dicatat');
    }

    public function show(Loan $loan)
    {
        $loan->load(['employee', 'payments']);

        return view('loans.show', compact('loan'));
    }

    public function storePayment(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:255',
        ]);

        $validated['loan_id'] = $loan->id;
        $validated['employee_id'] = $loan->employee_id;

        LoanPayment::create($validated);

        if ($loan->isFullyPaid()) {
            $loan->update(['status' => 'paid']);
        }

        return redirect()->route('loans.show', $loan)->with('success', 'Pembayaran berhasil dicatat');
    }

    public function destroy(Loan $loan)
    {
        $loan->delete();

        return redirect()->route('loans.index')->with('success', 'Pinjaman berhasil dihapus');
    }

    /**
     * Laporan mutasi pinjaman per karyawan, termasuk sisa bon periode lalu.
     */
    public function mutasi(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $employeeId = $request->input('employee_id');

        $employees = Employee::query()
            ->where('status', 'active')
            ->whereHas('loans')
            ->when($employeeId, fn ($q) => $q->whereKey($employeeId))
            ->orderBy('name')
            ->get();

        $mutasi = [];

        foreach ($employees as $employee) {
            $loans = $employee->loans()->with('payments')->orderBy('loan_date')->get();

            foreach ($loans as $loan) {
                $payments = $loan->payments()->orderBy('payment_date')->get();

                $mutasi[] = [
                    'employee_id' => $employee->employee_id,
                    'name' => $employee->name,
                    'loan_id' => $loan->id,
                    'loan_date' => $loan->loan_date->format('d M Y'),
                    'principal' => (float) $loan->principal,
                    'total_paid' => (float) $payments->sum('amount'),
                    'remaining' => $loan->remaining_balance,
                    'status' => $loan->status,
                    'payments' => $payments,
                ];
            }
        }

        return view('loans.mutasi', compact('mutasi', 'employees', 'year', 'month', 'employeeId'));
    }

    /**
     * Laporan pinjaman bulanan - menampilkan sisa, bon bulan lalu, bayar bulan ini, sisa akhir, status.
     */
    public function laporan(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $employeeId = $request->input('employee_id');

        $employees = Employee::query()
            ->where('status', 'active')
            ->whereHas('loans')
            ->when($employeeId, fn ($q) => $q->whereKey($employeeId))
            ->orderBy('name')
            ->get();

        $report = [];
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $reportMonthName = $monthNames[$month];

        foreach ($employees as $employee) {
            $loans = $employee->loans()->with('payments')->orderBy('loan_date')->get();

            $periodStart = now()->setDate($year, $month, 1)->startOfMonth();
            $sisaBefore = $loans->filter(fn ($loan) => $loan->loan_date->lt($periodStart))
                ->sum('principal') - $loans->filter(fn ($loan) => $loan->loan_date->lt($periodStart))
                ->flatMap(fn ($loan) => $loan->payments)
                ->filter(fn ($payment) => $payment->payment_date->lt($periodStart))
                ->sum('amount');

            $bonMonth = $loans->filter(fn ($loan) => $loan->loan_date->year === $year && $loan->loan_date->month === $month)
                ->sum('principal');

            $bayarMonth = Payroll::where('employee_id', $employee->id)
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->sum('pinjaman_deduction');

            $sisaBefore = max(0, $sisaBefore);
            $sisaAkhir = max(0, $sisaBefore + $bonMonth - $bayarMonth);

            $report[] = [
                'employee_id' => $employee->employee_id,
                'name' => $employee->name,
                'sisa_before' => $sisaBefore,
                'bon_month' => $bonMonth,
                'bayar_month' => $bayarMonth,
                'sisa_akhir' => $sisaAkhir,
            ];
        }

        return view('loans.laporan', compact('report', 'employees', 'year', 'month', 'reportMonthName'));
    }
}
