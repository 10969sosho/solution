<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('permits', 'nominal')) {
            Schema::table('permits', function (Blueprint $table) {
                $table->decimal('nominal', 12, 2)->default(0)->after('late_fine_amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('permits', 'nominal')) {
            Schema::table('permits', fn (Blueprint $table) => $table->dropColumn('nominal'));
        }
    }
};
