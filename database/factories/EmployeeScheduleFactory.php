<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeScheduleFactory extends Factory
{
    protected $model = EmployeeSchedule::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'name' => 'Jam Kerja Normal',
            'check_in_time' => '08:00:00',
            'break_out_time' => '12:00:00',
            'break_in_time' => '13:00:00',
            'check_out_time' => '17:00:00',
            'late_tolerance_minutes' => 15,
            'effective_from' => fake()->dateTimeBetween('-1 year', '-1 month'),
            'is_active' => true,
            'description' => 'Jam kerja standar Senin-Jumat',
        ];
    }
}
