<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'employee_id',
        'name',
        'position',
        'department',
        'location',
        'phone',
        'email',
        'join_date',
        'status',
        'salary',
        'salary_tier',
        'address',
    ];

    protected $casts = [
        'join_date' => 'date',
        'salary' => 'decimal:2',
    ];

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class, 'user_id', 'employee_id');
    }

    public function schedules()
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function permits()
    {
        return $this->hasMany(Permit::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function activeSchedule()
    {
        return $this->hasOne(EmployeeSchedule::class)->where('is_active', true)->latest('effective_from');
    }
}
