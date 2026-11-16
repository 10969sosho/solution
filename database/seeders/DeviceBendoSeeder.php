<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Lokasi;
use Illuminate\Database\Seeder;

class DeviceBendoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Pastikan lokasi Bendo ada
        $bendo = Lokasi::firstOrCreate(
            ['name' => 'Bendo']
        );

        // 2. Buat Mesin 1 dan hubungkan ke lokasi Bendo
        Device::firstOrCreate(
            ['serial_number' => 'MESIN-01-BENDO'],
            [
                'name' => 'Mesin 1',
                'lokasi_id' => $bendo->id,
                'is_active' => true,
                'notes' => 'Mesin absensi utama Solution X905 - Bendo',
            ]
        );
    }
}
