<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PotonganTerlambat extends Model
{
    protected $table = 'potongan_terlamats';
    protected $fillable = ['golongan_id', 'type', 'min_minutes', 'max_minutes', 'amount'];

    public function golongan()
    {
        return $this->belongsTo(Golongan::class);
    }

    public function getRangeLabelAttribute(): string
    {
        if ($this->max_minutes === null) {
            return '>' . $this->min_minutes . ' menit';
        }
        return $this->min_minutes . '-' . $this->max_minutes . ' menit';
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'masuk_kerja' ? 'Masuk Kerja' : 'Setelah Istirahat';
    }
}
