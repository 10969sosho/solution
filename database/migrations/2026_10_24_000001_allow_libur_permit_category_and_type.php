<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Opsi "Libur" di form Kelola Izin memakai category & type = 'libur',
     * tapi enum MySQL lama belum memuat nilai itu sehingga insert gagal (Data truncated).
     */
    public function up(): void
    {
        if (! Schema::hasTable('permits')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            // SQLite enum = CHECK constraint; ubah ke string supaya 'libur' valid di test.
            Schema::table('permits', function (Blueprint $table) {
                $table->string('category', 30)->nullable()->change();
            });

            return;
        }

        DB::statement("ALTER TABLE permits MODIFY category ENUM('tidak_masuk','terlambat','pulang_awal','libur') NULL DEFAULT NULL");
        DB::statement("ALTER TABLE permits MODIFY type ENUM('no_deduction','salary_deduction','addition','libur') NOT NULL DEFAULT 'no_deduction'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("UPDATE permits SET category = 'tidak_masuk' WHERE category = 'libur'");
            DB::statement("UPDATE permits SET type = 'no_deduction' WHERE type = 'libur'");
            Schema::table('permits', function (Blueprint $table) {
                $table->enum('category', ['tidak_masuk', 'terlambat', 'pulang_awal'])->nullable()->change();
            });

            return;
        }

        DB::statement("UPDATE permits SET category = 'tidak_masuk' WHERE category = 'libur'");
        DB::statement("UPDATE permits SET type = 'no_deduction' WHERE type = 'libur'");
        DB::statement("ALTER TABLE permits MODIFY category ENUM('tidak_masuk','terlambat','pulang_awal') NULL DEFAULT NULL");
        DB::statement("ALTER TABLE permits MODIFY type ENUM('no_deduction','salary_deduction','addition') NOT NULL DEFAULT 'no_deduction'");
    }
};
