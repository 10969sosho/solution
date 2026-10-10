<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\NightGuard;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NightGuardController extends Controller
{
    /**
     * Menampilkan daftar tanggal jaga malam dalam satu bulan.
     * Mirip list kompilasi, status diganti nama karyawan (dropdown), aksi konfirmasi dan edit.
     */
    public function index(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $totalDays = $end->day;

        $employees = Employee::where('status', 'active')
            ->orderBy('name')
            ->get();

        $nightGuards = NightGuard::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->with('employee')
            ->get()
            ->keyBy(fn ($ng) => $ng->date->toDateString());

        $days = [];
        $countConfirmed = 0;
        $countDraft = 0;
        $countEmpty = 0;

        for ($d = 1; $d <= $totalDays; $d++) {
            $date = Carbon::create($year, $month, $d);
            $dateStr = $date->toDateString();
            $ng = $nightGuards->get($dateStr);

            if ($ng && $ng->status === 'confirmed') {
                $countConfirmed++;
            } elseif ($ng && $ng->employee_id) {
                $countDraft++;
            } else {
                $countEmpty++;
            }

            $days[] = [
                'day' => $d,
                'date' => $dateStr,
                'day_name' => $date->locale('id')->isoFormat('dddd'),
                'is_weekend' => $date->isWeekend(),
                'night_guard' => $ng,
                'employee_id' => $ng?->employee_id,
                'status' => $ng?->status ?? 'empty',
            ];
        }

        return view('attendance.night_guard', compact(
            'days',
            'employees',
            'year',
            'month',
            'totalDays',
            'countConfirmed',
            'countDraft',
            'countEmpty'
        ));
    }

    /**
     * Update atau Konfirmasi Jaga Malam per tanggal.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'employee_id' => 'nullable|exists:employees,id',
            'action' => 'required|in:confirm,draft,clear',
        ]);

        $dateStr = $validated['date'];
        $action = $validated['action'];

        $nightGuard = NightGuard::firstOrNew(['date' => $dateStr]);

        if ($action === 'clear') {
            $nightGuard->delete();
            return back()->with('success', "Penugasan jaga malam tanggal {$dateStr} berhasil dihapus.");
        }

        $nightGuard->employee_id = $validated['employee_id'] ?? null;

        if ($action === 'confirm') {
            if (!$nightGuard->employee_id) {
                return back()->with('error', "Pilih karyawan terlebih dahulu sebelum mengonfirmasi.");
            }
            $nightGuard->status = 'confirmed';
            $nightGuard->confirmed_at = now();
        } else {
            // mode edit / draft
            $nightGuard->status = 'draft';
        }

        $nightGuard->save();

        $statusMsg = $action === 'confirm' ? 'dikonfirmasi' : 'disimpan sebagai draft';
        return back()->with('success', "Jaga malam tanggal {$dateStr} berhasil {$statusMsg}.");
    }
}
