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
                } catch (\Exception $e) {
                    Log::channel('adms')->error('Failed to save log line: '.$line, [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
