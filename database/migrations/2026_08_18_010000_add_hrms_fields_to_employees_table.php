<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('location')->nullable()->after('department');
            $table->decimal('salary', 12, 2)->default(0)->after('status');
            $table->string('salary_tier')->nullable()->after('salary');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['location', 'salary', 'salary_tier']);
        });
    }
};