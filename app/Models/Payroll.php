<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $fillable = [
        'employee_id',
        'period_year',
        'period_month',
        'base_salary',
        'late_deduction',
        'loan_deduction',
        'absence_deduction',
        'total_deduction',
        'attendance_bonus',
        'total_incentive',
        'net_salary',
        'breakdown',
        'status',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'late_deduction' => 'decimal:2',
        'loan_deduction' => 'decimal:2',
        'absence_deduction' => 'decimal:2',
        'total_deduction' => 'decimal:2',
        'attendance_bonus' => 'decimal:2',
        'total_incentive' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'breakdown' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}