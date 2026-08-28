<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class WorkSetting extends Model
{
    protected $fillable = [
        'name',
        'day',
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

    /**
     * Ambil setting jam kerja yang berlaku untuk golongan & hari tertentu.
     * Field "day" berisi daftar hari (koma-separated) atau NULL = semua hari.
     * Prioritas: (1) hari spesifik golongan, (2) "Semua Hari" golongan,
     * (3) hari spesifik global, (4) "Semua Hari" global.
     */
    public static function getActiveForGolongan(?int $golonganId, ?Carbon $date = null): ?self
    {
        $dayName = $date ? self::dayName($date) : null;

        $golonganIds = $golonganId ? [$golonganId, null] : [null];

        foreach ($golonganIds as $gid) {
            $setting = static::where('is_active', true)
                ->where('golongan_id', $gid)
                ->where(function ($query) use ($dayName) {
                    $query->whereNull('day')->orWhereRaw('FIND_IN_SET(?, day)', [$dayName]);
                })
                ->orderByRaw('CASE WHEN day IS NULL THEN 1 ELSE 0 END ASC')
                ->first();

            if ($setting) {
                return $setting;
            }
        }

        return null;
    }

    protected static function dayName(Carbon $date): string
    {
        return match ($date->dayOfWeek) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            default => 'Minggu',
        };
    }
}
