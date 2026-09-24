<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCompilation;
use App\Models\DailyAttendance;
use App\Models\Employee;
use App\Models\Permit;
use App\Models\PotonganTerlambat;
use App\Services\AttendanceProcessingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceDailyController extends Controller
{
    public function __construct(private AttendanceProcessingService $attendanceService)
    {
    }

    /**
     * MODUL 5: List Kompilasi Harian
     * Menampilkan daftar seluruh tanggal dalam 1 bulan beserta status datanya (Draft, Lock, Fix).
     */
    public function compilation(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        // Ambil data status yang sudah tersimpan di database
        $compilations = AttendanceCompilation::whereBetween('date', [
            $start->toDateString(),
            $end->toDateString(),
        ])->get()->keyBy(fn ($c) => $c->date->toDateString());

        $days = [];
        $totalDays = $end->day;
        $countFix = 0;
        $countLock = 0;
        $countDraft = 0;

        for ($d = 1; $d <= $totalDays; $d++) {
            $date = Carbon::create($year, $month, $d);
            $dateStr = $date->toDateString();
            $comp = $compilations->get($dateStr);
            $status = $comp ? $comp->status : 'draft';

            if ($status === 'fix') {
                $countFix++;
            } elseif ($status === 'lock') {
                $countLock++;
            } else {
                $countDraft++;
            }

            $days[] = [
                'day' => $d,
                'date' => $dateStr,
                'day_name' => $date->locale('id')->isoFormat('dddd'),
                'is_weekend' => $date->isWeekend(),
                'status' => $status,
                'compilation' => $comp,
            ];
        }

        $allFixed = ($countFix === $totalDays);

        return view('attendance.compilation', compact(
            'days',
            'year',
            'month',
            'totalDays',
            'countFix',
            'countLock',
            'countDraft',
            'allFixed'
        ));
    }

    /**
     * MODUL 4: Absen Daily (Harian Seluruh Karyawan)
     * Data absensi seluruh karyawan dalam 1 hari dengan deteksi anomali (highlight kuning),
     * action FIX per baris, keterangan izin, dan status tanggal (Draft/Lock/Fix).
     */
    public function daily(Request $request)
    {
        $dateStr = $request->input('date', now()->toDateString());
        $date = Carbon::parse($dateStr);

        $compilation = AttendanceCompilation::firstOrCreate(
            ['date' => $dateStr],
            ['status' => 'draft']
        );

        $employees = Employee::query()
            ->where('status', 'active')
            ->when(! auth()->user()->isSuperAdmin(), fn ($q) => $q->whereIn('position', config('hrms.operational_positions', [])))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->with(['golongan', 'jabatan'])
            ->orderBy('name')
            ->get();

        $permits = Permit::where('permit_date', $dateStr)->get();

        // Sinkronkan atau ambil data absensi harian karyawan
        $dailyRecords = [];
        foreach ($employees as $employee) {
            $record = DailyAttendance::where('date', $dateStr)
                ->where('employee_id', $employee->id)
                ->first();

            // Jika belum ada record, sinkronkan awal dari log mesin absensi
            if (! $record) {
                $dayData = $this->attendanceService->processDay($employee, $date);

                $checkIn = $dayData['check_locks']['check_in']
                    ? $dayData['check_locks']['check_in']['scan_time']->format('H:i:s')
                    : null;
                $breakOut = $dayData['check_locks']['break_out']
                    ? $dayData['check_locks']['break_out']['scan_time']->format('H:i:s')
                    : null;
                $breakIn = $dayData['check_locks']['break_in']
                    ? $dayData['check_locks']['break_in']['scan_time']->format('H:i:s')
                    : null;
                $checkOut = $dayData['check_locks']['check_out']
                    ? $dayData['check_locks']['check_out']['scan_time']->format('H:i:s')
                    : null;

                $permit = $permits->firstWhere('employee_id', $employee->id);
                $defaultKet = 'hadir';
                if ($permit) {
                    $defaultKet = 'izin';
                } elseif (! $dayData['present']) {
                    $defaultKet = $date->isWeekend() ? 'libur' : 'alpha';
                }

                $record = DailyAttendance::create([
                    'date' => $dateStr,
                    'employee_id' => $employee->id,
                    'check_in' => $checkIn,
                    'break_out' => $breakOut,
                    'break_in' => $breakIn,
                    'check_out' => $checkOut,
                    'late_check_in_minutes' => $dayData['late_minutes'],
                    'late_break_in_minutes' => $dayData['late_break_in_minutes'],
                    'overtime_minutes' => $dayData['overtime_minutes'],
                    'is_anomaly' => $dayData['is_anomaly'],
                    'anomaly_reason' => $dayData['anomaly_reason'],
                    'keterangan' => $defaultKet,
                    'nominal_izin' => 0,
                    'tipe_nominal_izin' => null,
                    'is_fixed' => false,
                ]);
            }

            $dailyRecords[] = [
                'employee' => $employee,
                'attendance' => $record,
            ];
        }

        return view('attendance.daily', compact('compilation', 'dailyRecords', 'dateStr'));
    }

    /**
     * Action FIX per baris karyawan & update Izin (Potong/Tambah Gaji)
     */
    public function saveRow(Request $request, DailyAttendance $daily)
    {
        $validated = $request->validate([
            'keterangan' => 'required|in:hadir,izin,libur,alpha',
            'tipe_nominal_izin' => 'nullable|in:potong_gaji,tambah_gaji',
            'nominal_izin' => 'nullable|numeric|min:0',
            'is_fixed' => 'nullable|boolean',
        ]);

        $isFixed = $request->has('is_fixed') ? (bool) $request->is_fixed : true;
        $updateData = [
            'keterangan' => $validated['keterangan'],
            'tipe_nominal_izin' => $validated['tipe_nominal_izin'] ?? null,
            'nominal_izin' => $validated['nominal_izin'] ?? 0,
            'is_fixed' => $isFixed,
        ];

        if ($isFixed) {
            $updateData['is_anomaly'] = false;
        }

        $daily->update($updateData);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $daily]);
        }

        return back()->with('success', 'Data absensi karyawan berhasil diperbarui');
    }

    /**
     * Update Status Tanggal (Draft, Lock, Fix)
     */
    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'status' => 'required|in:draft,lock,fix',
        ]);

        $dateStr = Carbon::parse($validated['date'])->toDateString();

        if ($validated['status'] === 'fix') {
            $hasUnfixedAnomalies = DailyAttendance::where('date', $dateStr)
                ->where('is_anomaly', true)
                ->where('is_fixed', false)
                ->exists();

            if ($hasUnfixedAnomalies) {
                return back()->withErrors(['status' => 'Semua anomali (kuning) harus diselesaikan dan di-FIX terlebih dahulu sebelum status tanggal dapat diubah menjadi FIX.']);
            }
        }

        $compilation = AttendanceCompilation::firstOrCreate(['date' => $dateStr]);
        $updateData = ['status' => $validated['status']];

        if ($validated['status'] === 'lock') {
            $updateData['locked_at'] = now();
        } elseif ($validated['status'] === 'fix') {
            $updateData['fixed_at'] = now();
            // Tandai seluruh baris hari itu sudah fixed
            DailyAttendance::where('date', $dateStr)->update(['is_fixed' => true, 'is_anomaly' => false]);
        }

        $compilation->update($updateData);

        return back()->with('success', "Status tanggal {$validated['date']} berhasil diubah menjadi " . strtoupper($validated['status']));
    }

    /**
     * MODUL 3: Detail Absen Karyawan
     * Menampilkan detail absensi per nama karyawan dalam 1 bulan (tanggal 1 - 30/31).
     */
    public function employeeDetail(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $employeeId = $request->input('employee_id');
        $search = $request->input('search');

        $employees = Employee::query()
            ->where('status', 'active')
            ->when(! auth()->user()->isSuperAdmin(), fn ($q) => $q->whereIn('position', config('hrms.operational_positions', [])))
            ->when($search, fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
            ->orderBy('name')
            ->get();

        $selectedEmployee = $employeeId ? Employee::find($employeeId) : $employees->first();

        $rows = [];
        $totalLateIn = 0;
        $totalLateBreakIn = 0;
        $totalLibur = 0;

        if ($selectedEmployee) {
            $start = Carbon::create($year, $month, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $dateStr = $date->toDateString();
                $record = DailyAttendance::where('date', $dateStr)
                    ->where('employee_id', $selectedEmployee->id)
                    ->first();

                if (! $record) {
                    $dayData = $this->attendanceService->processDay($selectedEmployee, $date);
                    $checkIn = $dayData['check_locks']['check_in'] ? $dayData['check_locks']['check_in']['scan_time']->format('H:i') : '-';
                    $breakOut = $dayData['check_locks']['break_out'] ? $dayData['check_locks']['break_out']['scan_time']->format('H:i') : '-';
                    $breakIn = $dayData['check_locks']['break_in'] ? $dayData['check_locks']['break_in']['scan_time']->format('H:i') : '-';
                    $checkOut = $dayData['check_locks']['check_out'] ? $dayData['check_locks']['check_out']['scan_time']->format('H:i') : '-';
                    $lateIn = $dayData['late_minutes'];
                    $lateBreakIn = $dayData['late_break_in_minutes'];
                    $ket = $dayData['present'] ? 'Hadir' : ($date->isWeekend() ? 'Libur' : 'Alpha');
                } else {
                    $checkIn = $record->check_in ? substr($record->check_in, 0, 5) : '-';
                    $breakOut = $record->break_out ? substr($record->break_out, 0, 5) : '-';
                    $breakIn = $record->break_in ? substr($record->break_in, 0, 5) : '-';
                    $checkOut = $record->check_out ? substr($record->check_out, 0, 5) : '-';
                    $lateIn = $record->late_check_in_minutes;
                    $lateBreakIn = $record->late_break_in_minutes;
                    $ket = ucfirst($record->keterangan);
                }

                if (strtolower($ket) === 'libur') {
                    $totalLibur++;
                }

                $totalLateIn += $lateIn;
                $totalLateBreakIn += $lateBreakIn;

                $rows[] = [
                    'date' => $dateStr,
                    'day_num' => $date->day,
                    'day_name' => $date->locale('id')->isoFormat('dddd'),
                    'check_in' => $checkIn,
                    'break_out' => $breakOut,
                    'break_in' => $breakIn,
                    'check_out' => $checkOut,
                    'late_in' => $lateIn,
                    'late_break_in' => $lateBreakIn,
                    'keterangan' => $ket,
                    'total_libur_cumulative' => $totalLibur,
                ];
            }
        }

        return view('attendance.employee_detail', compact(
            'employees',
            'selectedEmployee',
            'year',
            'month',
            'rows',
            'totalLateIn',
            'totalLateBreakIn',
            'totalLibur',
            'search'
        ));
    }

    /**
     * MODUL 6: Rekap Bulanan
     * Menampilkan rekap seluruh karyawan dalam 1 bulan dengan syarat seluruh tanggal sudah FIX.
     */
    public function monthlyRecap(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $totalDays = $end->day;

        // Validasi kelengkapan status FIX
        $fixedDatesCount = AttendanceCompilation::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('status', 'fix')
            ->count();

        $allFixed = ($fixedDatesCount === $totalDays);

        $employees = Employee::query()
            ->where('status', 'active')
            ->when(! auth()->user()->isSuperAdmin(), fn ($q) => $q->whereIn('position', config('hrms.operational_positions', [])))
            ->with(['golongan', 'jabatan'])
            ->orderBy('name')
            ->get();

        $potonganMasters = PotonganTerlambat::all();

        $recap = [];

        foreach ($employees as $employee) {
            $dailies = DailyAttendance::whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->where('employee_id', $employee->id)
                ->get();

            $totalLateInMinutes = 0;
            $totalLateBreakInMinutes = 0;
            $nominalIzinTambah = 0;
            $nominalIzinPotong = 0;
            $totalLiburDays = 0;
            $totalOvertimeMinutes = 0;

            foreach ($dailies as $d) {
                // Jika izin ada nominal, keterlambatan diabaikan
                if ($d->isLateIgnored()) {
                    if ($d->tipe_nominal_izin === 'tambah_gaji') {
                        $nominalIzinTambah += (float) $d->nominal_izin;
                    } elseif ($d->tipe_nominal_izin === 'potong_gaji') {
                        $nominalIzinPotong += (float) $d->nominal_izin;
                    }
                } else {
                    $totalLateInMinutes += $d->late_check_in_minutes;
                    $totalLateBreakInMinutes += $d->late_break_in_minutes;
                }

                if ($d->keterangan === 'libur') {
                    $totalLiburDays++;
                }

                $totalOvertimeMinutes += $d->overtime_minutes;
            }

            // Hitung potongan terlambat dari akumulasi sebulan vs master potongan
            $nominalPotonganMasukKerja = $this->lookupPotonganFine($employee, $totalLateInMinutes, 'masuk_kerja', $potonganMasters);
            $nominalPotonganSetelahIstirahat = $this->lookupPotonganFine($employee, $totalLateBreakInMinutes, 'setelah_istirahat', $potonganMasters);
            $totalNominalTerlambat = $nominalPotonganMasukKerja + $nominalPotonganSetelahIstirahat;

            // Aturan Jatah Libur & Potongan Masuk
            $quota = $employee->getEffectiveLeaveQuota();
            $hariPotongMasuk = max(0, $totalLiburDays - $quota);
            $sisaLibur = max(0, $quota - $totalLiburDays);

            $dailyRate = ((float) $employee->salary) / (int) config('payroll_rules.potongan_masuk.divider', 30);
            $nominalPotongMasuk = round($dailyRate * $hariPotongMasuk, 2);

            // Perhitungan Bonus Libur (Section 13 & 14 briefing)
            $bonusLibur = 0;
            if ($sisaLibur > 0) {
                $salaryThreshold = (float) config('payroll_rules.bonus_libur.salary_threshold', 2250000);
                if ((float) $employee->salary >= $salaryThreshold) {
                    $bonusLibur = round($dailyRate * (float) config('payroll_rules.bonus_libur.high_salary_multiplier', 1.5) * $sisaLibur, 2);
                } else {
                    $bonusLibur = (float) config('payroll_rules.bonus_libur.low_salary_per_day', 75000) * $sisaLibur;
                }
            }

            // Uang Jaga Malam: Mandor jaga malam = Rp 600.000 / bulan
            $uangJagaMalam = 0;
            if ($employee->is_night_guard) {
                if ($employee->isMandor()) {
                    $uangJagaMalam = (float) config('payroll_rules.uang_jaga_malam.mandor_bonus', 600000);
                } else {
                    $uangJagaMalam = (float) config('payroll_rules.uang_jaga_malam.default_bonus', 0);
                }
            }

            // Nominal Lembur (fleksibel: default per jam = Gaji / 173)
            $overtimeHours = $totalOvertimeMinutes / 60;
            $hourlyRate = ((float) $employee->salary > 0) ? round(((float) $employee->salary) / 173, 2) : 0;
            $nominalLembur = round($overtimeHours * $hourlyRate, 2);

            $recap[] = [
                'employee' => $employee,
                'total_late_in_minutes' => $totalLateInMinutes,
                'total_late_break_in_minutes' => $totalLateBreakInMinutes,
                'nominal_potongan_masuk_kerja' => $nominalPotonganMasukKerja,
                'nominal_potongan_setelah_istirahat' => $nominalPotonganSetelahIstirahat,
                'total_nominal_terlambat' => $totalNominalTerlambat,
                'nominal_izin_tambah' => $nominalIzinTambah,
                'nominal_izin_potong' => $nominalIzinPotong,
                'total_libur_days' => $totalLiburDays,
                'leave_quota' => $quota,
                'hari_potong_masuk' => $hariPotongMasuk,
                'nominal_potong_masuk' => $nominalPotongMasuk,
                'total_overtime_minutes' => $totalOvertimeMinutes,
                'nominal_lembur' => $nominalLembur,
                'bonus_libur' => $bonusLibur,
                'uang_jaga_malam' => $uangJagaMalam,
            ];
        }

        return view('attendance.monthly_recap', compact(
            'recap',
            'year',
            'month',
            'allFixed',
            'fixedDatesCount',
            'totalDays'
        ));
    }

    private function lookupPotonganFine(Employee $employee, int $minutes, string $type, $potonganMasters): float
    {
        if ($minutes <= 0 || ! $employee->golongan_id) {
            return 0;
        }

        $match = $potonganMasters->filter(function ($item) use ($employee, $minutes, $type) {
            if ($item->golongan_id !== $employee->golongan_id || $item->type !== $type) {
                return false;
            }
            if ($minutes < $item->min_minutes) {
                return false;
            }
            if (! is_null($item->max_minutes) && $minutes > $item->max_minutes) {
                return false;
            }
            return true;
        })->sortByDesc('min_minutes')->first();

        return $match ? (float) $match->amount : 0;
    }
}
