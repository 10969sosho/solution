<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('night_guards')) {
            Schema::create('night_guards', function (Blueprint $table) {
                $table->id();
                $table->date('date')->unique();
                $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->enum('status', ['draft', 'confirmed'])->default('draft');
                $table->timestamp('confirmed_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('night_guards');
    }
};
