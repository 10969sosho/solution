<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->time('check_in_time')->default('08:00:00');
            $table->time('break_out_time')->default('12:00:00');
            $table->time('break_in_time')->default('13:00:00');
            $table->time('check_out_time')->default('17:00:00');
            $table->integer('late_tolerance_minutes')->default(15);
            $table->date('effective_from')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_schedules');
    }
};