<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lokasi extends Model
{
    protected $fillable = ['name', 'code'];

    protected $casts = [
        'code' => 'string',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}