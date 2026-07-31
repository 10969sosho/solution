<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Log incoming request
            Log::channel('adms')->info('ADMS Request Received', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'headers' => $request->headers->all(),
                'payload' => $request->all(),
            ]);

            // Validasi input
            $validator = Validator::make($request->all(), [
                'machine_sn' => 'required|string|max:50',
                'user_id' => 'required|string|max:50',
                'scan_time' => 'required|date',
                'status' => 'required|string|max:20',
                'raw_data' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                Log::channel('adms')->warning('Validation failed', [
                    'errors' => $validator->errors(),
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Simpan data attendance
            $attendanceLog = AttendanceLog::create([
                'machine_sn' => $request->machine_sn,
                'user_id' => $request->user_id,
                'scan_time' => $request->scan_time,
                'status' => $request->status,
                'raw_data' => $request->raw_data ?? [],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            Log::channel('adms')->info('Attendance log saved successfully', [
                'id' => $attendanceLog->id,
                'machine_sn' => $attendanceLog->machine_sn,
                'user_id' => $attendanceLog->user_id,
                'scan_time' => $attendanceLog->scan_time,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Attendance data received successfully',
                'data' => [
                    'id' => $attendanceLog->id,
                    'machine_sn' => $attendanceLog->machine_sn,
                    'user_id' => $attendanceLog->user_id,
                    'scan_time' => $attendanceLog->scan_time,
                    'status' => $attendanceLog->status,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::channel('adms')->error('Error processing attendance data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function latest(Request $request)
    {
        try {
            $query = AttendanceLog::query();

            // Filter berdasarkan tanggal
            if ($request->has('date')) {
                $date = $request->date;
                $query->whereDate('scan_time', $date);
            }

            // Filter berdasarkan user_id
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Filter berdasarkan machine_sn
            if ($request->has('machine_sn')) {
                $query->where('machine_sn', $request->machine_sn);
            }

            $attendanceLogs = $query->orderBy('scan_time', 'desc')
                ->paginate($request->get('per_page', 50));

            return response()->json([
                'status' => 'success',
                'data' => $attendanceLogs,
            ]);

        } catch (\Exception $e) {
            Log::channel('adms')->error('Error fetching attendance logs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
