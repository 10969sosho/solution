<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Loan;
use App\Models\LoanPayment;
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
            $previousLoansTotal = $employee->loans()->where('status', '!=' , 'paid')
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
            ->orderBy('name')
            ->get();

        if ($employeeId) {
            $employees = $employees->where('employee_id', $employeeId);
        }

        $mutasi = [];

        foreach ($employees as $employee) {
            $loans = $employee->loans()->orderBy('loan_date')->get();

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
            ->orderBy('name')
            ->get();

        if ($employeeId) {
            $employees = $employees->where('employee_id', $employeeId);
        }

        $report = [];
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $reportMonthName = $monthNames[$month];

        // Hitung bulan sebelumnya
        if ($month == 1) {
            $prevMonth = 12;
            $prevYear = $year - 1;
        } else {
            $prevMonth = $month - 1;
            $prevYear = $year;
        }
        $prevMonthName = $monthNames[$prevMonth];

        foreach ($employees as $employee) {
            $loans = $employee->loans()->orderBy('loan_date')->get();

            // Sisa sebelum bulan sebelumnya (sisa sampai akhir bulan -2)
            $paymentsBeforePrevMonth = $employee->loans->flatMap->payments
                ->filter(function ($payment) use ($prevYear, $prevMonth) {
                    $payDate = $payment->payment_date;
                    return $payDate->year < $prevYear || ($payDate->year == $prevYear && $payDate->month < $prevMonth);
                })
                ->sum('amount');

            $loansBeforePrevMonth = $loans->filter(function ($loan) use ($prevYear, $prevMonth) {
                $loanDate = $loan->loan_date;
                return $loanDate->year < $prevYear || ($loanDate->year == $prevYear && $loanDate->month < $prevMonth);
            })->sum('principal');

            $sisaBefore = $loansBeforePrevMonth - $paymentsBeforePrevMonth;

            // Bon bulan sebelumnya (pinjaman baru di bulan sebelumnya)
            $bonPrevMonth = $loans->filter(function ($loan) use ($prevYear, $prevMonth) {
                $loanDate = $loan->loan_date;
                return $loanDate->year == $prevYear && $loanDate->month == $prevMonth;
            })->sum('principal');

            // Pembayaran di bulan ini (bulan yang di-filter)
            $bayarMonth = $employee->loans->flatMap->payments
                ->filter(function ($payment) use ($year, $month) {
                    $payDate = $payment->payment_date;
                    return $payDate->year == $year && $payDate->month == $month;
                })
                ->sum('amount');

            // Sisa akhir
            $sisaAkhir = $sisaBefore + $bonPrevMonth - $bayarMonth;
            $status = $sisaAkhir <= 0 ? 'Lunas' : 'Belum Lunas';

            $report[] = [
                'employee_id' => $employee->employee_id,
                'name' => $employee->name,
                'sisa_before' => $sisaBefore,
                'bon_prev_month' => $bonPrevMonth,
                'bayar_month' => $bayarMonth,
                'sisa_akhir' => max(0, $sisaAkhir),
                'status' => $status,
            ];
        }

        return view('loans.laporan', compact('report', 'employees', 'year', 'month', 'reportMonthName', 'prevMonthName'));
    }
}