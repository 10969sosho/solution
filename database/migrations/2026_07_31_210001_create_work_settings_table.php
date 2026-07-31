<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Default');
            $table->time('check_in_time')->default('08:00:00');
            $table->time('check_out_time')->default('17:00:00');
            $table->integer('late_tolerance_minutes')->default(15);
            $table->integer('overtime_threshold_minutes')->default(30);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        DB::table('work_settings')->insert([
            [
                'name' => 'Default',
                'check_in_time' => '08:00:00',
                'check_out_time' => '17:00:00',
                'late_tolerance_minutes' => 15,
                'overtime_threshold_minutes' => 30,
                'is_active' => true,
                'description' => 'Jam kerja standar',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('work_settings');
    }
};
