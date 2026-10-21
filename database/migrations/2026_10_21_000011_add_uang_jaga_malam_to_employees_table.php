<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('employees', 'uang_jaga_malam')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->decimal('uang_jaga_malam', 12, 2)->default(0)->after('is_night_guard');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('employees', 'uang_jaga_malam')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('uang_jaga_malam');
            });
        }
    }
};
