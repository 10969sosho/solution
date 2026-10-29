<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\NightGuard;
use App\Services\AttendanceProcessingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NightGuardController extends Controller
{
    public function __construct(private AttendanceProcessingService $attendanceService) {}

    /**
     * Menu Jaga Malam - tampilan & alur seperti Absen Daily.
     * Menampilkan seluruh petugas jaga malam (17:00 - 06:00) pada satu tanggal.
     */
    public function index(Request $request)
    {
        $dateStr = $request->input('date', now()->toDateString());
        $date = Carbon::parse($dateStr);

        $employees = Employee::where('status', 'active')
            ->where('is_night_guard', true)
            ->orderBy('name')
            ->get();

        $rows = [];
        $presentCount = 0;

        foreach ($employees as $employee) {
            $day = $this->attendanceService->processNight($employee, $date->copy());

            if ($day['present']) {
                $presentCount++;
            }

            $rows[] = [
                'employee' => $employee,
                'check_in' => $day['check_locks']['check_in']
                    ? $day['check_locks']['check_in']['scan_time']->format('H:i')
                    : '-',
                'check_out' => $day['check_locks']['check_out']
                    ? $day['check_locks']['check_out']['scan_time']->format('H:i')
                    : '-',
                'work_minutes' => $day['total_work_minutes'],
                'late_minutes' => $day['late_minutes'],
                'early_leave_minutes' => $day['early_leave_minutes'],
                'present' => $day['present'],
            ];
        }

        return view('attendance.night_guard', compact('rows', 'dateStr', 'employees', 'presentCount'));
    }

    /**
     * LIST KOMPILASI JAGA MALAM: rekap per tanggal dalam 1 bulan untuk
     * petugas jaga malam (is_night_guard) — mirip List Kompilasi absensi harian.
     */
    public function compilation(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $employees = Employee::where('status', 'active')
            ->where('is_night_guard', true)
            ->orderBy('name')
            ->get();

        $guards = NightGuard::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($g) => $g->date->toDateString());

        $days = [];
        $totalPresent = 0;
        $totalAssigned = 0;

        for ($d = 1; $d <= $end->day; $d++) {
            $date = Carbon::create($year, $month, $d);
            $dateStr = $date->toDateString();

            $present = 0;
            $absentNames = [];
            foreach ($employees as $employee) {
                $day = $this->attendanceService->processNight($employee, $date->copy());
                if ($day['present']) {
                    $present++;
                } else {
                    $absentNames[] = $employee->name;
                }
            }

            $totalPresent += $present;
            $totalAssigned += $employees->count();

            $days[] = [
                'day' => $d,
                'date' => $dateStr,
                'day_name' => $date->locale('id')->isoFormat('dddd'),
                'is_weekend' => $date->isWeekend(),
                'present' => $present,
                'absent' => max(0, $employees->count() - $present),
                'absent_names' => $absentNames,
                'guard' => $guards->get($dateStr),
            ];
        }

        return view('attendance.night_guard_compilation', [
            'days' => $days,
            'year' => $year,
            'month' => $month,
            'totalDays' => $end->day,
            'employees' => $employees,
            'totalPresent' => $totalPresent,
            'totalAssigned' => $totalAssigned,
        ]);
    }

    /**
     * Update atau Konfirmasi Jaga Malam per tanggal (backward compatibility).
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
            if (! $nightGuard->employee_id) {
                return back()->with('error', 'Pilih karyawan terlebih dahulu sebelum mengonfirmasi.');
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
