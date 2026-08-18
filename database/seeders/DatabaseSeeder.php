<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkSetting;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Default akun Super Admin / Owner
        User::firstOrCreate(
            ['email' => 'admin@adms.test'],
            [
                'name' => 'Owner',
                'password' => bcrypt('password'),
                'role' => 'super_admin',
            ]
        );

        // Seed work settings
        WorkSetting::create([
            'name' => 'Default',
            'check_in_time' => '08:00:00',
            'check_out_time' => '17:00:00',
            'late_tolerance_minutes' => 15,
            'overtime_threshold_minutes' => 30,
            'is_active' => true,
            'description' => 'Jam kerja standar',
        ]);

        // Seed sample employees
        $employees = [
            ['employee_id' => '1', 'name' => 'Tian', 'position' => 'Staff', 'department' => 'IT'],
            ['employee_id' => '2', 'name' => 'John', 'position' => 'Staff', 'department' => 'HR'],
            ['employee_id' => '3', 'name' => 'Alice', 'position' => 'Manager', 'department' => 'Finance'],
            ['employee_id' => '4', 'name' => 'Bob', 'position' => 'Staff', 'department' => 'Marketing'],
            ['employee_id' => '5', 'name' => 'Charlie', 'position' => 'Staff', 'department' => 'Operations'],
        ];

        foreach ($employees as $emp) {
            Employee::create($emp);
        }
    }
}
