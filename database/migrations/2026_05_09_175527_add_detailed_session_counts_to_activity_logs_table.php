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
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->integer('session_risky_count')->default(0)->after('session_duplicate_count');
            $table->integer('session_role_based_count')->default(0)->after('session_risky_count');
            $table->integer('session_disposable_count')->default(0)->after('session_role_based_count');
            $table->integer('session_catch_all_count')->default(0)->after('session_disposable_count');
            $table->integer('session_typo_count')->default(0)->after('session_catch_all_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn([
                'session_risky_count',
                'session_role_based_count',
                'session_disposable_count',
                'session_catch_all_count',
                'session_typo_count'
            ]);
        });
    }
};
