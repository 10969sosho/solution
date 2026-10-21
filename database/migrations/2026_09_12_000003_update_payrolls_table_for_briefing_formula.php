<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom payroll untuk formula briefing.
     * Setiap kolom dicek satu per satu supaya migrasi aman dijalankan ulang
     * pada database yang sebagian besar kolomnya sudah ada.
     */
    private function columns(): array
    {
        return [
            'total_izin_count' => fn (Blueprint $t) => $t->unsignedInteger('total_izin_count')->default(0),
            'izin_potong_gaji' => fn (Blueprint $t) => $t->decimal('izin_potong_gaji', 12, 2)->default(0),
            'izin_tambah_gaji' => fn (Blueprint $t) => $t->decimal('izin_tambah_gaji', 12, 2)->default(0),
            'potongan_terlambat_masuk' => fn (Blueprint $t) => $t->decimal('potongan_terlambat_masuk', 12, 2)->default(0),
            'potongan_terlambat_istirahat' => fn (Blueprint $t) => $t->decimal('potongan_terlambat_istirahat', 12, 2)->default(0),
            'total_potongan_terlambat' => fn (Blueprint $t) => $t->decimal('total_potongan_terlambat', 12, 2)->default(0),
            'total_lembur_minutes' => fn (Blueprint $t) => $t->unsignedInteger('total_lembur_minutes')->default(0),
            'nominal_lembur' => fn (Blueprint $t) => $t->decimal('nominal_lembur', 12, 2)->default(0),
            'uang_jaga_malam' => fn (Blueprint $t) => $t->decimal('uang_jaga_malam', 12, 2)->default(0),
            'bonus_libur' => fn (Blueprint $t) => $t->decimal('bonus_libur', 12, 2)->default(0),
            'total_hari_libur' => fn (Blueprint $t) => $t->unsignedTinyInteger('total_hari_libur')->default(0),
            'hari_potong_masuk' => fn (Blueprint $t) => $t->unsignedTinyInteger('hari_potong_masuk')->default(0),
            'potongan_masuk' => fn (Blueprint $t) => $t->decimal('potongan_masuk', 12, 2)->default(0),
            'pinjaman_deduction' => fn (Blueprint $t) => $t->decimal('pinjaman_deduction', 12, 2)->default(0),
            'grand_total' => fn (Blueprint $t) => $t->decimal('grand_total', 12, 2)->default(0),
            'payment_method' => fn (Blueprint $t) => $t->string('payment_method')->default('transfer'),
        ];
    }

    public function up(): void
    {
        foreach ($this->columns() as $column => $define) {
            if (Schema::hasColumn('payrolls', $column)) {
                continue;
            }

            Schema::table('payrolls', function (Blueprint $table) use ($define) {
                $define($table);
            });
        }
    }

    public function down(): void
    {
        $existing = array_values(array_filter(
            array_keys($this->columns()),
            fn ($column) => Schema::hasColumn('payrolls', $column)
        ));

        if ($existing === []) {
            return;
        }

        Schema::table('payrolls', function (Blueprint $table) use ($existing) {
            $table->dropColumn($existing);
        });
    }
};
