<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('permit_date');
            $table->enum('type', ['no_deduction', 'salary_deduction'])->default('no_deduction');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes')->default(0);
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();

            $table->index(['employee_id', 'permit_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permits');
    }
};