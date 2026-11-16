<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lokasi extends Model
{
    protected $fillable = ['name', 'code'];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function devices()
    {
        return $this->hasMany(Device::class, 'lokasi_id');
    }
}
