<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permits', function (Blueprint $table) {
            $table->string('deduction_type')->nullable()->after('type');
            $table->integer('deduction_hours')->default(0)->after('deduction_type');
            $table->integer('deduction_minutes')->default(0)->after('deduction_hours');
        });
    }

    public function down(): void
    {
        Schema::table('permits', function (Blueprint $table) {
            $table->dropColumn(['deduction_type', 'deduction_hours', 'deduction_minutes']);
        });
    }
};
