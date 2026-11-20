<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncOfficialMasterEmployees extends Command
{
    protected $signature = 'payroll:sync-official-master';
    protected $description = 'Sinkronisasi 98 Master Karyawan resmi (ID/PIN, Nama, Jabatan, Golongan, Status)';

    public function handle()
    {
        $this->info("Menyiapkan pemetaan Jabatan & Golongan...");

        // Pastikan Jabatan ada
        $jabatans = [
            'Admin' => 6,
            'Mandor' => 4,
            'Anak Gudang' => 5,
            'Anak kandang' => 7,
            'Teknisi' => DB::table('jabatans')->where('name', 'Teknisi')->value('id'),
            'Jaga Malam' => DB::table('jabatans')->where('name', 'Jaga Malam')->value('id'),
        ];

        // Pastikan Golongan ada
        $golongans = [
            'Admin' => 1,
            'Mandor' => 2,
            'Gudang dan Kandang' => 3,
        ];

        // 98 Data Resmi dari User
        $raw = [
            ['id' => '1', 'name' => 'Admin', 'jabatan' => 'Admin', 'golongan' => 'Admin', 'status' => 'Active'],
            ['id' => '193', 'name' => 'Tutik BD', 'jabatan' => 'Admin', 'golongan' => 'Admin', 'status' => 'Active'],
            ['id' => '25', 'name' => 'Ana (Siska Ana)', 'jabatan' => 'Admin', 'golongan' => 'Admin', 'status' => 'Active'],
            ['id' => '257', 'name' => 'Suprih', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '277', 'name' => 'Dimas', 'jabatan' => 'Teknisi', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '280', 'name' => 'Reyhan', 'jabatan' => '-', 'golongan' => '-', 'status' => 'Resigned'],
            ['id' => '293', 'name' => 'Yuli', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '300', 'name' => 'dewi', 'jabatan' => 'Admin', 'golongan' => 'Admin', 'status' => 'Active'],
            ['id' => '301', 'name' => 'Dwi', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '302', 'name' => 'Putra', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '303', 'name' => 'Bu Nur', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '304', 'name' => 'Andre', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '305', 'name' => 'DIO', 'jabatan' => 'Mandor', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '306', 'name' => 'Rio', 'jabatan' => 'Mandor', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '307', 'name' => 'Hendrik', 'jabatan' => 'Mandor', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '308', 'name' => 'Yoga', 'jabatan' => 'Mandor', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '309', 'name' => 'Catur', 'jabatan' => 'Mandor', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '310', 'name' => 'WahyuKd', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '311', 'name' => 'Agung', 'jabatan' => 'Mandor', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '312', 'name' => 'Jibral', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '313', 'name' => 'Nurul Telur', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '314', 'name' => 'Andre', 'jabatan' => 'Admin', 'golongan' => 'Admin', 'status' => 'Active'],
            ['id' => '315', 'name' => 'vania', 'jabatan' => 'Admin', 'golongan' => 'Admin', 'status' => 'Active'],
            ['id' => '316', 'name' => 'IrulKd', 'jabatan' => '-', 'golongan' => '-', 'status' => 'Resigned'],
            ['id' => '317', 'name' => 'Paijo', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '318', 'name' => 'Alex', 'jabatan' => '-', 'golongan' => '-', 'status' => 'Active'],
            ['id' => '319', 'name' => 'Ana', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '320', 'name' => 'Jamil', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '321', 'name' => 'Bayu', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '322', 'name' => 'Risky K', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '323', 'name' => 'ErutanTek', 'jabatan' => '-', 'golongan' => '-', 'status' => 'Resigned'],
            ['id' => '324', 'name' => 'Kevin', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '325', 'name' => 'Erik', 'jabatan' => 'Mandor', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '326', 'name' => 'Adil', 'jabatan' => 'Mandor', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '327', 'name' => 'Wahyu', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '328', 'name' => 'Ahmad', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '329', 'name' => 'Lukman', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '330', 'name' => 'Gibran', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '331', 'name' => 'Farel', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '332', 'name' => 'Ilham', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '333', 'name' => 'Bu Yam', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '334', 'name' => 'Rengga', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '335', 'name' => 'Imam', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '336', 'name' => 'Hana', 'jabatan' => 'Mandor', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '337', 'name' => 'UdinMdr', 'jabatan' => 'Mandor', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '338', 'name' => 'FeriKd', 'jabatan' => '-', 'golongan' => '-', 'status' => 'Resigned'],
            ['id' => '339', 'name' => 'ArifinKd', 'jabatan' => '-', 'golongan' => '-', 'status' => 'Resigned'],
            ['id' => '341', 'name' => 'DILLA', 'jabatan' => '-', 'golongan' => '-', 'status' => 'Resigned'],
            ['id' => '343', 'name' => 'Purnomo', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '345', 'name' => 'Munib', 'jabatan' => 'Teknisi', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '346', 'name' => 'Kawid', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '347', 'name' => 'Indra', 'jabatan' => 'Teknisi', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '348', 'name' => 'Risky', 'jabatan' => 'Teknisi', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '349', 'name' => 'Bambang S.', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '350', 'name' => 'Pak Gianto', 'jabatan' => 'Jaga Malam', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '353', 'name' => 'Yunus', 'jabatan' => 'Mandor', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '354', 'name' => 'Rendy', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '355', 'name' => 'Merry', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '356', 'name' => 'Adit', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '357', 'name' => 'Bu Preh', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '358', 'name' => 'Bu Tun', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '360', 'name' => 'Risky 2', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '362', 'name' => 'Rista', 'jabatan' => 'Admin', 'golongan' => 'Admin', 'status' => 'Active'],
            ['id' => '364', 'name' => 'ImamKd', 'jabatan' => '-', 'golongan' => '-', 'status' => 'Resigned'],
            ['id' => '365', 'name' => 'Khotim', 'jabatan' => 'Admin', 'golongan' => 'Admin', 'status' => 'Active'],
            ['id' => '367', 'name' => 'Jeffi', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '368', 'name' => 'Khoiri', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '370', 'name' => 'Nety', 'jabatan' => 'Admin', 'golongan' => 'Admin', 'status' => 'Active'],
            ['id' => '372', 'name' => 'Juni', 'jabatan' => 'Mandor', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '373', 'name' => 'ZAKARIA', 'jabatan' => 'Mandor', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '374', 'name' => 'KunyelKd', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '375', 'name' => 'Ahmad', 'jabatan' => 'Mandor', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '376', 'name' => 'Dafik', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '377', 'name' => 'Siska', 'jabatan' => 'Admin', 'golongan' => 'Admin', 'status' => 'Active'],
            ['id' => '379', 'name' => 'Wulan Telur', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '380', 'name' => 'Reza', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '381', 'name' => 'Yudi', 'jabatan' => 'Mandor', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '382', 'name' => 'AbilGd', 'jabatan' => '-', 'golongan' => '-', 'status' => 'Resigned'],
            ['id' => '383', 'name' => 'Bu Janah', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '384', 'name' => 'Singgih', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '385', 'name' => 'Zainal', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '388', 'name' => 'Anaja', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '391', 'name' => 'Adi', 'jabatan' => 'Mandor', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '392', 'name' => 'DafiPkl', 'jabatan' => '-', 'golongan' => '-', 'status' => 'Resigned'],
            ['id' => '393', 'name' => 'IchaPkl', 'jabatan' => '-', 'golongan' => '-', 'status' => 'Resigned'],
            ['id' => '396', 'name' => 'DerryAss', 'jabatan' => 'Mandor', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '398', 'name' => 'P. Naam', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '399', 'name' => 'Bagus', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '424', 'name' => 'Diana', 'jabatan' => 'Anak Gudang', 'golongan' => 'Gudang dan Kandang', 'status' => 'Active'],
            ['id' => '425', 'name' => 'Kolis', 'jabatan' => 'Mandor', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '430', 'name' => 'Ar', 'jabatan' => 'Mandor', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '509', 'name' => 'Imam', 'jabatan' => 'Mandor', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '512', 'name' => 'Joko', 'jabatan' => 'Mandor', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '514', 'name' => 'BayuBd', 'jabatan' => '-', 'golongan' => '-', 'status' => 'Resigned'],
            ['id' => '700', 'name' => 'Binti', 'jabatan' => 'Admin', 'golongan' => 'Admin', 'status' => 'Active'],
            ['id' => '702', 'name' => 'YusufSr', 'jabatan' => 'Mandor', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '801', 'name' => 'Hendro', 'jabatan' => 'Teknisi', 'golongan' => 'Mandor', 'status' => 'Active'],
            ['id' => '83', 'name' => 'Fitria', 'jabatan' => 'Admin', 'golongan' => 'Admin', 'status' => 'Active'],
        ];

        $updatedCount = 0;
        $createdCount = 0;

        foreach ($raw as $item) {
            $pin = (string) $item['id'];
            $name = trim($item['name']);
            $jabatanName = trim($item['jabatan']);
            $golonganName = trim($item['golongan']);
            $status = strtolower(trim($item['status'])) === 'resigned' ? 'resigned' : 'active';

            $jabatanId = $jabatans[$jabatanName] ?? null;
            $golonganId = $golongans[$golonganName] ?? null;

            // Aturan jam masuk & leave quota jika tidak resigned
            $jamMasuk = null;
            $leaveQuota = 1;
            if ($status === 'active') {
                if (in_array($pin, ['193', '365', '362', '314', '377', '300', '315', '370', '83'])) {
                    // Admin 08.00-17.00
                    $jamMasuk = '08:00:00';
                    $leaveQuota = 2;
                } elseif (in_array($pin, ['700', '336', '326'])) {
                    // Shift 07.30
                    $jamMasuk = '07:30:00';
                    $leaveQuota = 2;
                } elseif ($pin === '350') {
                    // Pak Gianto Jaga Malam
                    $jamMasuk = '17:00:00';
                    $leaveQuota = 0;
                } elseif ($golonganName === 'Mandor' || $jabatanName === 'Teknisi') {
                    $jamMasuk = '07:00:00';
                    $leaveQuota = ($pin === '307' || $pin === '801') ? 1 : 2;
                } else {
                    // Gudang / Kandang
                    $jamMasuk = '07:00:00';
                    $leaveQuota = 1;
                }
            } else {
                $leaveQuota = 0;
            }

            $isNightGuard = in_array($pin, ['391', '325', '350']);

            $emp = Employee::where('employee_id', $pin)->first();

            $payload = [
                'name' => $name,
                'position' => $jabatanName !== '-' ? $jabatanName : null,
                'department' => $jabatanName !== '-' ? $jabatanName : null,
                'jabatan_id' => $jabatanId,
                'golongan_id' => $golonganId,
                'status' => $status,
                'jam_masuk_normal' => $jamMasuk,
                'leave_quota' => $leaveQuota,
                'is_night_guard' => $isNightGuard,
                'payment_method' => 'transfer',
            ];

            if ($emp) {
                $emp->update($payload);
                $updatedCount++;
            } else {
                $payload['employee_id'] = $pin;
                $payload['salary'] = 0;
                Employee::create($payload);
                $createdCount++;
            }
        }

        $this->info("Sukses! Total {$updatedCount} karyawan diperbarui, {$createdCount} karyawan baru dibuat.");
        return 0;
    }
}
