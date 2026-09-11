<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            if (!Schema::hasColumn('payrolls', 'grand_total')) {
                $table->unsignedInteger('total_izin_count')->default(0)->after('absence_deduction');
                $table->decimal('izin_potong_gaji', 12, 2)->default(0)->after('total_izin_count');
                $table->decimal('izin_tambah_gaji', 12, 2)->default(0)->after('izin_potong_gaji');
                $table->decimal('potongan_terlambat_masuk', 12, 2)->default(0)->after('izin_tambah_gaji');
                $table->decimal('potongan_terlambat_istirahat', 12, 2)->default(0)->after('potongan_terlambat_masuk');
                $table->decimal('total_potongan_terlambat', 12, 2)->default(0)->after('potongan_terlambat_istirahat');
                $table->unsignedInteger('total_lembur_minutes')->default(0)->after('total_potongan_terlambat');
                $table->decimal('nominal_lembur', 12, 2)->default(0)->after('total_lembur_minutes');
                $table->decimal('uang_jaga_malam', 12, 2)->default(0)->after('nominal_lembur');
                $table->decimal('bonus_libur', 12, 2)->default(0)->after('uang_jaga_malam');
                $table->unsignedTinyInteger('total_hari_libur')->default(0)->after('bonus_libur');
                $table->unsignedTinyInteger('hari_potong_masuk')->default(0)->after('total_hari_libur');
                $table->decimal('potongan_masuk', 12, 2)->default(0)->after('hari_potong_masuk');
                $table->decimal('pinjaman_deduction', 12, 2)->default(0)->after('potongan_masuk');
                $table->decimal('grand_total', 12, 2)->default(0)->after('net_salary');
                $table->string('payment_method')->default('transfer')->after('grand_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
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
            ]);
        });
    }
};
