<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkSetting extends Model
{
    protected $fillable = [
        'name',
        'golongan_id',
        'check_in_time',
        'check_out_time',
        'break_out_time',
        'break_in_time',
        'late_tolerance_minutes',
        'overtime_threshold_minutes',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function golongan()
    {
        return $this->belongsTo(Golongan::class);
    }

    public static function getActive()
    {
        return static::where('is_active', true)->whereNull('golongan_id')->first();
    }

    public static function getActiveForGolongan(?int $golonganId)
    {
        if ($golonganId) {
            $setting = static::where('is_active', true)->where('golongan_id', $golonganId)->first();
            if ($setting) {
                return $setting;
            }
        }

        return static::where('is_active', true)->whereNull('golongan_id')->first();
    }
}
