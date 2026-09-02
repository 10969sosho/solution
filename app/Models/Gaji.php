<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gaji extends Model
{
    protected $fillable = ['name', 'amount'];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
