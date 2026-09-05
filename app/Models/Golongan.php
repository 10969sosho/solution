<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Golongan extends Model
{
    protected $fillable = ['name', 'description', 'type'];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function workSettings()
    {
        return $this->hasMany(WorkSetting::class);
    }

    public function potonganTerlamats()
    {
        return $this->hasMany(PotonganTerlambat::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'gudang_kandang' => 'Gudang & Kandang',
            'mandor_admin' => 'Mandor & Admin',
            default => '-',
        };
    }
}