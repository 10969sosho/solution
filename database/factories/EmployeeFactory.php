<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Golongan;
use App\Models\Jabatan;
use App\Models\Lokasi;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $name = fake()->unique()->name('male');
        $status = fake()->randomElement(['active', 'active', 'active', 'active', 'inactive', 'resigned']);

        return [
            'employee_id' => fake()->unique()->numerify('EMP#####'),
            'name' => $name,
            'position' => fake()->randomElement(['Staff', 'Senior Staff', 'Supervisor', 'Operator', 'Technician']),
            'department' => fake()->randomElement(['IT', 'HR', 'Finance', 'Marketing', 'Operations', 'Logistik', 'Produksi']),
            'location' => fake()->randomElement(['Jakarta', 'Bandung', 'Surabaya', 'Semarang']),
            'golongan_id' => Golongan::factory(),
            'jabatan_id' => Jabatan::factory(),
            'lokasi_id' => Lokasi::factory(),
            'tanggal_keluar' => $status === 'resigned' ? fake()->dateTimeBetween('-6 months', '-1 month') : null,
            'jam_masuk_normal' => '08:00:00',
            'phone' => fake()->unique()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'join_date' => fake()->dateTimeBetween('-2 years', '-6 months'),
            'status' => $status,
            'salary' => fake()->randomFloat(2, 3000000, 7000000),
            'salary_tier' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'address' => fake()->address(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => 'active',
            'tanggal_keluar' => null,
        ]);
    }

    public function resigned(): static
    {
        return $this->state(fn () => [
            'status' => 'resigned',
            'tanggal_keluar' => fake()->dateTimeBetween('-6 months', '-1 month'),
        ]);
    }
}
