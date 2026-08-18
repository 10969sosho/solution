<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\SeasonalSchedule;
use App\Models\WorkSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceProcessingService
{
    public const MIN_BREAK_IN_MARGIN_MINUTES = 15;

    /**
     * Proses absensi harian satu karyawan.
     *
     * @return array{
     *   date: string,
     *   present: bool,
     *   schedule: array,
     *   check_locks: array<string, ?array>,
     *   late_minutes: int,
     *   early_leave_minutes: int,
     *   total_work_minutes: int,
     *   ignored_scans: array
     * }
     */
    public function processDay(Employee $employee, Carbon $date): array
    {
        $logs = $employee->attendanceLogs()
            ->whereBetween('scan_time', [
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay(),
            ])
            ->orderBy('scan_time')
            ->get();

        $schedule = $this->resolveSchedule($employee, $date);

        $result = [
            'date' => $date->toDateString(),
            'present' => false,
            'schedule' => $schedule,
            'check_locks' => [
                'check_in' => null,
                'break_out' => null,
                'break_in' => null,
                'check_out' => null,
            ],
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'total_work_minutes' => 0,
            'ignored_scans' => [],
        ];

        if ($logs->isEmpty()) {
            return $result;
        }

        $result['present'] = true;

        $checkInTime = $date->copy()->setTimeFromTimeString($schedule['check_in_time']);
        $breakOutTime = $date->copy()->setTimeFromTimeString($schedule['break_out_time']);
        $breakInTime = $date->copy()->setTimeFromTimeString($schedule['break_in_time']);
        $checkOutTime = $date->copy()->setTimeFromTimeString($schedule['check_out_time']);
        $tolerance = (int) $schedule['late_tolerance_minutes'];
        $minBreakIn = $breakInTime->copy()->subMinutes(self::MIN_BREAK_IN_MARGIN_MINUTES);

        [$checkLocks, $ignored] = $this->classifyCheckLocks($logs, $minBreakIn, $breakOutTime);

        $result['check_locks'] = $checkLocks;
        $result['ignored_scans'] = $ignored;

        if ($checkLocks['check_in']) {
            $expectedCheckIn = $checkInTime->copy()->addMinutes($tolerance);
            if ($checkLocks['check_in']['scan_time']->gt($expectedCheckIn)) {
                $result['late_minutes'] = (int) $expectedCheckIn->diffInMinutes($checkLocks['check_in']['scan_time']);
            }
        }

        if ($checkLocks['check_out']) {
            if ($checkLocks['check_out']['scan_time']->lt($checkOutTime)) {
                $result['early_leave_minutes'] = (int) $checkLocks['check_out']['scan_time']->diffInMinutes($checkOutTime);
            }
        }

        $result['total_work_minutes'] = $this->computeWorkMinutes($checkLocks);

        return $result;
    }

    /**
     * Proses absensi satu bulan untuk satu karyawan (untuk laporan rincian & payroll).
     *
     * @return array
     */
    public function processMonth(Employee $employee, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $report = [
            'schedule' => $this->resolveSchedule($employee, $start),
            'total_days' => 0,
            'days_present' => 0,
            'total_late_minutes' => 0,
            'days_late' => 0,
            'total_early_leave_minutes' => 0,
            'total_work_minutes' => 0,
            'daily_details' => [],
        ];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $day = $this->processDay($employee, $date->copy());
            $day['is_weekend'] = $date->isWeekend();

            if ($day['present']) {
                $report['days_present']++;
                $report['total_late_minutes'] += $day['late_minutes'];
                if ($day['late_minutes'] > 0) {
                    $report['days_late']++;
                }
                $report['total_early_leave_minutes'] += $day['early_leave_minutes'];
                $report['total_work_minutes'] += $day['total_work_minutes'];
            }

            $report['daily_details'][] = $day;
        }

        $report['total_days'] = $end->day;

        return $report;
    }

    /**
     * Resolve jadwal kerja untuk tanggal tertentu dengan prioritas:
     * 1. Jam kerja khusus per karyawan (aktif & sudah efektif)
     * 2. Setting global
     * Lalu menerapkan penyesuaian jam kerja musiman bila ada.
     */
    public function resolveSchedule(Employee $employee, Carbon $date): array
    {
        $default = WorkSetting::getActive();

        $schedule = $employee->activeSchedule()
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_from')->orWhere('effective_from', '<=', $date->toDateString());
            })
            ->first();

        $data = [
            'check_in_time' => $schedule?->check_in_time ?? $default?->check_in_time ?? '08:00:00',
            'break_out_time' => $schedule?->break_out_time ?? $default?->break_out_time ?? '12:00:00',
            'break_in_time' => $schedule?->break_in_time ?? $default?->break_in_time ?? '13:00:00',
            'check_out_time' => $schedule?->check_out_time ?? $default?->check_out_time ?? '17:00:00',
            'late_tolerance_minutes' => $schedule?->late_tolerance_minutes ?? $default?->late_tolerance_minutes ?? 15,
            'source' => $schedule ? 'employee_schedule' : 'global',
        ];

        $seasonal = SeasonalSchedule::forDate($date);
        if ($seasonal) {
            $data['seasonal'] = $seasonal->name;

            $checkIn = Carbon::parse($data['check_in_time']);
            $checkOut = Carbon::parse($data['check_out_time']);

            if ($seasonal->force_check_in_time) {
                $checkIn = Carbon::parse($seasonal->force_check_in_time);
            } else {
                $checkIn->addMinutes((int) $seasonal->check_in_delta_minutes);
            }

            $checkOut->addMinutes((int) $seasonal->check_out_delta_minutes);

            $data['check_in_time'] = $checkIn->format('H:i:s');
            $data['check_out_time'] = $checkOut->format('H:i:s');
        }

        return $data;
    }

    /**
     * Klasifikasikan scan menjadi 4 check-lock dengan proteksi anti-kecurangan.
     *
     * Aturan:
     * - check_in  = scan pertama hari itu
     * - break_out = scan yang paling dekat dengan jam keluar istirahat
     *               (scan curang seperti 12:30 tidak akan terpilih karena
     *                jaraknya lebih jauh dari break_out 12:00)
     * - break_in  = scan pertama >= batas minimal masuk istirahat (12:45)
     * - check_out = scan terakhir setelah break_in
     * - scan yang tidak masuk slot dianggap tidak sah / di-skip
     */
    private function classifyCheckLocks(Collection $logs, Carbon $minBreakIn, Carbon $breakOutTime): array
    {
        $checkLocks = [
            'check_in' => null,
            'break_out' => null,
            'break_in' => null,
            'check_out' => null,
        ];
        $ignored = [];

        $checkLocks['check_in'] = $this->mapLog($logs->first());

        $lunchRegion = $logs->filter(fn ($log) => $log->scan_time->lt($minBreakIn) && ! $log->is($logs->first()));
        $afterBreakIn = $logs->filter(fn ($log) => $log->scan_time->gte($minBreakIn));

        if ($lunchRegion->isNotEmpty()) {
            $checkLocks['break_out'] = $lunchRegion->sortBy(
                fn ($log) => abs($log->scan_time->diffInMinutes($breakOutTime))
            )->first();
        }

        if ($afterBreakIn->isNotEmpty()) {
            $checkLocks['break_in'] = $this->mapLog($afterBreakIn->first());
            $checkLocks['check_out'] = $this->mapLog($afterBreakIn->last());
        }

        // Scan yang tidak masuk slot dianggap tidak sah (anti-fraud).
        foreach ($logs as $log) {
            $isUsed = array_filter($checkLocks, fn ($c) => $c && $c['scan_time']->equalTo($log->scan_time));
            if (empty($isUsed)) {
                $ignored[] = [
                    'scan_time' => $log->scan_time->format('Y-m-d H:i:s'),
                    'status' => $log->status,
                ];
            }
        }

        return [$checkLocks, $ignored];
    }

    private function mapLog(AttendanceLog $log): array
    {
        return [
            'scan_time' => $log->scan_time,
            'status' => $log->status,
            'log_id' => $log->id,
        ];
    }

    private function computeWorkMinutes(array $checkLocks): int
    {
        $total = 0;

        if ($checkLocks['check_in'] && $checkLocks['break_out']) {
            $total += (int) $checkLocks['check_in']['scan_time']->diffInMinutes($checkLocks['break_out']['scan_time']);
        }

        if ($checkLocks['break_in'] && $checkLocks['check_out']) {
            $total += (int) $checkLocks['break_in']['scan_time']->diffInMinutes($checkLocks['check_out']['scan_time']);
        }

        // Jika tidak ada punch istirahat, hitung langsung masuk -> pulang.
        if ($checkLocks['check_in'] && $checkLocks['check_out'] && ! $checkLocks['break_in']) {
            $total = (int) $checkLocks['check_in']['scan_time']->diffInMinutes($checkLocks['check_out']['scan_time']);
        }

        return max(0, $total);
    }
}