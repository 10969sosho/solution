<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite enum = CHECK constraint; rebuild kolom supaya 'addition' valid di test.
            Schema::table('permits', function (Blueprint $table) {
                $table->string('type', 30)->default('no_deduction')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE permits MODIFY type ENUM('no_deduction', 'salary_deduction', 'addition') NOT NULL DEFAULT 'no_deduction'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("UPDATE permits SET type = 'no_deduction' WHERE type = 'addition'");
            Schema::table('permits', function (Blueprint $table) {
                $table->enum('type', ['no_deduction', 'salary_deduction'])->default('no_deduction')->change();
            });

            return;
        }

        DB::statement("UPDATE permits SET type = 'no_deduction' WHERE type = 'addition'");
        DB::statement("ALTER TABLE permits MODIFY type ENUM('no_deduction', 'salary_deduction') NOT NULL DEFAULT 'no_deduction'");
    }
};
