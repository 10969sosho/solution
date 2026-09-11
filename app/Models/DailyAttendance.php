<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyAttendance extends Model
{
    protected $fillable = [
        'date',
        'employee_id',
        'check_in',
        'break_out',
        'break_in',
        'check_out',
        'late_check_in_minutes',
        'late_break_in_minutes',
        'overtime_minutes',
        'is_anomaly',
        'anomaly_reason',
        'keterangan',
        'nominal_izin',
        'tipe_nominal_izin',
        'is_fixed',
    ];

    protected $casts = [
        'late_check_in_minutes' => 'integer',
        'late_break_in_minutes' => 'integer',
        'overtime_minutes' => 'integer',
        'is_anomaly' => 'boolean',
        'nominal_izin' => 'decimal:2',
        'is_fixed' => 'boolean',
    ];

    public function setDateAttribute($value): void
    {
        $this->attributes['date'] = $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d')
            : substr((string) $value, 0, 10);
    }

    public function getDateAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value) : null;
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function compilation()
    {
        return $this->belongsTo(AttendanceCompilation::class, 'date', 'date');
    }

    /**
     * Hitung apakah terlambat diabaikan jika terdapat nominal izin.
     */
    public function isLateIgnored(): bool
    {
        return $this->keterangan === 'izin' && (float) $this->nominal_izin > 0;
    }
}
