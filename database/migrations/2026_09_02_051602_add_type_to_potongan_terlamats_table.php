<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('potongan_terlamats', function (Blueprint $table) {
            $table->enum('type', ['masuk_kerja', 'setelah_istirahat'])->default('masuk_kerja')->after('golongan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('potongan_terlamats', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
