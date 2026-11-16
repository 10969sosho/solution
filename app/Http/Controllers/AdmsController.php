<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdmsController extends Controller
{
    public function cdata(Request $request)
    {
        $sn = $request->query('SN');
        $table = $request->query('table');
        $body = $request->getContent();

        Log::channel('adms')->info('ADMS DATA RECEIVED', [
            'sn' => $sn,
            'table' => $table,
            'body' => $body,
            'ip' => $request->ip(),
        ]);

        if ($table === 'ATTLOG' && ! empty($body)) {
            $this->parseAttendanceLogs($body, $sn, $request->ip());
        }

        // Tangkap data user/karyawan dari mesin
        if (($table === 'USERINFO' || $table === 'OPERLOG') && ! empty($body)) {
            $this->parseUserInfo($body, $sn);
        }

        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    public function getrequest(Request $request)
    {
        $sn = $request->query('SN');

        // 1. Cek antrian perintah injek user ke mesin
        $queueFile = storage_path('app/adms_commands.json');
        if (file_exists($queueFile)) {
            $queue = json_decode(file_get_contents($queueFile), true) ?: [];
            if (! empty($queue)) {
                // Ambil hingga 5 perintah per heartbeat agar cepat tapi tidak overload buffer mesin
                $commandsToSend = array_splice($queue, 0, 5);
                file_put_contents($queueFile, json_encode($queue, JSON_PRETTY_PRINT));

                $responseBody = '';
                $cmdId = rand(100, 999);
                foreach ($commandsToSend as $idx => $cmd) {
                    $responseBody .= "C:" . ($cmdId + $idx) . ":" . trim($cmd) . "\n";
                }

                Log::channel('adms')->info("Injek ke Mesin [SN: {$sn}]:\n" . $responseBody);
                return response($responseBody, 200)->header('Content-Type', 'text/plain');
            }
        }

        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    public function ping(Request $request)
    {
        Log::channel('adms')->info('ADMS PING', [
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);
        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    private function parseUserInfo($body, $sn)
    {
        $lines = explode("\n", str_replace("\r", '', trim($body)));

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || (! str_contains($line, 'PIN=') && ! str_contains($line, 'Pin='))) {
                continue;
            }

            // Format: USER PIN=6\tName=Dewi\tPri=14...
            $pin = null;
            $name = null;

            $parts = preg_split('/[\t\s]+/', $line);
            foreach ($parts as $part) {
                if (stripos($part, 'PIN=') === 0) {
                    $pin = substr($part, 4);
                } elseif (stripos($part, 'Name=') === 0) {
                    $name = substr($part, 5);
                }
            }

            if (! empty($pin)) {
                try {
                    $emp = Employee::where('employee_id', $pin)
                        ->orWhere('id', $pin)
                        ->first();

                    $displayName = ! empty($name) ? trim($name) : 'User ' . $pin;

                    if ($emp) {
                        if (! empty($name) && $emp->name !== $name) {
                            $emp->update(['name' => $displayName]);
                            Log::channel('adms')->info("Updated employee from machine: [PIN: {$pin}] -> Name: {$displayName}");
                        }
                    } else {
                        Employee::create([
                            'employee_id' => $pin,
                            'name' => $displayName,
                            'status' => 'active',
                            'salary' => 0,
                            'payment_method' => 'transfer',
                        ]);
                        Log::channel('adms')->info("Auto-created new employee from machine: [PIN: {$pin}] -> Name: {$displayName}");
                    }
                } catch (\Exception $e) {
                    Log::channel('adms')->error("Failed to sync userinfo PIN {$pin}: " . $e->getMessage());
                }
            }
        }
    }

    private function parseAttendanceLogs($body, $sn, $ip)
    {
        $lines = explode("\n", str_replace("\r", '', trim($body)));

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $parts = explode("\t", trim($line));

            if (count($parts) >= 2) {
                $userId = $parts[0];
                $scanTime = $parts[1];
                $status = isset($parts[2]) ? $parts[2] : '0';
                $verify = isset($parts[3]) ? $parts[3] : '0';
                $workCode = isset($parts[4]) ? $parts[4] : '';

                try {
                    AttendanceLog::create([
                        'machine_sn' => $sn ?? 'UNKNOWN',
                        'user_id' => $userId,
                        'scan_time' => $scanTime,
                        'status' => $status,
                        'raw_data' => [
                            'full_line' => $line,
                            'verify_mode' => $verify,
                            'work_code' => $workCode,
                        ],
                        'ip_address' => $ip,
                    ]);

                    $this->syncScanToDailyAttendance($userId, $scanTime);
                } catch (\Exception $e) {
                    Log::channel('adms')->error('Failed to save log line: '.$line, [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
    private function syncScanToDailyAttendance(string $userId, string $scanTime): void
    {
        try {
            $scanDate = \Carbon\Carbon::parse($scanTime);
            $dateStr = $scanDate->toDateString();

            $employee = Employee::where('employee_id', $userId)
                ->orWhere('id', $userId)
                ->orWhere('employee_id', 'EMP' . str_pad($userId, 5, '0', STR_PAD_LEFT))
                ->first();

            if (! $employee) {
                $digits = preg_replace('/\\D/', '', $userId);
                if ($digits !== '') {
                    $cleanDigits = ltrim($digits, '0') ?: '0';
                    $employee = Employee::where('employee_id', $cleanDigits)
                        ->orWhere('id', $cleanDigits)
                        ->first();
                }
            }

            if (! $employee) {
                return;
            }

            $permits = \App\Models\Permit::where('employee_id', $employee->id)
                ->whereDate('permit_date', $dateStr)
                ->get();

            $record = \App\Models\DailyAttendance::where('date', $dateStr)
                ->where('employee_id', $employee->id)
                ->first();

            $attendanceService = app(\App\Services\AttendanceProcessingService::class);
            $dayData = $attendanceService->processDay($employee, $scanDate);
            $summary = \App\Models\Permit::summaryFor($permits);
            $checkLocks = $dayData['check_locks'];

            if (! $record) {
                \App\Models\DailyAttendance::create([
                    'date' => $dateStr,
                    'employee_id' => $employee->id,
                    'check_in' => $checkLocks['check_in'] ? $checkLocks['check_in']['scan_time']->format('H:i:s') : null,
                    'break_out' => $checkLocks['break_out'] ? $checkLocks['break_out']['scan_time']->format('H:i:s') : null,
                    'break_in' => $checkLocks['break_in'] ? $checkLocks['break_in']['scan_time']->format('H:i:s') : null,
                    'check_out' => $checkLocks['check_out'] ? $checkLocks['check_out']['scan_time']->format('H:i:s') : null,
                    'late_check_in_minutes' => $dayData['late_minutes'],
                    'late_break_in_minutes' => $dayData['late_break_in_minutes'],
                    'overtime_minutes' => $dayData['overtime_minutes'],
                    'is_anomaly' => $dayData['is_anomaly'],
                    'anomaly_reason' => $dayData['anomaly_reason'],
                    'keterangan' => $permits->isNotEmpty()
                        ? 'izin'
                        : ($dayData['present'] ? 'hadir' : ($scanDate->isWeekend() ? 'libur' : 'alpha')),
                    'nominal_izin' => $summary['nominal'],
                    'tipe_nominal_izin' => in_array($summary['tipe'], ['potong_gaji', 'tambah_gaji']) ? $summary['tipe'] : null,
                    'is_fixed' => false,
                ]);
            } elseif (! $record->is_fixed) {
                $edited = is_array($record->edited_fields) ? $record->edited_fields : [];
                $updateData = [];

                if (empty($edited['check_in'])) {
                    $updateData['check_in'] = $checkLocks['check_in'] ? $checkLocks['check_in']['scan_time']->format('H:i:s') : null;
                }
                if (empty($edited['break_out'])) {
                    $updateData['break_out'] = $checkLocks['break_out'] ? $checkLocks['break_out']['scan_time']->format('H:i:s') : null;
                }
                if (empty($edited['break_in'])) {
                    $updateData['break_in'] = $checkLocks['break_in'] ? $checkLocks['break_in']['scan_time']->format('H:i:s') : null;
                }
                if (empty($edited['check_out'])) {
                    $updateData['check_out'] = $checkLocks['check_out'] ? $checkLocks['check_out']['scan_time']->format('H:i:s') : null;
                }

                $updateData['late_check_in_minutes'] = $dayData['late_minutes'];
                $updateData['late_break_in_minutes'] = $dayData['late_break_in_minutes'];
                $updateData['overtime_minutes'] = $dayData['overtime_minutes'];
                $updateData['is_anomaly'] = $dayData['is_anomaly'];
                $updateData['anomaly_reason'] = $dayData['anomaly_reason'];

                if ($permits->isNotEmpty()) {
                    $updateData['keterangan'] = 'izin';
                    $updateData['nominal_izin'] = $summary['nominal'];
                    $updateData['tipe_nominal_izin'] = in_array($summary['tipe'], ['potong_gaji', 'tambah_gaji']) ? $summary['tipe'] : null;
                } else {
                    $updateData['keterangan'] = $dayData['present'] ? 'hadir' : ($scanDate->isWeekend() ? 'libur' : 'alpha');
                }

                $record->update($updateData);
            }
        } catch (\Exception $e) {
            Log::channel('adms')->error("Failed to auto sync scan to DailyAttendance: " . $e->getMessage());
        }
    }
}
