<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ApplyPayrollMasterRevisions extends Command
{
    protected $signature = 'payroll:apply-revisions';
    protected $description = 'Menerapkan master data karyawan, jam kerja, jatah libur, dan potongan keterlambatan baru';

    public function handle()
    {
        $this->info("1. Memperbarui tabel jabatans & golongans...");

        // Pastikan Jabatan 'Teknisi' dan 'Jaga Malam' ada
        $jabatanTeknisi = DB::table('jabatans')->where('name', 'Teknisi')->first();
        if (! $jabatanTeknisi) {
            $jabatanTeknisiId = DB::table('jabatans')->insertGetId([
                'name' => 'Teknisi',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $jabatanTeknisiId = $jabatanTeknisi->id;
        }

        $jabatanJagaMalam = DB::table('jabatans')->where('name', 'Jaga Malam')->first();
        if (! $jabatanJagaMalam) {
            $jabatanJagaMalamId = DB::table('jabatans')->insertGetId([
                'name' => 'Jaga Malam',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $jabatanJagaMalamId = $jabatanJagaMalam->id;
        }

        $jabatanMandorId = DB::table('jabatans')->where('name', 'Mandor')->value('id') ?? 4;
        $jabatanAdminId = DB::table('jabatans')->where('name', 'Admin')->value('id') ?? 6;
        $jabatanGudangId = DB::table('jabatans')->where('name', 'Anak Gudang')->value('id') ?? 5;

        $golonganAdminId = 1;
        $golonganMandorId = 2;
        $golonganGudangKandangId = 3;

        $this->info("2. Memperbarui tabel potongan_terlamats...");
        // Hapus potongan lama dan masukkan skema baru persis sesuai instruksi
        DB::table('potongan_terlamats')->truncate();

        $potonganGudangKandang = [
            ['min' => 0, 'max' => 30, 'amt' => 0],
            ['min' => 31, 'max' => 45, 'amt' => 10000],
            ['min' => 46, 'max' => 60, 'amt' => 15000],
            ['min' => 61, 'max' => 100, 'amt' => 25000],
            ['min' => 101, 'max' => 200, 'amt' => 50000],
            ['min' => 201, 'max' => 9999, 'amt' => 100000],
        ];

        $potonganMandorAdmin = [
            ['min' => 0, 'max' => 30, 'amt' => 0],
            ['min' => 31, 'max' => 45, 'amt' => 50000],
            ['min' => 46, 'max' => 60, 'amt' => 75000],
            ['min' => 61, 'max' => 100, 'amt' => 100000],
            ['min' => 101, 'max' => 200, 'amt' => 150000],
            ['min' => 201, 'max' => 9999, 'amt' => 200000],
        ];

        foreach (['masuk_kerja', 'setelah_istirahat'] as $type) {
            foreach ($potonganGudangKandang as $p) {
                DB::table('potongan_terlamats')->insert([
                    'golongan_id' => $golonganGudangKandangId,
                    'type' => $type,
                    'min_minutes' => $p['min'],
                    'max_minutes' => $p['max'],
                    'amount' => $p['amt'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ([$golonganAdminId, $golonganMandorId] as $gId) {
                foreach ($potonganMandorAdmin as $p) {
                    DB::table('potongan_terlamats')->insert([
                        'golongan_id' => $gId,
                        'type' => $type,
                        'min_minutes' => $p['min'],
                        'max_minutes' => $p['max'],
                        'amount' => $p['amt'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
        $this->info("Berhasil mengonfigurasi aturan keterlambatan Gudang & Kandang serta Mandor & Admin.");

        $this->info("3. Memperbarui tabel work_settings...");
        // 07:00 - 16:00 (Normal 07)
        DB::table('work_settings')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'Shift 07.00 - 16.00',
                'day' => 'Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
                'check_in_time' => '07:00:00',
                'check_out_time' => '16:00:00',
                'break_out_time' => '12:00:00',
                'break_in_time' => '13:00:00',
                'is_active' => 1,
                'updated_at' => now(),
            ]
        );

        // 08:00 - 17:00 (Normal 08)
        DB::table('work_settings')->updateOrInsert(
            ['id' => 2],
            [
                'name' => 'Shift 08.00 - 17.00',
                'day' => 'Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
                'check_in_time' => '08:00:00',
                'check_out_time' => '17:00:00',
                'break_out_time' => '12:00:00',
                'break_in_time' => '13:00:00',
                'is_active' => 1,
                'updated_at' => now(),
            ]
        );

        // 07:30 - 16:30 (Shift Binti, Hana, Adil)
        DB::table('work_settings')->updateOrInsert(
            ['id' => 4],
            [
                'name' => 'Shift 07.30 - 16.30',
                'day' => 'Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
                'check_in_time' => '07:30:00',
                'check_out_time' => '16:30:00',
                'break_out_time' => '12:00:00',
                'break_in_time' => '13:00:00',
                'is_active' => 1,
                'updated_at' => now(),
            ]
        );

        // Jaga Malam (17:00 - 07:00)
        DB::table('work_settings')->updateOrInsert(
            ['id' => 6],
            [
                'name' => 'Shift Jaga Malam 17.00 - 07.00',
                'day' => 'Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
                'check_in_time' => '17:00:00',
                'check_out_time' => '07:00:00',
                'break_out_time' => '00:00:00',
                'break_in_time' => '00:00:00',
                'is_active' => 1,
                'updated_at' => now(),
            ]
        );

        $this->info("4. Memperbarui Master Data Karyawan...");

        // Daftar spesifik karyawan
        $revisions = [
            // Admin & Mandor & Teknisi & Jaga Malam
            ['pin' => '309', 'name' => 'Catur', 'position' => 'Mandor', 'jam' => '07:00:00', 'leave' => 2, 'g' => $golonganMandorId, 'j' => $jabatanMandorId],
            ['pin' => '193', 'name' => 'Tutik BD', 'position' => 'Admin', 'jam' => '08:00:00', 'leave' => 2, 'g' => $golonganAdminId, 'j' => $jabatanAdminId],
            ['pin' => '365', 'name' => 'Khotim', 'position' => 'Admin', 'jam' => '08:00:00', 'leave' => 2, 'g' => $golonganAdminId, 'j' => $jabatanAdminId],
            ['pin' => '362', 'name' => 'Rista', 'position' => 'Admin', 'jam' => '08:00:00', 'leave' => 2, 'g' => $golonganAdminId, 'j' => $jabatanAdminId],
            ['pin' => '314', 'name' => 'Andre', 'position' => 'Admin', 'jam' => '08:00:00', 'leave' => 2, 'g' => $golonganAdminId, 'j' => $jabatanAdminId],
            ['pin' => '377', 'name' => 'Siska', 'position' => 'Admin', 'jam' => '08:00:00', 'leave' => 2, 'g' => $golonganAdminId, 'j' => $jabatanAdminId],
            ['pin' => '311', 'name' => 'Agung', 'position' => 'Mandor', 'jam' => '07:00:00', 'leave' => 2, 'g' => $golonganMandorId, 'j' => $jabatanMandorId],
            ['pin' => '391', 'name' => 'Adi', 'position' => 'Mandor + Jaga Malam', 'jam' => '07:00:00', 'leave' => 2, 'g' => $golonganMandorId, 'j' => $jabatanMandorId, 'night' => true],
            ['pin' => '308', 'name' => 'Yoga', 'position' => 'Mandor', 'jam' => '07:00:00', 'leave' => 2, 'g' => $golonganMandorId, 'j' => $jabatanMandorId],
            ['pin' => '381', 'name' => 'Yudi', 'position' => 'Mandor', 'jam' => '07:00:00', 'leave' => 2, 'g' => $golonganMandorId, 'j' => $jabatanMandorId],
            ['pin' => '325', 'name' => 'Erik', 'position' => 'Mandor + Jaga Malam', 'jam' => '07:00:00', 'leave' => 2, 'g' => $golonganMandorId, 'j' => $jabatanMandorId, 'night' => true],
            ['pin' => '306', 'name' => 'Rio', 'position' => 'Mandor', 'jam' => '07:00:00', 'leave' => 2, 'g' => $golonganMandorId, 'j' => $jabatanMandorId],
            ['pin' => '375', 'name' => 'Ahmad', 'position' => 'Mandor', 'jam' => '07:00:00', 'leave' => 2, 'g' => $golonganMandorId, 'j' => $jabatanMandorId],
            ['pin' => '25',  'name' => 'Ana (Siska Ana)', 'position' => 'Admin', 'jam' => '07:00:00', 'leave' => 2, 'g' => $golonganAdminId, 'j' => $jabatanAdminId],
            ['pin' => '307', 'name' => 'Hendrik', 'position' => 'Mandor', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganMandorId, 'j' => $jabatanMandorId],
            ['pin' => '700', 'name' => 'Binti', 'position' => 'Admin', 'jam' => '07:30:00', 'leave' => 2, 'g' => $golonganAdminId, 'j' => $jabatanAdminId],
            ['pin' => '336', 'name' => 'Hana', 'position' => 'Mandor', 'jam' => '07:30:00', 'leave' => 2, 'g' => $golonganMandorId, 'j' => $jabatanMandorId],
            ['pin' => '326', 'name' => 'Adil', 'position' => 'Mandor', 'jam' => '07:30:00', 'leave' => 2, 'g' => $golonganMandorId, 'j' => $jabatanMandorId],
            ['pin' => '280', 'name' => 'Reyhan', 'position' => 'Keluar', 'jam' => null, 'leave' => 0, 'g' => null, 'j' => null, 'resigned' => true],
            ['pin' => '277', 'name' => 'Dimas', 'position' => 'Teknisi', 'jam' => '07:00:00', 'leave' => 2, 'g' => $golonganMandorId, 'j' => $jabatanTeknisiId],
            ['pin' => '345', 'name' => 'Munib', 'position' => 'Teknisi', 'jam' => '07:00:00', 'leave' => 2, 'g' => $golonganMandorId, 'j' => $jabatanTeknisiId],
            ['pin' => '347', 'name' => 'Indra', 'position' => 'Teknisi', 'jam' => '07:00:00', 'leave' => 2, 'g' => $golonganMandorId, 'j' => $jabatanTeknisiId],
            ['pin' => '348', 'name' => 'Risky', 'position' => 'Teknisi', 'jam' => '07:00:00', 'leave' => 2, 'g' => $golonganMandorId, 'j' => $jabatanTeknisiId],
            ['pin' => '350', 'name' => 'Pak Gianto', 'position' => 'Jaga Malam', 'jam' => '17:00:00', 'leave' => 0, 'g' => $golonganGudangKandangId, 'j' => $jabatanJagaMalamId, 'night' => true],

            // 42 Karyawan Gudang / Kandang
            ['pin' => '349', 'name' => 'Bambang S.', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '368', 'name' => 'Khoiri', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '335', 'name' => 'Imam', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '304', 'name' => 'Andre', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '346', 'name' => 'Kawid', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '354', 'name' => 'Rendy', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '376', 'name' => 'Dafik', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '320', 'name' => 'Jamil', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '328', 'name' => 'Ahmad', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '329', 'name' => 'Lukman', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '331', 'name' => 'Farel', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '332', 'name' => 'Ilham', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '319', 'name' => 'Ana', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '388', 'name' => 'Anaja', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '367', 'name' => 'Jeffi', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '333', 'name' => 'Bu Yam', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '358', 'name' => 'Bu Tun', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '399', 'name' => 'Bagus', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '330', 'name' => 'Gibran', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '357', 'name' => 'Bu Preh', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '356', 'name' => 'Adit', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '322', 'name' => 'Risky K', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '383', 'name' => 'Bu Janah', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '385', 'name' => 'Zainal', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '360', 'name' => 'Risky 2', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '303', 'name' => 'Bu Nur', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '384', 'name' => 'Singgih', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '343', 'name' => 'Purnomo', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '324', 'name' => 'Kevin', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '355', 'name' => 'Merry', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '327', 'name' => 'Wahyu', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '301', 'name' => 'Dwi', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '302', 'name' => 'Putra', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '321', 'name' => 'Bayu', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '380', 'name' => 'Reza', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '334', 'name' => 'Rengga', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '379', 'name' => 'Wulan Telur', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '313', 'name' => 'Nurul Telur', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '312', 'name' => 'Jibral', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '317', 'name' => 'Paijo', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],
            ['pin' => '398', 'name' => 'P. Naam', 'position' => 'Gudang / Kandang', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganGudangKandangId, 'j' => $jabatanGudangId],

            // Nomor 42: Hendro (Teknisi Baru)
            ['pin' => '801', 'name' => 'Hendro', 'position' => 'Teknisi', 'jam' => '07:00:00', 'leave' => 1, 'g' => $golonganMandorId, 'j' => $jabatanTeknisiId],
        ];

        foreach ($revisions as $rev) {
            $isResigned = ! empty($rev['resigned']);
            $isNight = ! empty($rev['night']);

            $emp = Employee::where('employee_id', $rev['pin'])->first();

            $payload = [
                'name' => $rev['name'],
                'position' => $rev['position'],
                'department' => in_array($rev['position'], ['Admin', 'Mandor', 'Mandor + Jaga Malam', 'Jaga Malam']) ? $rev['position'] : ($rev['position'] === 'Teknisi' ? 'Teknisi' : 'Gudang / Kandang'),
                'jam_masuk_normal' => $rev['jam'],
                'leave_quota' => $rev['leave'],
                'golongan_id' => $rev['g'],
                'jabatan_id' => $rev['j'],
                'status' => $isResigned ? 'resigned' : 'active',
                'is_night_guard' => $isNight,
                'payment_method' => 'transfer',
            ];

            if ($emp) {
                $emp->update($payload);
            } else {
                $payload['employee_id'] = $rev['pin'];
                $payload['salary'] = 0;
                Employee::create($payload);
            }
        }

        $this->info("Berhasil memperbarui master data seluruh karyawan!");
        return 0;
    }
}
