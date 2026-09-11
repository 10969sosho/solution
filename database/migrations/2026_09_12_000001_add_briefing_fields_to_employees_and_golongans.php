<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('golongans', function (Blueprint $table) {
            if (!Schema::hasColumn('golongans', 'is_confidential')) {
                $table->boolean('is_confidential')->default(false)->after('type');
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('address');
                $table->string('account_number')->nullable()->after('bank_name');
                $table->string('account_holder')->nullable()->after('account_number');
                $table->enum('payment_method', ['transfer', 'cash'])->default('transfer')->after('account_holder');
                $table->unsignedTinyInteger('leave_quota')->nullable()->after('payment_method');
                $table->boolean('is_night_guard')->default(false)->after('leave_quota');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'account_number',
                'account_holder',
                'payment_method',
                'leave_quota',
                'is_night_guard',
            ]);
        });

        Schema::table('golongans', function (Blueprint $table) {
            $table->dropColumn('is_confidential');
        });
    }
};
