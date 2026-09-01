<?php

namespace Database\Factories;

use App\Models\Lokasi;
use Illuminate\Database\Eloquent\Factories\Factory;

class LokasiFactory extends Factory
{
    protected $model = Lokasi::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Kantor Pusat Jakarta', 'Cabang Bandung', 'Cabang Surabaya',
                'Cabang Semarang', 'Cabang Medan', 'Cabang Makassar',
                'Cabang Yogyakarta', 'Cabang Bali',
            ]),
        ];
    }
}
