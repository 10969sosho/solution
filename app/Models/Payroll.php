<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $fillable = [
        'employee_id',
        'period_year',
        'period_month',
        'base_salary',
        'late_deduction',
        'loan_deduction',
        'absence_deduction',
        'total_deduction',
        'attendance_bonus',
        'total_incentive',
        'net_salary',
        'total_izin_count',
        'izin_potong_gaji',
        'izin_tambah_gaji',
        'potongan_terlambat_masuk',
        'potongan_terlambat_istirahat',
        'total_potongan_terlambat',
        'total_lembur_minutes',
        'nominal_lembur',
        'uang_jaga_malam',
        'bonus_libur',
        'total_hari_libur',
        'hari_potong_masuk',
        'potongan_masuk',
        'pinjaman_deduction',
        'grand_total',
        'payment_method',
        'breakdown',
        'status',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'late_deduction' => 'decimal:2',
        'loan_deduction' => 'decimal:2',
        'absence_deduction' => 'decimal:2',
        'total_deduction' => 'decimal:2',
        'attendance_bonus' => 'decimal:2',
        'total_incentive' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'total_izin_count' => 'integer',
        'izin_potong_gaji' => 'decimal:2',
        'izin_tambah_gaji' => 'decimal:2',
        'potongan_terlambat_masuk' => 'decimal:2',
        'potongan_terlambat_istirahat' => 'decimal:2',
        'total_potongan_terlambat' => 'decimal:2',
        'total_lembur_minutes' => 'integer',
        'nominal_lembur' => 'decimal:2',
        'uang_jaga_malam' => 'decimal:2',
        'bonus_libur' => 'decimal:2',
        'total_hari_libur' => 'integer',
        'hari_potong_masuk' => 'integer',
        'potongan_masuk' => 'decimal:2',
        'pinjaman_deduction' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'breakdown' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}