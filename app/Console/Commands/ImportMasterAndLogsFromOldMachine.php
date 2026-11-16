<?php

namespace App\Console\Commands;

use App\Models\AttendanceLog;
use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportMasterAndLogsFromOldMachine extends Command
{
    protected $signature = 'payroll:import-json-master {--clear : Clear old employees and logs before importing}';
    protected $description = 'Import master karyawan dan log absensi dari JSON lama';

    public function handle()
    {
        $usersPath = storage_path('app/extracted_users.json');
        $logsPath = storage_path('app/extracted_attlogs.json');

        if (! file_exists($usersPath) || ! file_exists($logsPath)) {
            $this->error('File extracted_users.json atau extracted_attlogs.json tidak ditemukan di storage/app/');
            return 1;
        }

        $users = json_decode(file_get_contents($usersPath), true);
        $attlogs = json_decode(file_get_contents($logsPath), true);

        $this->info("Menemukan " . count($users) . " users dan " . count($attlogs) . " logs.");

        if ($this->option('clear')) {
            $this->warn('Membersihkan data employees dan attendance_logs lama...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Employee::truncate();
            AttendanceLog::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->info('Data lama berhasil dibersihkan.');
        }

        // 1. Simpan user PIN 1 (Tian) jika belum ada di JSON agar tetap bisa absen
        Employee::updateOrCreate(
            ['employee_id' => '1'],
            [
                'name' => 'Tian',
                'status' => 'active',
                'salary' => 0,
                'payment_method' => 'transfer',
            ]
        );

        // 2. Import 103 User dari JSON
        $importedUsers = 0;
        foreach ($users as $u) {
            $pin = trim((string) $u['pin']);
            $name = trim((string) $u['name']);

            if (empty($pin)) {
                continue;
            }

            Employee::updateOrCreate(
                ['employee_id' => $pin],
                [
                    'name' => ! empty($name) ? $name : 'User ' . $pin,
                    'status' => 'active',
                    'salary' => 0,
                    'payment_method' => 'transfer',
                ]
            );
            $importedUsers++;
        }
        $this->info("Berhasil mengimpor {$importedUsers} master karyawan.");

        // 3. Import 500 Attlogs dari JSON
        $importedLogs = 0;
        foreach ($attlogs as $log) {
            $pin = trim((string) $log['pin']);
            $scanTime = $log['scan_time'];
            $deviceId = $log['device_id'] ?? 'OLD_MACHINE';
            $status = (string) ($log['status'] ?? '0');
            $verify = (string) ($log['verify'] ?? '1');
            $workCode = (string) ($log['work_code'] ?? '0');

            AttendanceLog::create([
                'machine_sn' => $deviceId,
                'user_id' => $pin,
                'scan_time' => $scanTime,
                'status' => $status,
                'raw_data' => [
                    'full_line' => "{$pin}\t{$scanTime}\t{$status}\t{$verify}\t{$workCode}",
                    'verify_mode' => $verify,
                    'work_code' => $workCode,
                ],
                'ip_address' => '127.0.0.1',
            ]);
            $importedLogs++;
        }
        $this->info("Berhasil mengimpor {$importedLogs} log absensi.");

        // 4. Siapkan antrian injek ke mesin via storage/app/adms_queue.json
        $queue = [];
        // Masukkan Tian dan semua user JSON
        $queue[] = "DATA UPDATE USER PIN=1\tName=Tian\tPri=14\tPasswd=\tCard=\tGrp=1\tTZ=0000000100000000\tVerify=0";
        foreach ($users as $u) {
            $pin = trim((string) $u['pin']);
            $name = trim((string) $u['name']);
            $pri = $u['privilege'] ?? 0;
            $card = $u['card'] ?? '';
            $pass = $u['password'] ?? '';
            $queue[] = "DATA UPDATE USER PIN={$pin}\tName={$name}\tPri={$pri}\tPasswd={$pass}\tCard={$card}\tGrp=1\tTZ=0000000100000000\tVerify=0";
        }

        file_put_contents(storage_path('app/adms_commands.json'), json_encode($queue, JSON_PRETTY_PRINT));
        $this->info("Berhasil menyiapkan " . count($queue) . " perintah injek user ke mesin fisik.");

        return 0;
    }
}
