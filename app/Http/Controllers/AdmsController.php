<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
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

        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    public function getrequest(Request $request)
    {
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
