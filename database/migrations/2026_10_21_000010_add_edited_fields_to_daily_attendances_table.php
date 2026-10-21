<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('daily_attendances', 'edited_fields')) {
            Schema::table('daily_attendances', function (Blueprint $table) {
                $table->json('edited_fields')->nullable()->after('is_fixed');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('daily_attendances', 'edited_fields')) {
            Schema::table('daily_attendances', function (Blueprint $table) {
                $table->dropColumn('edited_fields');
            });
        }
    }
};
