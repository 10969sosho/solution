<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    protected $fillable = [
        'machine_sn',
        'user_id',
        'scan_time',
        'status',
        'raw_data',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'scan_time' => 'datetime',
        'raw_data' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'user_id', 'employee_id');
    }

    public function getEmployeeNameAttribute()
    {
        return $this->employee ? $this->employee->name : 'Unknown';
    }
}
