<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PotonganTerlambat extends Model
{
    protected $table = 'potongan_terlamats';
    protected $fillable = ['golongan_type', 'min_minutes', 'max_minutes', 'amount'];

    public function getGolonganTypeLabelAttribute(): string
    {
        return match($this->golongan_type) {
            'gudang_kandang' => 'Gudang & Kandang',
            'mandor_admin' => 'Mandor & Admin',
            default => '-',
        };
    }

    public function getRangeLabelAttribute(): string
    {
        if ($this->max_minutes === null) {
            return '>' . $this->min_minutes . ' menit';
        }
        return $this->min_minutes . '-' . $this->max_minutes . ' menit';
    }
}
