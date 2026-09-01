<?php

namespace Database\Factories;

use App\Models\Golongan;
use App\Models\WorkSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkSettingFactory extends Factory
{
    protected $model = WorkSetting::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'day' => fake()->randomElement([
                'Senin,Selasa,Rabu,Kamis,Jumat',
                'Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
                null,
            ]),
            'golongan_id' => null,
            'check_in_time' => '08:00:00',
            'check_out_time' => '17:00:00',
            'break_out_time' => '12:00:00',
            'break_in_time' => '13:00:00',
            'late_tolerance_minutes' => fake()->randomElement([5, 10, 15]),
            'overtime_threshold_minutes' => fake()->randomElement([30, 60]),
            'is_active' => true,
            'description' => fake()->sentence(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withGolongan(?int $golonganId): static
    {
        return $this->state(fn () => ['golongan_id' => $golonganId]);
    }
}
