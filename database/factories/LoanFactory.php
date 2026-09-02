<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        $status = fake()->randomElement(['active', 'active', 'paid']);

        return [
            'employee_id' => Employee::factory(),
            'loan_date' => fake()->dateTimeBetween('-6 months', '-1 month'),
            'principal' => fake()->randomFloat(2, 500000, 5000000),
            'description' => fake()->randomElement([
                'Pinjaman Karyawan', 'Emergency Loan', 'Medical Loan',
                'Education Loan', 'Kebutuhan Mendadak',
            ]),
            'status' => $status,
            'previous_loans_total' => fake()->randomFloat(2, 0, 2000000),
            'all_loans_total' => fake()->randomFloat(2, 500000, 7000000),
        ];
    }
}
