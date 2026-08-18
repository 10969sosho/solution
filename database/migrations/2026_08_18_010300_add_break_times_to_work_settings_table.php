<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_settings', function (Blueprint $table) {
            $table->time('break_out_time')->default('12:00:00')->after('check_out_time');
            $table->time('break_in_time')->default('13:00:00')->after('break_out_time');
        });

        DB::table('work_settings')
            ->where('name', 'Default')
            ->update(['break_out_time' => '12:00:00', 'break_in_time' => '13:00:00']);
    }

    public function down(): void
    {
        Schema::table('work_settings', function (Blueprint $table) {
            $table->dropColumn(['break_out_time', 'break_in_time']);
        });
    }
};