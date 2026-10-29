<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Nominal potongan izin per karyawan per periode bulan.
 * Diinput manual lewat menu Manajemen Izin, ditarik ke Rekap Bulanan
 * dan komponen potongan_izin (izin_potong_gaji) saat penggajian.
 */
class PermitDeduction extends Model
{
    protected $fillable = [
        'employee_id',
        'period_year',
        'period_month',
        'amount',
        'notes',
    ];

    protected $casts = [
        'period_year' => 'integer',
        'period_month' => 'integer',
        'amount' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public static function amountFor(int $employeeId, int $year, int $month): ?float
    {
        $row = static::where('employee_id', $employeeId)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->first();

        return $row ? (float) $row->amount : null;
    }
}
