<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('attendance_compilations')) {
            Schema::create('attendance_compilations', function (Blueprint $table) {
                $table->id();
                $table->date('date')->unique();
                $table->enum('status', ['draft', 'lock', 'fix'])->default('draft');
                $table->timestamp('locked_at')->nullable();
                $table->timestamp('fixed_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('daily_attendances')) {
            Schema::create('daily_attendances', function (Blueprint $table) {
                $table->id();
                $table->date('date')->index();
                $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
                $table->time('check_in')->nullable();
                $table->time('break_out')->nullable();
                $table->time('break_in')->nullable();
                $table->time('check_out')->nullable();
                $table->unsignedInteger('late_check_in_minutes')->default(0);
                $table->unsignedInteger('late_break_in_minutes')->default(0);
                $table->unsignedInteger('overtime_minutes')->default(0);
                $table->boolean('is_anomaly')->default(false);
                $table->string('anomaly_reason')->nullable();
                $table->enum('keterangan', ['hadir', 'izin', 'libur', 'alpha'])->default('hadir');
                $table->decimal('nominal_izin', 12, 2)->default(0);
                $table->enum('tipe_nominal_izin', ['potong_gaji', 'tambah_gaji'])->nullable();
                $table->boolean('is_fixed')->default(false);
                $table->timestamps();

                $table->unique(['date', 'employee_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_attendances');
        Schema::dropIfExists('attendance_compilations');
    }
};
