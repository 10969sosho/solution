<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_libur_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('batas_gaji', 12, 2)->default(2250000);
            $table->decimal('bonus_satu_hari', 12, 2)->default(75000);
            $table->decimal('bonus_setengah_hari', 12, 2)->default(50000);
            $table->decimal('pengali_bonus', 5, 2)->default(1.5);
            $table->timestamps();
        });

        DB::table('bonus_libur_settings')->insert([
            'batas_gaji' => 2250000,
            'bonus_satu_hari' => 75000,
            'bonus_setengah_hari' => 50000,
            'pengali_bonus' => 1.5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_libur_settings');
    }
};
