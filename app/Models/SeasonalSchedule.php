<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SeasonalSchedule extends Model
{
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'check_in_delta_minutes',
        'check_out_delta_minutes',
        'force_check_in_time',
        'is_active',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function forDate(Carbon $date): ?self
    {
        return static::active()
            ->where('start_date', '<=', $date->toDateString())
            ->where(function (Builder $q) use ($date) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $date->toDateString());
            })
            ->orderBy('start_date', 'desc')
            ->first();
    }
}