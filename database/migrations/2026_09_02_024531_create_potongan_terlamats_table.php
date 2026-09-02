<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('potongan_terlamats', function (Blueprint $table) {
            $table->id();
            $table->enum('golongan_type', ['gudang_kandang', 'mandor_admin']);
            $table->integer('min_minutes');
            $table->integer('max_minutes')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('potongan_terlamats');
    }
};
