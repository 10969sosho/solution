<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permit extends Model
{
    public const TYPE_NO_DEDUCTION = 'no_deduction';

    public const TYPE_SALARY_DEDUCTION = 'salary_deduction';

    protected $fillable = [
        'employee_id',
        'category',
        'location',
        'position',
        'permit_date',
        'type',
        'start_time',
        'end_time',
        'duration_minutes',
        'reason',
        'status',
        'deduction_type',
        'deduction_hours',
        'deduction_minutes',
    ];

    protected $casts = [
        'permit_date' => 'date',
        'duration_minutes' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}