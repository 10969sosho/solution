<?php

namespace Database\Factories;

use App\Models\AttendanceLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceLogFactory extends Factory
{
    protected $model = AttendanceLog::class;

    public function definition(): array
    {
        $scanHour = $this->faker->numberBetween(7, 17);
        $scanMinute = $this->faker->numberBetween(0, 59);

        return [
            'machine_sn' => fake()->randomElement(['1', '2', '3']),
            'user_id' => fake()->numerify('EMP#####'),
            'scan_time' => fake()->dateTimeBetween('-2 months', 'now'),
            'status' => fake()->randomElement([0, 0, 0, 0, 1, 1, 2, 3]),
            'raw_data' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => 'iClock ZKTeco',
        ];
    }
}
