<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Nominal potongan izin per karyawan per periode bulan (data lama).
 * Menu Manajemen Izin sudah dihapus; sejak revisi ini potongan izin dihitung
 * otomatis dari menit izin di Absen Daily, jadi tabel ini tidak lagi dipakai
 * perhitungan (dipertahankan agar riwayat lama tidak hilang).
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
