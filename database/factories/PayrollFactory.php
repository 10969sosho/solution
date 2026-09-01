<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayrollFactory extends Factory
{
    protected $model = Payroll::class;

    public function definition(): array
    {
        $baseSalary = fake()->randomFloat(2, 3000000, 7000000);
        $lateDeduction = fake()->randomFloat(2, 0, 200000);
        $loanDeduction = fake()->randomFloat(2, 0, 500000);
        $absenceDeduction = fake()->randomFloat(2, 0, 300000);
        $totalDeduction = $lateDeduction + $loanDeduction + $absenceDeduction;
        $attendanceBonus = fake()->randomFloat(2, 0, 200000);
        $totalIncentive = $attendanceBonus;
        $netSalary = $baseSalary - $totalDeduction + $totalIncentive;

        return [
            'employee_id' => Employee::factory(),
            'period_year' => (int) now()->year,
            'period_month' => (int) now()->month,
            'base_salary' => $baseSalary,
            'late_deduction' => $lateDeduction,
            'loan_deduction' => $loanDeduction,
            'absence_deduction' => $absenceDeduction,
            'total_deduction' => $totalDeduction,
            'attendance_bonus' => $attendanceBonus,
            'total_incentive' => $totalIncentive,
            'net_salary' => $netSalary,
            'breakdown' => [
                'base_salary' => $baseSalary,
                'late_deduction' => $lateDeduction,
                'loan_deduction' => $loanDeduction,
                'absence_deduction' => $absenceDeduction,
                'attendance_bonus' => $attendanceBonus,
                'net_salary' => $netSalary,
            ],
            'status' => fake()->randomElement(['draft', 'paid']),
        ];
    }
}
