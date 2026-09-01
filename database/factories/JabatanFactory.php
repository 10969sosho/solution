<?php

namespace Database\Factories;

use App\Models\Jabatan;
use Illuminate\Database\Eloquent\Factories\Factory;

class JabatanFactory extends Factory
{
    protected $model = Jabatan::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Staff', 'Senior Staff', 'Supervisor',
                'Manager', 'Senior Manager', 'Director',
                'Admin', 'Operator', 'Technician',
            ]),
        ];
    }
}
