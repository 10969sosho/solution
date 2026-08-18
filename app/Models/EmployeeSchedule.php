<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSchedule extends Model
{
    protected $fillable = [
        'employee_id',
        'name',
        'check_in_time',
        'break_out_time',
        'break_in_time',
        'check_out_time',
        'late_tolerance_minutes',
        'effective_from',
        'is_active',
        'description',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'is_active' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}