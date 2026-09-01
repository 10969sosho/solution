<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Permit;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermitFactory extends Factory
{
    protected $model = Permit::class;

    public function definition(): array
    {
        $type = fake()->randomElement([
            Permit::TYPE_NO_DEDUCTION,
            Permit::TYPE_SALARY_DEDUCTION,
        ]);

        $startTime = fake()->time('H:i');
        $endTime = fake()->time('H:i');
        $duration = fake()->numberBetween(30, 480);

        return [
            'employee_id' => Employee::factory(),
            'location' => fake()->randomElement(['Jakarta', 'Bandung', 'Surabaya']),
            'position' => fake()->randomElement(['Staff', 'Senior Staff', 'Supervisor']),
            'permit_date' => fake()->dateTimeBetween('-2 months', 'now'),
            'type' => $type,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_minutes' => $duration,
            'reason' => fake()->sentence(),
            'status' => fake()->randomElement(['pending', 'approved', 'approved', 'rejected']),
            'deduction_type' => $type === Permit::TYPE_SALARY_DEDUCTION ? fake()->randomElement(['hourly', 'daily']) : null,
            'deduction_hours' => $type === Permit::TYPE_SALARY_DEDUCTION ? fake()->numberBetween(1, 8) : 0,
            'deduction_minutes' => $type === Permit::TYPE_SALARY_DEDUCTION ? fake()->numberBetween(0, 59) : 0,
        ];
    }
}
