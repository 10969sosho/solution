<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Employee;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_today' => AttendanceLog::whereDate('scan_time', today())->count(),
            'total_machines' => AttendanceLog::distinct('machine_sn')->count('machine_sn'),
            'total_users' => AttendanceLog::distinct('user_id')->count('user_id'),
            'latest_scan' => AttendanceLog::latest('scan_time')->first(),
        ];

        return view('dashboard.index', compact('stats'));
    }

    public function data(Request $request)
    {
        $logs = AttendanceLog::latest('scan_time')
            ->limit(100)
            ->get()
            ->map(function ($log) {
                $employee = Employee::where('employee_id', $log->user_id)->first();
                
                return [
                    'id' => $log->id,
                    'user_id' => $log->user_id,
                    'employee_name' => $employee ? $employee->name : 'Unknown',
                    'department' => $employee ? $employee->department : '-',
                    'scan_time' => $log->scan_time->format('d/m/Y H:i:s'),
                    'machine_sn' => $log->machine_sn,
                    'status' => $log->status,
                    'status_label' => $this->getStatus($log->status),
                    'created_at' => $log->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $logs,
        ]);
    }

    private function getStatus($code)
    {
        return match ($code) {
            '0' => 'Check In',
            '1' => 'Check Out',
            '2' => 'Break Out',
            '3' => 'Break In',
            '4' => 'Overtime In',
            '5' => 'Overtime Out',
            '255' => 'Other',
            default => 'Unknown',
        };
    }
}
