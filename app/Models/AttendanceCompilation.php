<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceCompilation extends Model
{
    protected $fillable = [
        'date',
        'status',
        'locked_at',
        'fixed_at',
        'notes',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
        'fixed_at' => 'datetime',
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

    public function dailyAttendances()
    {
        return $this->hasMany(DailyAttendance::class, 'date', 'date');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isLocked(): bool
    {
        return $this->status === 'lock';
    }

    public function isFixed(): bool
    {
        return $this->status === 'fix';
    }
}
