<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // Dedicated atomic counter columns — safe for concurrent chunk updates
            $table->unsignedInteger('session_valid_count')->default(0)->after('details');
            $table->unsignedInteger('session_invalid_count')->default(0)->after('session_valid_count');
            $table->unsignedInteger('session_duplicate_count')->default(0)->after('session_invalid_count');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn(['session_valid_count', 'session_invalid_count', 'session_duplicate_count']);
        });
    }
};
