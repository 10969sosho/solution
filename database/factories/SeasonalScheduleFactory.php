<?php

namespace Database\Factories;

use App\Models\SeasonalSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeasonalScheduleFactory extends Factory
{
    protected $model = SeasonalSchedule::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Puasa Ramadan', 'Lebaran', 'Natal', 'Tahun Baru',
            ]),
            'start_date' => fake()->dateTimeBetween('-6 months', '+1 month'),
            'end_date' => fake()->dateTimeBetween('+1 month', '+3 months'),
            'check_in_delta_minutes' => fake()->randomElement([-30, -60, 0]),
            'check_out_delta_minutes' => fake()->randomElement([-30, 0, 60]),
            'force_check_in_time' => fake()->randomElement(['06:00:00', '07:00:00', null]),
            'is_active' => fake()->boolean(70),
            'description' => fake()->sentence(),
        ];
    }
}
