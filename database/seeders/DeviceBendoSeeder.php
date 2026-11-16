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

        // 2. Hubungkan Mesin 1 (Bendo) dengan SN fisik aktif NYU7250300070
        Device::updateOrCreate(
            ['serial_number' => 'NYU7250300070'],
            [
                'name' => 'Mesin 1 - Bendo',
                'lokasi_id' => $bendo->id,
                'is_active' => true,
                'notes' => 'Mesin absensi utama Solution X905 - Bendo',
            ]
        );

        // Hapus dummy serial number jika ada
        Device::where('serial_number', 'MESIN-01-BENDO')->delete();
    }
}
