<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permits', function (Blueprint $table) {
            $table->string('location')->nullable()->after('employee_id');
            $table->string('position')->nullable()->after('location');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->decimal('previous_loans_total', 12, 2)->default(0)->after('principal');
            $table->decimal('all_loans_total', 12, 2)->default(0)->after('previous_loans_total');
        });
    }

    public function down(): void
    {
        Schema::table('permits', function (Blueprint $table) {
            $table->dropColumn(['location', 'position']);
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['previous_loans_total', 'all_loans_total']);
        });
    }
};
