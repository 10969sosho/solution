<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('golongans', function (Blueprint $table) {
            $table->dropColumn('code');
        });

        Schema::table('jabatans', function (Blueprint $table) {
            $table->dropColumn('code');
        });

        Schema::table('lokasis', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }

    public function down(): void
    {
        Schema::table('golongans', function (Blueprint $table) {
            $table->string('code')->unique()->after('name');
        });

        Schema::table('jabatans', function (Blueprint $table) {
            $table->string('code')->unique()->after('name');
        });

        Schema::table('lokasis', function (Blueprint $table) {
            $table->string('code')->unique()->after('name');
        });
    }
};
