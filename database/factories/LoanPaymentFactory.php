<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Loan;
use App\Models\LoanPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanPaymentFactory extends Factory
{
    protected $model = LoanPayment::class;

    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'employee_id' => Employee::factory(),
            'payment_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'amount' => fake()->randomFloat(2, 100000, 1000000),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
