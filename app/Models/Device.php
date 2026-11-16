<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'name',
        'serial_number',
        'lokasi_id',
        'ip_address',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_id');
    }

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class, 'machine_sn', 'serial_number');
    }
}
