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
        'bank_name',
        'account_number',
        'account_holder',
        'payment_method',
        'leave_quota',
        'is_night_guard',
    ];

    protected $casts = [
        'join_date' => 'date',
        'salary' => 'decimal:2',
        'tanggal_keluar' => 'date',
        'jam_masuk_normal' => 'string',
        'is_night_guard' => 'boolean',
        'leave_quota' => 'integer',
    ];

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class, 'user_id', 'employee_id');
    }

    public function dailyAttendances()
    {
        return $this->hasMany(DailyAttendance::class);
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

    /**
     * Tentukan jatah libur bulanan efektif (Admin/Mandor = 2, Jaga Malam/AGK = 1).
     */
    public function getEffectiveLeaveQuota(): int
    {
        if (!is_null($this->leave_quota)) {
            return (int) $this->leave_quota;
        }

        $pos = strtolower($this->position ?? '');
        $jabatanName = strtolower($this->jabatan?->name ?? '');
        $golType = $this->golongan?->type;

        if (
            str_contains($pos, 'admin') ||
            str_contains($pos, 'mandor') ||
            str_contains($jabatanName, 'admin') ||
            str_contains($jabatanName, 'mandor') ||
            $golType === 'mandor_admin'
        ) {
            return (int) config('payroll_rules.default_leave_quota.admin_mandor', 2);
        }

        return (int) config('payroll_rules.default_leave_quota.jaga_malam_agk', 1);
    }

    public function isMandor(): bool
    {
        $pos = strtolower($this->position ?? '');
        $jabatanName = strtolower($this->jabatan?->name ?? '');
        return str_contains($pos, 'mandor') || str_contains($jabatanName, 'mandor');
    }

    public function isConfidential(): bool
    {
        return (bool) ($this->golongan?->is_confidential ?? false);
    }
}
