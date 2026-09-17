<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_setting_golongan', function (Blueprint $table) {
            $table->foreignId('work_setting_id')->constrained('work_settings')->cascadeOnDelete();
            $table->foreignId('golongan_id')->constrained('golongans')->cascadeOnDelete();
            $table->primary(['work_setting_id', 'golongan_id']);
        });

        DB::table('work_settings')->whereNotNull('golongan_id')->get()->each(function ($setting) {
            DB::table('work_setting_golongan')->insert([
                'work_setting_id' => $setting->id,
                'golongan_id' => $setting->golongan_id,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_setting_golongan');
    }
};
