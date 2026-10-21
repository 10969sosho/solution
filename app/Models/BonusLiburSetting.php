<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BonusLiburSetting extends Model
{
    protected $fillable = [
        'batas_gaji',
        'bonus_satu_hari',
        'bonus_setengah_hari',
        'pengali_bonus',
    ];

    protected $casts = [
        'batas_gaji' => 'decimal:2',
        'bonus_satu_hari' => 'decimal:2',
        'bonus_setengah_hari' => 'decimal:2',
        'pengali_bonus' => 'decimal:2',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'batas_gaji' => 2250000,
            'bonus_satu_hari' => 75000,
            'bonus_setengah_hari' => 50000,
            'pengali_bonus' => 1.5,
        ]);
    }
}
