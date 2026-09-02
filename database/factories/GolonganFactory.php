<?php

namespace Database\Factories;

use App\Models\Golongan;
use Illuminate\Database\Eloquent\Factories\Factory;

class GolonganFactory extends Factory
{
    protected $model = Golongan::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Golongan I', 'Golongan II', 'Golongan III',
                'Golongan IV', 'Golongan V', 'Golongan VI',
            ]),
        ];
    }
}
