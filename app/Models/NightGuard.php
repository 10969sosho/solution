<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NightGuard extends Model
{
    protected $fillable = [
        'date',
        'employee_id',
        'status',
        'confirmed_at',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'confirmed_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }
}
