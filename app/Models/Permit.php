<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permit extends Model
{
    public const TYPE_NO_DEDUCTION = 'no_deduction';

    public const TYPE_SALARY_DEDUCTION = 'salary_deduction';

    public const TYPE_ADDITION = 'addition';

    protected $fillable = [
        'employee_id',
        'category',
        'location',
        'position',
        'permit_date',
        'type',
        'start_time',
        'end_time',
        'duration_minutes',
        'reason',
        'status',
        'deduction_type',
        'deduction_hours',
        'deduction_minutes',
        'late_minutes',
        'late_fine_amount',
        'nominal',
    ];

    protected $casts = [
        'permit_date' => 'date',
        'duration_minutes' => 'integer',
        'nominal' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Agregasi izin 1 karyawan pada 1 tanggal -> field DailyAttendance.
     * Net (tambah - potong) jika ada nominal; else total menit no_deduction.
     * ponytail: campuran no_deduction + nominal -> nominal menang, menit diabaikan
     */
    public static function summaryFor($permits): array
    {
        $potong = 0.0;
        $tambah = 0.0;
        $minutes = 0;
        $hasMoney = false;

        foreach ($permits as $permit) {
            if ($permit->type === self::TYPE_SALARY_DEDUCTION) {
                $potong += (float) $permit->nominal;
                $hasMoney = true;
            } elseif ($permit->type === self::TYPE_ADDITION) {
                $tambah += (float) $permit->nominal;
                $hasMoney = true;
            } else {
                $minutes += (int) $permit->duration_minutes;
            }
        }

        if ($hasMoney) {
            $net = $tambah - $potong;
            if ($net > 0) {
                return ['tipe' => 'tambah_gaji', 'nominal' => round($net, 2)];
            }
            if ($net < 0) {
                return ['tipe' => 'potong_gaji', 'nominal' => round(-$net, 2)];
            }

            return ['tipe' => null, 'nominal' => 0];
        }

        return [
            'tipe' => $minutes > 0 ? 'tidak_potong' : null,
            'nominal' => $minutes,
        ];
    }

    /**
     * Sinkronkan agregat izin -> DailyAttendance (simpan/hapus izin dari modal harian).
     */
    public static function syncDailyAttendance(int $employeeId, string $date): void
    {
        $record = DailyAttendance::where('employee_id', $employeeId)
            ->where('date', $date)
            ->first();
        if (! $record) {
            return;
        }

        $permits = static::where('employee_id', $employeeId)
            ->whereDate('permit_date', $date)
            ->get();

        if ($permits->isEmpty()) {
            if ($record->tipe_nominal_izin !== null || (float) $record->nominal_izin > 0) {
                $record->update(['tipe_nominal_izin' => null, 'nominal_izin' => 0]);
            }

            return;
        }

        $summary = static::summaryFor($permits);
        $record->update([
            'keterangan' => 'izin',
            'tipe_nominal_izin' => $summary['tipe'],
            'nominal_izin' => $summary['nominal'],
        ]);
    }
}
