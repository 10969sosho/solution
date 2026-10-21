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
