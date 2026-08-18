<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->smallInteger('period_year');
            $table->tinyInteger('period_month');
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->decimal('late_deduction', 12, 2)->default(0);
            $table->decimal('loan_deduction', 12, 2)->default(0);
            $table->decimal('absence_deduction', 12, 2)->default(0);
            $table->decimal('total_deduction', 12, 2)->default(0);
            $table->decimal('attendance_bonus', 12, 2)->default(0);
            $table->decimal('total_incentive', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);
            $table->json('breakdown')->nullable();
            $table->enum('status', ['draft', 'paid'])->default('draft');
            $table->timestamps();

            $table->unique(['employee_id', 'period_year', 'period_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};