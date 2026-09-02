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
        'golongan_id',
        'jabatan_id',
        'lokasi_id',
        'gaji_id',
        'tanggal_keluar',
        'jam_masuk_normal',
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
        'tanggal_keluar' => 'date',
        'jam_masuk_normal' => 'string',
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

    public function golongan()
    {
        return $this->belongsTo(Golongan::class);
    }

    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class);
    }

    public function gaji()
    {
        return $this->belongsTo(Gaji::class);
    }
}
