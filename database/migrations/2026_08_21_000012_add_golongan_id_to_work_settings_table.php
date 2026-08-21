<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_settings', function (Blueprint $table) {
            $table->foreignId('golongan_id')->nullable()->after('name')->constrained('golongans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('work_settings', function (Blueprint $table) {
            $table->dropForeign(['golongan_id']);
            $table->dropColumn('golongan_id');
        });
    }
};
