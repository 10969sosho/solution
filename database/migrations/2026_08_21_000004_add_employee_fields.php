<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('tanggal_keluar')->nullable()->after('salary_tier');
            $table->time('jam_masuk_normal')->nullable()->after('tanggal_keluar');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['tanggal_keluar', 'jam_masuk_normal']);
        });
    }
};