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
        Schema::create('gajis', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('gaji_id')->nullable()->after('salary_tier')->foreign('gaji_id')->references('id')->on('gajis')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['gaji_id']);
            $table->dropColumn('gaji_id');
        });
        Schema::dropIfExists('gajis');
    }
};
