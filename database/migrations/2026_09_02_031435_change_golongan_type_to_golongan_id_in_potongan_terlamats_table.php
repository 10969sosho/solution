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
        Schema::table('potongan_terlamats', function (Blueprint $table) {
            $table->dropColumn('golongan_type');
            $table->unsignedBigInteger('golongan_id')->after('id')->foreign('golongan_id')->references('id')->on('golongans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('potongan_terlamats', function (Blueprint $table) {
            $table->dropForeign(['golongan_id']);
            $table->dropColumn('golongan_id');
            $table->enum('golongan_type', ['gudang_kandang', 'mandor_admin'])->after('id');
        });
    }
};
