<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // JANGAN JALAN OTOMATIS — data real production dipertahankan.
        // Untuk data dummy jalankan manual:
        //   php artisan db:seed --class=DummyDataSeeder
        //
        // Default akun Super Admin / Owner
        // User::firstOrCreate(
        //     ['email' => 'admin@adms.test'],
        //     [
        //         'name' => 'Owner',
        //         'password' => bcrypt('password'),
        //         'role' => 'super_admin',
        //     ]
        // );
        //
        // Seed work settings
        // WorkSetting::create([...]);
        //
        // Seed sample employees
        // $employees = [...];
        // foreach ($employees as $emp) {
        //     Employee::create($emp);
        // }

        // DummyDataSeeder dijalankan manual, bukan via db:seed default.
        // $this->call(DummyDataSeeder::class);
    }
}
