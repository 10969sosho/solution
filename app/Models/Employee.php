<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'employee_id',
        'name',
        'position',
        'department',
        'phone',
        'email',
        'join_date',
        'status',
        'address',
    ];

    protected $casts = [
        'join_date' => 'date',
    ];

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class, 'user_id', 'employee_id');
    }

    public function getMonthlyReport($year, $month)
    {
        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();

        $logs = $this->attendanceLogs()
            ->whereBetween('scan_time', [$startDate, $endDate])
            ->orderBy('scan_time')
            ->get();

        $workSetting = WorkSetting::where('is_active', true)->first();
        $checkInTime = $workSetting ? $workSetting->check_in_time : '08:00:00';
        $checkOutTime = $workSetting ? $workSetting->check_out_time : '17:00:00';
        $lateTolerance = $workSetting ? $workSetting->late_tolerance_minutes : 15;

        $report = [
            'total_days' => 0,
            'total_hours' => 0,
            'total_late_minutes' => 0,
            'total_overtime_minutes' => 0,
            'days_late' => 0,
            'days_absent' => 0,
            'daily_details' => [],
        ];

        $groupedByDate = $logs->groupBy(function ($log) {
            return $log->scan_time->format('Y-m-d');
        });

        foreach ($groupedByDate as $date => $dayLogs) {
            $checkIn = $dayLogs->where('status', '0')->first();
            $checkOut = $dayLogs->where('status', '1')->last();

            if ($checkIn && $checkOut) {
                $report['total_days']++;

                $workHours = $checkIn->scan_time->diffInHours($checkOut->scan_time);
                $report['total_hours'] += $workHours;

                $expectedCheckIn = \Carbon\Carbon::parse($date . ' ' . $checkInTime);
                $actualCheckIn = $checkIn->scan_time;

                if ($actualCheckIn->gt($expectedCheckIn->copy()->addMinutes($lateTolerance))) {
                    $lateMinutes = $expectedCheckIn->diffInMinutes($actualCheckIn);
                    $report['total_late_minutes'] += $lateMinutes;
                    $report['days_late']++;
                }

                $expectedCheckOut = \Carbon\Carbon::parse($date . ' ' . $checkOutTime);
                if ($checkOut->scan_time->gt($expectedCheckOut)) {
                    $overtimeMinutes = $expectedCheckOut->diffInMinutes($checkOut->scan_time);
                    $report['total_overtime_minutes'] += $overtimeMinutes;
                }

                $report['daily_details'][] = [
                    'date' => $date,
                    'check_in' => $checkIn->scan_time->format('H:i'),
                    'check_out' => $checkOut->scan_time->format('H:i'),
                    'work_hours' => $workHours,
                    'late_minutes' => isset($lateMinutes) ? $lateMinutes : 0,
                    'overtime_minutes' => isset($overtimeMinutes) ? $overtimeMinutes : 0,
                ];
            }
        }

        return $report;
    }
}
